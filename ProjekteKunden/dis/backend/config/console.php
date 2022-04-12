<?php

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';
$importDb = require __DIR__ . '/importDb.php';

$igsn = [ 'class' => 'app\components\Igsn'];
if (file_exists( __DIR__ . '/igsn.php')) {
    $igsn = require  __DIR__ . '/igsn.php';
}

$config = [
    'id' => 'basic-console',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'controllerNamespace' => 'app\commands',
    'controllerMap' => [
        'migrate' => [
            'class' => 'yii\console\controllers\MigrateController',
            'migrationPath' => [
                '@app/migrations',
                '@yii/rbac/migrations'
            ],
            'migrationNamespaces' => [
                'Da\User\Migration',
            ],
        ],
        'dis-migrate' => [
            'class' => 'app\commands\DisMigrateController',
        ]
    ],
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
        '@tests' => '@app/tests',
    ],
    'modules' => [
        'user' => [
            'class' => 'Da\User\Module',
            'classMap' => [
                'User' => 'app\models\core\User',
            ],
        ],
        // 'rbac' => 'dektrium\rbac\RbacConsoleModule',
    ],
    'components' => [
        'authManager' => [
            'class' => 'yii\rbac\DbManager',
        ],
        'config' => [
            'class' => 'ancor\relatedKvStorage\Config',
            'tableName' => '{{%app_config}}'
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'templates' => [
            'class' => 'app\components\templates\Component'
        ],
        'mailer' => [
            'class' => 'yii\swiftmailer\Mailer',
            // send all mails to a file by default. You have to set
            // 'useFileTransport' to false and configure a transport
            // for the mailer to send real emails.
            'useFileTransport' => true,
        ],
        'log' => [
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'db' => $db,
        'importDb' => $importDb,
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
    /*
    'controllerMap' => [
        'fixture' => [ // Fixture generation command line.
            'class' => 'yii\faker\FixtureController',
        ],
    ],
    */
];

// Add cg module command
$config['bootstrap'][] = 'cg';
$config['modules']['cg'] = [
    'class' => 'app\modules\cg\Module',
    'removeGenerators' => ['crud', 'extension', 'module', 'form', 'controller', 'model'],
    'generators' => [
        'data-model' => [
            'class' => 'app\modules\cg\generators\DISModel\Generator',
        ],
        'dis-form' => [
            'class' => 'app\modules\cg\generators\DISForm\Generator',
        ]
    ]
];

return $config;
