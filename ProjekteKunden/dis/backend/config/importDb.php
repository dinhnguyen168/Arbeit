<?php

return [
    'class' => 'yii\db\Connection',
    'dsn' => 'sqlsrv:server=192.168.0.140;database=DISSQL_EXP_DSEIS',
    'username' => 'sa',
    'password' => 'icdp',
    'charset' => 'latin1',

    // Schema cache options (for production environment)
    //'enableSchemaCache' => true,
    //'schemaCacheDuration' => 60,
    //'schemaCache' => 'cache',
];


/**
 * Treiber von Microsoft herunterladen:
 * https://github.com/Microsoft/msphpsql/releases/tag/v5.3.0
 * Je nach Apache Server die passenden SO-Dateien in das Extension-Verzeichnis (siehe phpinfo) kopieren.
 *
 * Zusätzlich mussten die MS-ODBC-Treiber installiert werden:
 * https://docs.microsoft.com/de-de/sql/connect/odbc/linux-mac/installing-the-microsoft-odbc-driver-for-sql-server?view=sql-server-2017
 *
 * Auf Ubuntu wurden zusätzlich die folgenden Pakete benötigt:
 * apt-get install libodbc1 odbcinst1debian2
 */