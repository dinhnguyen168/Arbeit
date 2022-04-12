<?php
/**
 * Created by PhpStorm.
 * User: alali
 * Date: 10.09.2018
 * Time: 11:04
 */

namespace app\modules\api\common\controllers;

use app\models\core\User;
use da\User\model\Assignment;
use da\User\Form\LoginForm;
use da\User\Form\RegistrationForm;
use Yii;

use yii\base\Security;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\VerbFilter;
use yii\rest\Controller;
use yii\web\BadRequestHttpException;
use yii\web\UnprocessableEntityHttpException;

class AuthController extends Controller
{
    /**
     * @var \app\models\core\User
     */
    public $userModel = \app\resources\User::class;

    public $serializer = [
        'class' => 'yii\rest\Serializer',
        'collectionEnvelope' => 'items',
    ];

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::class,
            'except' => ['login']
        ];
        $behaviors['verbs'] = [
            'class' => VerbFilter::class,
            'actions' => [
                'login' => ['POST'],
                'logout' => ['POST'],
                'refresh' => ['GET']
            ]
        ];
        return $behaviors;
    }

    public function actionLogin () {
        $bodyParams = Yii::$app->getRequest()->getBodyParams();
        $username = $password = null;
        if (isset($bodyParams['username']))
            $username = $bodyParams['username'];
        if (isset($bodyParams['password']))
            $password = $bodyParams['password'];
        if ($username === null || $password === null) {
            throw new BadRequestHttpException('password/username is not set');
        }

        $user = $this->findUser($username, $password);
        $authenticated = false;

        if($user) {
            if($user->hasProperty('is_ldap_user') && $user->is_ldap_user) {
                if ($user->blocked_at == null) {
                    $authenticated = true;
                }
            } else {
                $securityModel = new Security();
                if ($securityModel->validatePassword($password, $user->password_hash) && $user->blocked_at == null) {
                    $authenticated = true;
                }
            }
        }


        if ($user === null || !$authenticated) {
            Yii::$app->response->statusCode = 422;
            return [
                [
                    'field' => 'password',
                    'message' => 'Login data is invalid'
                ]
            ];
        } else {
            if ($user->api_token && $user->token_expire > time() && Yii::$app->request->isFromIntranet()) {
                // token still valid
                $user->updateAttributes([
                    'last_login_at' => time(),
                    'token_expire' => time() + User::TOKEN_VALID_TIME
                ]);
            } else {
                // always update api_token if not on intranet
                $user->updateAttributes([
                    'last_login_at' => time(),
                    'api_token' => \Yii::$app->security->generateRandomString(),
                    'token_expire' => time() + User::TOKEN_VALID_TIME
                ]);
            }
            $user->save();
            return $user;
        }
    }

    protected function findUser($username, $password) {
        $user = null;

        if (\Yii::$app->has('ldap')) {
            $connected = \Yii::$app->ldap->connect();
            if ($connected) {
                $infos = \Yii::$app->ldap->searchUser($username, ["mail", "sn", "givenname", "uid", "displayname"]);
                if ($infos && sizeof($infos)) {
                    $ldapUser = $infos[0];
                    if (\Yii::$app->ldap->authenticate($ldapUser["dn"], $password)) {
                        return $this->autoCreateUpdateUserFromLdapUser($ldapUser, $password);
                    }
                }
            }
        }


        if (filter_var($username, FILTER_VALIDATE_EMAIL)) {
            $user = $this->userModel::findOne(['email' => $username]);
        }
        if ($user == null) {
            $user = $this->userModel::findOne(['username' => $username]);
        }

        return $user;
    }

    protected function autoCreateUpdateUserFromLdapUser ($ldapUser, $password) {
        $user = $this->userModel::findOne(['username' => $ldapUser["uid"]]);
        if($user) {
            return $this->updateUserFromLdapUser($ldapUser, $user, $password);
        }

        $registerForm= \Yii::createObject(RegistrationForm::className());
        $registerForm->email = $ldapUser[\Yii::$app->ldap->personMailAttribute];
        $registerForm->username = $ldapUser['uid'];
        $registerForm->password = $password;
        if (!$registerForm->validate()) {
            return false;
        }

        /** @var User $user */
        $user = Yii::createObject(User::className());
        $user->setScenario('register');
        $user->setAttributes($registerForm->attributes);
        $user->is_ldap_user = 1;

        if (!$user->register()) {
            return null;
        }

        $this->initRoles($user);
        $profile = $user->profile;
        $profile->name = isset($ldapUser['displayname']) ? $ldapUser['displayname'] : null;
        $profile->save();
        $user->save();
        // reload user object; otherwise roles relations are not set!
        $user = $this->userModel::findOne(['id' => $user->id]);

        return $user;
    }

    public function updateUserFromLdapUser($ldapUser, $localUser, $password) {
        $profile = $localUser->profile;
        $profile->name = isset($ldapUser['displayname']) ? $ldapUser['displayname']  : null;
        $localUser->email = $ldapUser[\Yii::$app->ldap->personMailAttribute];
        $localUser->username = $ldapUser['uid'];
        $localUser->password_hash = Password::hash($password);
        // $this->updateRolesFromLdapUser($ldapUser, $localUser);
        $localUser->save();
        $profile->save();
        return $localUser;
    }

    public function initRoles($localUser) {
        $roles = ["Assignment" => ["items" => ["viewer"]]]; // TODO get roles from ldapUser
        $assignment = Yii::createObject([
            'class'   => Assignment::className(),
            'user_id' => $localUser->id,
        ]);

        if ($assignment->load($roles)) {
            $assignment->updateAssignments();
        }
    }

    public function updateRolesFromLdapUser($ldapUser, $localUser) {
        $roles = []; // TODO get roles from ldapUser
        $assignment = Yii::createObject([
            'class'   => Assignment::className(),
            'user_id' => $localUser->id,
        ]);

        if ($assignment->load($roles)) {
            $assignment->updateAssignments();
        }
    }

    public function actionLogout () {
        $success = Yii::$app->user->identity->updateAttributes([
            'api_token' => null,
            'token_expire' => null,
        ]);
        Yii::$app->user->identity->save();
        return $success;
    }

    public function actionRefresh () {
        return $this->userModel::findOne(['id' => Yii::$app->user->identity->id]);
    }
}
