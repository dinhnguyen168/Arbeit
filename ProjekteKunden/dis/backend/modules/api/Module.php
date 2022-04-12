<?php
/**
 * Created by PhpStorm.
 * User: alali
 * Date: 24.09.2018
 * Time: 11:51
 */

namespace app\modules\api;

use Yii;
use yii\base\BootstrapInterface;
use yii\base\Module as BaseModule;

class Module extends BaseModule implements BootstrapInterface
{
    public $controllerNamespace = 'app\modules\api\controllers';

    public $removeGenerators = [];

    public function bootstrap($app)
    {
        /**
         * Add the api version here.
         */
        $this->setModule('v1', [ 'class' => \app\modules\api\modules\v1\Module::class ]);

        /**
         * Add url rules for each api version
         */
        if ($app instanceof \yii\web\Application) {
            /* ---- File Controller ---- */
            $app->getUrlManager()->addRules([
                [
                    'class' => 'yii\rest\UrlRule',
                    'controller' => "api/v1/file",
                    'pluralize' => false,
                    'patterns' => [
                        'GET,HEAD' => 'index',
                        'GET update-select-values' => 'update-select-values',
                        'POST assign' => 'assign',
                        'POST delete' => 'delete',
                        'POST upload' => 'upload',
                        'GET meta-data' => 'meta-data',
                        'PUT unassign/<id:\d+>' => 'unassign',
                        '{id}' => 'options',
                        '' => 'options'

                    ]
                ]
            ], false);
            /* ---- Importer Controller ---- */
            $app->getUrlManager()->addRules([
                [
                    'class' => 'yii\rest\UrlRule',
                    'controller' => "api/v1/importer",
                    'pluralize' => false,
                    'patterns' => [
                        'GET,HEAD' => 'index',
                        '{id}' => 'options',
                        '' => 'options'
                    ]
                ]
            ], false);


            /* ---- Auth Controller ---- */
            $app->getUrlManager()->addRules([
                [
                    'class' => 'yii\rest\UrlRule',
                    'controller' => "api/v1/auth",
                    'pluralize' => false,
                    'patterns' => [
                        'POST login' => 'login',
                        'POST logout' => 'logout',
                        'GET refresh' => 'refresh',
                        '{id}' => 'options',
                        '' => 'options'
                    ]
                ]
            ], false);
            /* ---- App Controller ---- */
            $app->getUrlManager()->addRules([
                [
                    'class' => 'yii\rest\UrlRule',
                    'controller' => "api/v1/app",
                    'pluralize' => false,
                    'tokens' => [
                        '{id}' => '<id:\\d[\\d,]*>',
                        '{key}' => '<key:[a-z]+([\\.\\-]+[a-z]+)*>',
                        '{igsn}' => '<igsn:\\w+>'
                    ],
                    'patterns' => [
                        'GET forms' => 'forms',
                        'GET config' => 'get-app-config',
                        'GET config/{key}' => 'get-app-config',
                        'POST config' => 'save-app-config',
                        'GET user-config' => 'get-user-config',
                        'GET user-config/{key}' => 'get-user-config',
                        'POST user-config' => 'save-user-config',
                        'GET find-igsn/{igsn}' => 'find-igsn',
                        'GET get-messages' => 'get-messages',
                        'config' => 'options',
                        '' => 'options'
                    ]
                ]
            ], false);
            /* ---- Global (CRUD) Controller ---- */
            $app->getUrlManager()->addRules([
                [
                    'class' => 'yii\rest\UrlRule',
                    'controller' => "api/v1/global",
                    'pluralize' => false,
                    'patterns' => [
                        'PUT,PATCH {id}' => 'update',
                        'DELETE {id}' => 'delete',
                        'GET,HEAD {id}' => 'view',
                        'POST' => 'create',
                        'GET,HEAD' => 'index',
                        'GET harvest' => 'harvest',
                        'POST defaults' => 'defaults',
                        'POST duplicate' => 'duplicate',
                        'GET filter-lists' => 'filter-lists',
                        'GET async-lists' => 'async-lists',
                        'GET reports' => 'reports',
                        'GET print' => 'print',
                        '{id}' => 'options',
                        '' => 'options',
                    ]
                ]
            ], false);
            /* ---- ListValue Controller ---- */
            $app->getUrlManager()->addRules([
                [
                    'class' => 'yii\rest\UrlRule',
                    'controller' => "api/v1/list-values",
                    'pluralize' => false,
                    'patterns' => [
                        'PUT,PATCH {id}' => 'update',
                        'DELETE {id}' => 'delete',
                        'GET,HEAD {id}' => 'view',
                        'POST' => 'create',
                        'GET,HEAD' => 'index',
                        'GET list-names' => 'list-names',
                        'GET list' => 'list-info',
                        'PUT list' => 'update-list-info',
                        '{id}' => 'options',
                        '' => 'options',
                    ]
                ]
            ], false);
            /* ---- Form Controller ---- */
            $app->getUrlManager()->addRules([
                [
                    'class' => 'yii\rest\UrlRule',
                    'controller' => "api/v1/form",
                    'pluralize' => false,
                    'patterns' => [
                        'PUT,PATCH {id}' => 'update',
                        'DELETE {id}' => 'delete',
                        'GET,HEAD {id}' => 'view',
                        'POST' => 'create',
                        'GET,HEAD' => 'index',
                        'POST defaults' => 'defaults',
                        'POST duplicate' => 'duplicate',
                        'GET filter-lists' => 'filter-lists',
                        'GET reports' => 'reports',
                        'GET print' => 'print',
                        '{id}' => 'options',
                        '' => 'options',
                    ]
                ]
            ], false);
            /* ---- Widget Controller ---- */
            $app->getUrlManager()->addRules([
                [
                    'class' => 'yii\rest\UrlRule',
                    'controller' => "api/v1/widgets",
                    'pluralize' => false,
                    'extraPatterns' => [
                        'PUT,PATCH' => 'bulk-update',
                        'POST duplicate/{id}' => 'duplicate'
                    ]
                ]
            ], false);
            /* ---- Post Controller ---- */
            $app->getUrlManager()->addRules([
                [
                    'class' => 'yii\rest\UrlRule',
                    'controller' => "api/v1/posts",
                    'pluralize' => false
                ]
            ], false);
            /* ---- Account Controller ---- */
            $app->getUrlManager()->addRules([
                [
                    'class' => 'yii\rest\UrlRule',
                    'controller' => "api/v1/account",
                    'pluralize' => false,
                    'patterns' => [
                        'PUT' => 'update',
                        'POST change-password' => 'change-password',
                        'POST send-recovery-email' => 'send-recovery-email',
                        'POST check-recovery-link' => 'check-recovery-link',
                        'POST reset-password' => 'reset-password',
                        '{id}' => 'options',
                        '' => 'options',
                    ]
                ]
            ], false);
            /* ---- Messages Controller ---- */
            $app->getUrlManager()->addRules([
                [
                    'class' => 'yii\rest\UrlRule',
                    'controller' => "api/v1/messages",
                    'pluralize' => false
                ]
            ], false);
        }
    }

    public function afterAction($action, $result)
    {
        $user = Yii::$app->user;
        if ($user->identity) {
            $user->identity->extendTokenLifetime();
        }
        return parent::afterAction($action, $result);
    }
}
