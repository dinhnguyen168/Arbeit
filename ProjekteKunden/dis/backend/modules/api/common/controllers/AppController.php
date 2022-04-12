<?php
/**
 * Created by PhpStorm.
 * User: alali
 * Date: 24.09.2018
 * Time: 10:17
 */

namespace app\modules\api\common\controllers;

use app\modules\api\common\actions\FindIgsnAction;
use app\modules\api\common\actions\GetMessagesAction;
use app\rest\IndexAction;
use yii\filters\auth\HttpBearerAuth;
use yii\rest\Controller;
use yii\filters\AccessControl;
use yii\rest\OptionsAction;
use yii\web\BadRequestHttpException;
use Yii;
use yii\helpers\Inflector;

class AppController extends Controller
{

    public function behaviors()
    {
        return array_merge(
            parent::behaviors(),
            [
                'authenticator' => [
                    'class' => HttpBearerAuth::class,
                    'optional' => ['get-app-config']
                ],
                'access' => [
                    'class' => AccessControl::class,
                    'rules' => [
                        [
                            'allow' => true,
                            'actions' => ['forms', 'save-app-config'],
                            'roles' => ['developer']
                        ],
                        [
                            'allow' => true,
                            'actions' => ['find-igsn'],
                            'roles' => ['viewer']
                        ],
                        [
                            'allow' => true,
                            'actions' => ['forms', 'get-user-config', 'save-user-config', 'get-messages'],
                            'roles' => ['@']
                        ],
                        [
                            'allow' => true,
                            'actions' => ['get-app-config']
                        ]
                    ]
                ]
            ]
        );
    }

    public function actions()
    {
        return array_merge(parent::actions(), [
            'options' => OptionsAction::class,
            'find-igsn' => FindIgsnAction::class,
            'get-messages' => GetMessagesAction::class
        ]);
    }

    /**
     * Get infos about available forms.
     * The infos are generated based on the existing vue files in /src/forms/
     * TODO: Only forms visible to the current user are returned.
     * @return array of form infos; each with attributes "key", "label", "Component"
     */
    public function actionForms()
    {
//        Yii::$app->templates->ge
        $user = \Yii::$app->user;
        $formInfos = [];
        $formsDirectory = realpath(\Yii::getAlias("@app/forms")) . "/";
        $canViewAllForms = $user->can("viewer");
        $isOperator = $user->can("operator");

        foreach (glob($formsDirectory . "*Form.php") as $formFile) {
            try {
                $formInfo = static::getFormInfo($formFile);
            }
            catch (\Throwable $e) {
                \Yii::error("AppController.actionForms() Cannot get formInfo for file " . basename($formFile) . ": " . $e->getMessage());
                continue;
            }
            if ($formInfo && $formInfo['module'] && ($canViewAllForms || $user->can("form-" . Inflector::camel2id($formInfo["key"]) . ':view'))) {
                $formInfos[$formInfo['module']][] = $formInfo;
            }
        }

        foreach ($formInfos as $module => &$forms) {
            usort($forms, function($a, $b) {
                if ($a["label"] == $b["label"]) {
                    return 0;
                }
                return ($a["label"] < $b["label"]) ? -1 : 1;
            });
        }
        ksort ($formInfos);

        return $formInfos;
    }

    public function actionGetAppConfig ($key = null) {
        if (!$key) {
            $config = \Yii::$app->config;
            return $config;
        } else {
            return [
                $key => Yii::$app->config[$key]
            ];
        }
    }

    public function actionSaveAppConfig () {
        $config = \Yii::$app->getRequest()->getBodyParams();
        foreach ($config as $key => $value) {
            if (!preg_match('/^[a-z]+([\\.\\-]+[a-z]+)*$/', $key)) {
                throw new BadRequestHttpException("'$key' is not a valid config key");
            } elseif ($value === null) {
                Yii::$app->config->offsetUnset($key);
            } else {
                Yii::$app->config[$key] = $value;
            }
        }
        Yii::$app->config->save();
        return Yii::$app->config;
    }

