<?php

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';
$importDb = require __DIR__ . '/importDb.php';

$ldap = null;
if (file_exists( __DIR__ . '/ldap.php')) {
    $ldap = require  __DIR__ . '/ldap.php';
}

$igsn = [ 'class' => 'app\components\Igsn'];
if (file_exists( __DIR__ . '/igsn.php')) {
    $igsn = require  __DIR__ . '/igsn.php';
}


$config = [
    'id' => 'basic',
    'name' => 'mDIS',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log', 'api', 'cg'],
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'modules' => [
        'user' => [
            'class' => 'Da\User\Module',
            'enableFlashMessages' => false,
            'enableRegistration' => false,
            'enableEmailConfirmation' => false,
            'allowPasswordRecovery' => true,
            'administrators' => ['administrator'],
            'administratorPermissionName' => 'sa',
            'classMap' => [
                'User' => 'app\models\core\User',
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
        'config' => [
            'class' => 'ancor\relatedKvStorage\Config',
            'tableName' => '{{%app_config}}'
        ],
        'templates' => [
            'class' => 'app\components\templates\Component'
        ],
        'request' => [
            // !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
            'class' => '\app\components\web\Request',
            'cookieValidationKey' => 'J1eiJi3Q9muDL37UYz-Yn_GZhqc11lZH',
            'parsers' => [
                'application/json' => 'yii\web\JsonParser',
            ]
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'mailer' => [
            'class' => 'yii\swiftmailer\Mailer',
            // send all mails to a file by default. You have to set
            // 'useFileTransport' to false and configure a transport
            // for the mailer to send real emails.
            'useFileTransport' => true,
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'flushInterval' => 1,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                    'logVars' => ['_REQUEST']
                ],
                [
                    'class' => 'yii\log\FileTarget',
                    'categories' => ['convert'],
                    'levels' => ['info', 'error', 'warning'],
                    'logFile' => '@runtime/logs/convert.log',
                    'logVars' => [],
                ],
            ],
        ],
        'db' => $db,
        'importDb' => $importDb,
        'urlManager' => [
            'enablePrettyUrl' => true,
            'enableStrictParsing' => true,
            'showScriptName' => false,
            'rules' => [
                '/' => 'site/index',
                '/test' => 'site/test',
                '/site/<action>' => 'site/<action>',
                '<module:(rbac)>/<controller>/<action>' => 'rbac/<controller>/<action>',
                '<module:(user)>/<controller>/<action>' => 'user/<controller>/<action>',
                '/user/recover/<id:\d+>/<code:[A-Za-z0-9_-]+>' => '/user/recovery/reset',
                '<controller:(report)>/<reportName:[\w-]+>' => 'report/generate',
                '<controller:(files)>/<id:\d+>' => 'files/view',
                '<controller:(files)>/original/<id:\d+>' => 'files/view-original',
                '<controller:(files)>/converted/<id:\d+>' => 'files/view-converted',
                '<controller:(files)>/<action>' => 'files/<action>',
                '<controller:(importer)>/<action>' => 'importer/<action>',
                '<controller:(terminal)>/<action>' => 'terminal/<action>',
            ],
        ],
        'ldap' => $ldap,
        'igsn' => $igsn,
        'view' => [
            'theme' => [
                'pathMap' => [
                    '@Da/User/resources/views' => '@app/views/user'
                ]
            ]
        ]
    ],
    'params' => $params,
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        'allowedIPs' => ['127.0.0.1', '::1', env('EXTERNAL_ALLOWED_IP')],
    ];
}

return $config;
