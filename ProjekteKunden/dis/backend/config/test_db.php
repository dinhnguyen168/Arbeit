<?php
$db = require __DIR__ . '/db.php';
// test database! Important not to run tests on production or development databases
$hostName = env('DB_HOST') !== false ? env('DB_HOST') : env('MYSQL_HOST');
$driverName = env('DB_DRIVER_NAME');
if ($driverName !== false) {
    switch ($driverName) {
        case "mysql":
            $db['dsn'] = 'mysql:host=' . $hostName . ';dbname=dis_test';
            break;
        case "sqlsrv":
            $db['dsn'] = 'sqlsrv:Server=' . $hostName . ';Database=dis_test';
            break;
    }
} else {
    $db['dsn'] = 'mysql:host=' . $hostName . ';dbname=dis_test';
}

return $db;
