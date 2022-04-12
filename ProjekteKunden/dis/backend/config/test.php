<?php
$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/test_db.php';

/**
 * Application configuration shared by all test types
 */
return [
    'id' => 'basic-tests',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['api', 'cg'],
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
        '@tests' => '@app/tests'
    ],
    'language' => 'en-US',
    'modules' => [
        'user' => [
            'class' => 'Da\User\Module',
            'enableFlashMessages' => false,
            'enableRegistration' => false,
            'allowPasswordRecovery' => true,
            'administrators' => ['administrator'],
            'administratorPermissionName' => 'sa',
            'classMap' => [
                'User' => 'app\models\User',
            ],
        ],
        'api' => [
            'class' => 'app\modules\api\Module',
        ],
        'cg' => [
            'class' => 'app\modules\cg\Module',
            'removeGenerators' => ['crud', 'extension', 'module', 'form', 'controller', 'model'],
            // uncomment the following to add your IP if you are not connecting from localhost.
            'allowedIPs' => ['127.0.0.1', '::1', env('EXTERNAL_ALLOWED_IP')],
            'generators' => [
                'dis-model' => [
                    'class' => 'app\modules\cg\generators\DISModel\Generator',
                ],
                'dis-form' => [
                    'class' => 'app\modules\cg\generators\DISForm\Generator',
                ]
            ]
        ]
    ],
    'components' => [
        'db' => $db,
        'mailer' => [
            'useFileTransport' => true,
        ],
        'assetManager' => [
            'basePath' => __DIR__ . '/../../web/assets',
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'enableStrictParsing' => true,
            'showScriptName' => false,
            'rules' => [
                '/' => 'site/index',
                '/test' => 'site/test',
                '/site/error' => 'site/error',
                '<module:(rbac)>/<controller>/<action>' => 'rbac/<controller>/<action>',
                '<module:(user)>/<controller>/<action>' => 'user/<controller>/<action>',
                '<controller:(report)>/<reportName:[\w-]+>' => 'report/generate',
                '<controller:(files)>/<id:\d+>' => 'files/view',
                '<controller:(files)>/original/<id:\d+>' => 'files/view-original',
                '<controller:(files)>/<action>' => 'files/<action>',
                '<controller:(importer)>/<action>' => 'importer/<action>',
                '<controller:(terminal)>/<action>' => 'terminal/<action>',
            ],
        ],
        'user' => [
            'identityClass' => 'app\models\core\User',
        ],
        'request' => [
            'cookieValidationKey' => 'test',
            'enableCsrfValidation' => false,
            'parsers' => [
                'application/json' => 'yii\web\JsonParser',
            ]
        ],
        'templates' => [
            'class' => 'app\components\templates\Component'
        ],
        'config' => [
            'class' => 'ancor\relatedKvStorage\Config',
            'tableName' => '{{%app_config}}'
        ]
    ],
    'params' => $params,
];
