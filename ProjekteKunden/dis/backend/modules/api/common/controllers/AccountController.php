<?php
/**
 * Created by PhpStorm.
 * User: alali
 * Date: 24.09.2018
 * Time: 10:17
 */

namespace app\modules\api\common\controllers;

use app\resources\User;
use app\rest\IndexAction;
use Da\User\Factory\MailFactory;
use Da\User\Form\RecoveryForm;
use Da\User\Model\Token;
use Da\User\Query\TokenQuery;
use Da\User\Service\PasswordRecoveryService;
use Da\User\Service\ResetPasswordService;
use yii\base\DynamicModel;
use yii\base\Security;
use yii\filters\auth\HttpBearerAuth;
use yii\rest\Controller;
use yii\filters\AccessControl;
use yii\rest\OptionsAction;
use yii\web\BadRequestHttpException;
use Yii;
class AccountController extends Controller
{

    public function behaviors()
    {
        return array_merge(
            parent::behaviors(),
            [
                'authenticator' => [
                    'class' => HttpBearerAuth::class,
                    'except' => ['send-recovery-email', 'reset-password', 'check-recovery-link']
                ],
                'access' => [
                    'class' => AccessControl::class,
                    'only' => ['update', 'change-password'],
                    'rules' => [
                        [
                            'allow' => true,
                            'roles' => ['@']
                        ],
                    ]
                ]
            ]
        );
    }

    public function actions()
    {
        return array_merge(parent::actions(), [
            'options' => OptionsAction::class
        ]);
    }

    public function actionProfile()
    {
        $user = User::findOne(\Yii::$app->user->id);
        return [
            "email" => $user->email,
            "name" => $user->profile->name
        ];
    }

    public function actionUpdate()
    {
        $bodyParams = Yii::$app->getRequest()->getBodyParams();

        $model = DynamicModel::validateData(['name' => $bodyParams["name"], 'email' => $bodyParams["email"]], [
            [['name', 'email'], 'required'],
            [['name', 'email'], 'string'],
            ['email', 'email'],
        ]);

        if ($model->validate()) {
            $user = User::findOne(\Yii::$app->user->id);
            $profile = $user->profile;
            $profile->name = $bodyParams["name"];
            $user->email = $bodyParams["email"];
            $user->save();
            $profile->save();
        }

        return $model;
    }

    public function actionChangePassword()
    {
        $bodyParams = Yii::$app->getRequest()->getBodyParams();

        $model = DynamicModel::validateData(['oldPassword' => $bodyParams["oldPassword"], 'newPassword' => $bodyParams["newPassword"], 'newPasswordConfirmation' => $bodyParams["newPasswordConfirmation"],], [
            [['oldPassword', 'newPassword', 'newPasswordConfirmation'], 'required'],
            [['oldPassword', 'newPassword', 'newPasswordConfirmation'], 'string'],
            ['newPassword', 'string', 'min' => 6, 'max' => 72],
            ['newPassword', 'compare', 'compareAttribute' => 'newPasswordConfirmation'],
            [['oldPassword'], function () {
                $user = User::findOne(\Yii::$app->user->id);
                $securityModel = new Security();
                if (!$user || !$securityModel->validatePassword($this->oldPassword, $user->password_hash)) {
                    $this->addError('oldPassword', 'Incorrect password');
                }
            }]
        ]);

        if ($model->validate()) {
            $user = User::findOne(\Yii::$app->user->id);
            $user->password = $bodyParams["newPassword"];
            $user->save();
        }

        return $model;
    }

    public function actionSendRecoveryEmail()
    {
        $bodyParams = Yii::$app->getRequest()->getBodyParams();

        $model = DynamicModel::validateData(['email' => $bodyParams["email"]], [
            [['email'], 'required'],
            [['email'], 'email']
        ]);

        if ($model->validate()) {
            $user = User::find()->where(['email' => $bodyParams['email']])->one();
            if ($user instanceof User) {
                if(!$user->hasProperty('is_ldap_user') || !$user->is_ldap_user) {
                    $token = Token::find()->whereUserId($user->id)->whereIsRecoveryType()->one();

                    // note: The time before a recovery token becomes invalid is 6 hours, every 6 hours can the user send a new recovery email
                    if($token && !$token->isExpired) {
                        $expirationTime = Yii::$app->getModule('user')->tokenRecoveryLifespan;
                        Yii::$app->response->statusCode = 422;
                        return [
                            'kind' => 'still_valid',
                            'message' => 'You already received a recovery email, you can send it again ' . \Yii::$app->formatter->asRelativeTime($token->created_at + $expirationTime)
                        ];
                    }

                    /** @var Token $token */
                    $token = \Yii::createObject([
                        'class' => Token::class,
                        'user_id' => $user->id,
                        'type' => Token::TYPE_RECOVERY,
                    ]);

                    if (!$token->save(false)) {
                        return false;
                    }

                    $mailService = MailFactory::makeRecoveryMailerService($user->email);
                    if (\Yii::$container->get(PasswordRecoveryService::class, [$user->email, $mailService])->run()) {
                        return true;
                    }
                } else {
                    Yii::$app->response->statusCode = 422;
                    return [
                        'kind' => 'is_ldap',
                        'message' => 'Resetting password for ldap users is not allowed.'
                    ];
                }
            } else {
                Yii::$app->response->statusCode = 422;
                return [
                    'kind' => 'not_found',
                    'message' => 'Email not recognized'
                ];
            }
        }

        return $model;
    }

    public function actionCheckRecoveryLink()
    {
        $bodyParams = Yii::$app->request->bodyParams;
        $code = $bodyParams['code'];
        $userId = $bodyParams['userId'];

        $user = \app\models\core\User::find()->andWhere(['id' => $userId])->one();
        if($user) {
            $token = Token::find()->whereUserId($user->id)->whereCode($code)->whereIsRecoveryType()->one();

            if (empty($token) || ! $token instanceof Token || $token->isExpired || $token->user === null) {
                Yii::$app->response->statusCode = 422;
                return [
                    'message' => 'Recovery link is invalid or expired. Please try requesting a new one.'
                ];
            }

            return true;
        }
        Yii::$app->response->statusCode = 422;
        return [
            'message' => 'Recovery link is invalid or expired. Please try requesting a new one.'
        ];
    }

    public function actionResetPassword()
    {
        $bodyParams = Yii::$app->request->bodyParams;
        if (isset($bodyParams['code']))
            $code = $bodyParams['code'];
        if (isset($bodyParams['userId']))
            $userId = $bodyParams['userId'];
        if (isset($bodyParams['password']))
            $newPassword = $bodyParams['password'];

        if ($code === null || $userId === null || $newPassword === null) {
            throw new BadRequestHttpException('Bad request');
        }
        $model = DynamicModel::validateData(['password' => $newPassword], [
            'passwordRequired' => ['password', 'required'],
            'passwordLength'   => ['password', 'string', 'min' => 6, 'max' => 72],
        ]);

        if ($model->validate()) {
            $user = \app\models\core\User::find()->andWhere(['id' => $userId])->one();
            $token = Token::find()->whereUserId($user->id)->whereCode($code)->whereIsRecoveryType()->one();

            if (\Yii::$container->get(ResetPasswordService::class, [$newPassword, $token->user])->run()) {
                $token->delete();
                return true;
            }
        }
        return $model;
    }
}