    public function actionGetUserConfig ($key = null) {
        $userConfig = Yii::$app->user->identity->getConfig();
        if (!$key) {
            // return $userConfig->attributes if you want to merge app config with user config
            return $userConfig;
        } else {
            return [
                $key => $userConfig[$key]
            ];
        }
    }

    public function actionSaveUserConfig () {
        $userConfig = Yii::$app->user->identity->getConfig();
        $config = \Yii::$app->getRequest()->getBodyParams();
        foreach ($config as $key => $value) {
            if (!preg_match('/^[a-z]+([\\.\\-]+[a-z]+)*$/', $key)) {
                throw new BadRequestHttpException("'$key' is not a valid config key");
            } elseif ($value === null) {
                $userConfig->offsetUnset($key);
            } else {
                $userConfig[$key] = $value;
            }
        }
        $userConfig->save();
        return $userConfig;
    }

    /**
     * Get form infos on the given vue file.
     * The content of the file is being analyzed to find the label and key of the form
     * Infos are being cached but updated if the vue file is newer than the cached version.
     * TODO: If current user may not see the form, null is returned
     * @param $formFile
     * @return array|null Array with form infos with attributes "key", "label", "Component"
     */
    public static function getFormInfo ($phpFormFile) {
        $cacheId = "FormInfo:" . $phpFormFile;
        $formInfo = \Yii::$app->cache->get($cacheId);
        $vueFormFile = realpath(\Yii::getAlias("@app/../src/forms")) . "/" . pathinfo($phpFormFile, PATHINFO_FILENAME) . ".vue";

        $modified = filemtime($phpFormFile);
        if (file_exists($vueFormFile)) $modified = max($modified, filemtime($vueFormFile));

        if (!is_array($formInfo) || $formInfo["modified"] < $modified) {
            $formInfo = [
              "key" => "",
              "label" => "",
              "Component" => baseName($vueFormFile),
              "modified" => time(),
              "dataModel" => "",
            ];

            $class = 'app\\forms\\' . pathinfo($phpFormFile, PATHINFO_FILENAME);
            $formInfo["key"] = constant($class . '::FORM_NAME');
            $formInfo["label"] = ucfirst($formInfo["key"]);
            $formInfo["dataModel"] = constant($class . '::MODULE') . constant($class . '::SHORT_NAME');
            $formInfo['module'] = constant($class . '::MODULE');

            if (file_exists($vueFormFile)) {
                $content = file_get_contents($vueFormFile, false,null, 0, 1000);
                $matches = [];
                if (preg_match ('/<h2>(.*?)<\/h2>/', $content,$matches)) {
                    $formInfo["label"] = $matches[1]; // Get from ucfirst(PHP-Form::FORM_NAME)
                }
            }
            \Yii::$app->cache->set($cacheId, $formInfo);
        }
        unset ($formInfo["modified"]);

        //TODO: check if current user may NOT see this form
        if ($formInfo["key"] == '' || false /* User may not see form */) {
            $formInfo = null;
        }
        return $formInfo;
    }

    protected static $currentExpedition = null;

    public static function getCurrentExpedition() {
        if (static::$currentExpedition == null) {
            $id = \Yii::$app->params['currentExpeditionId'];
            static::$currentExpedition = \app\models\ProjectExpedition::find()->where(['id' => $id])->one();
        }
        return static::$currentExpedition;
    }

    protected static $currentProgram = null;

    public static function getCurrentProgram() {
        if (static::$currentProgram == null) {
            $id = \Yii::$app->params['currentProgramId'];
            if (!($id > 0)) $id = static::getCurrentExpedition()->program_id;
            static::$currentProgram = \app\models\ProjectProgram::find()->where(['id' => $id])->one();
        }
        return static::$currentProgram;
    }

}
