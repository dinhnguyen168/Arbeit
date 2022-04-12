<?php
$dsn = null;
$driverName = env('DB_DRIVER_NAME');
if ($driverName !== false) {
    switch ($driverName) {
        case "mysql":
            $dsn = 'mysql:host=' . env('DB_HOST') . ';dbname=' . env('DB_DATABASE');
            break;
        case "sqlsrv":
            $dsn = 'sqlsrv:Server=' . env('DB_HOST') . ';Database=' . env('DB_DATABASE');
            break;
    }
} else {
    $driverName = 'mysql';
    $dsn = 'mysql:host=' . env('MYSQL_HOST') . ';dbname=' . env('MYSQL_DATABASE');
}


return  [
    'class' => 'yii\db\Connection',
    'username' => env('DB_USER') !== false ? env('DB_USER') : env('MYSQL_USER'),
    'password' => env('DB_PASSWORD') !== false ? env('DB_PASSWORD') : env('MYSQL_PASSWORD') ,
    'charset' => 'utf8',
    'driverName' => $driverName,
    'dsn' => $dsn,

    // Schema cache options (for production environment)
    'enableSchemaCache' => !YII_ENV_DEV,
    'enableQueryCache' => !YII_ENV_DEV,
    'schemaCacheDuration' => 30,
    'queryCacheDuration' => 30,

    'schemaMap' => [
        'pgsql' => 'yii\db\pgsql\Schema',
        'mysqli' => 'yii\db\mysql\Schema',
        'mysql' => 'yii\db\mysql\Schema',
        'sqlite' => 'yii\db\sqlite\Schema',
        'sqlite2' => 'yii\db\sqlite\Schema',
        'sqlsrv' => 'app\components\schema\mssql\Schema',
        'oci' => 'yii\db\oci\Schema',
        'mssql' => 'yii\db\mssql\Schema',
        'dblib' => 'yii\db\mssql\Schema',
        'cubrid' => 'yii\db\cubrid\Schema',
    ]
];
