<?php
$httpSchema = isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] === "on" ? "https" : "http";
$httpHost = isset($_SERVER["HTTP_HOST"]) ? $_SERVER["HTTP_HOST"] : "";
$baseUrl = ($httpSchema && $httpHost) ? $httpSchema . "://". $httpHost : "";

return [
    // Mails are sent with this sender address
    'adminEmail' => 'admin@example.com',

    // Namespaces and directories (should not be changed)
    'modelsBaseClassesNs' => 'app\\models\\base',
    'modelsClassesNs' => 'app\\models',
    'formsClassesNs' => 'app\\forms',
    'modelsBaseClassesPath' => __DIR__ . '/../models/base',
    'modelsClassesPath' => __DIR__ . '/../models',
    'formsClassesPath' => __DIR__ . '/../forms',

    // Default expedition and program for reports if the cannot be determined by the parent hierarchie
    'currentExpeditionId' => 1,
    'currentProgramId' => null, /* Determined by currentExpeditionId */

    // array of intranet ips. e.g. ip range ['172.23.0.0,255.255.255.0'] or single ips ['192.168.0.1', '192.168.0.2']. true is default
    'intranetIPs' => true,

    // only for auto print or direct print of reports generating a pdf
    'printers' => [
        // 'default' => <printername> or <printername>@<host> or <printername>@<host>:<port> or @<host>...
        // <reportName, i.e. 'CoreQrCodes'> => <printername> (or see above), i.e. 'CoreQrCodes' => '@192.168.0.246'
    ],

    // Base HTTP address can be overwritten in .env file
    'baseUrl' => env('BASE_URL') ? env('BASE_URL') : $baseUrl,

    // Validate that name attributes have no underscore (because of combined id)
    'validateNoUnderscoresInNameAttribute' => true,
    'uuidNameSpace' => \Ramsey\Uuid\Uuid::uuid5(\Ramsey\Uuid\Uuid::NIL, env('BASE_URL') ? env('BASE_URL') : $baseUrl),

    // ListAction params
    'maxAllowedSetOfRecordsPerRequest' => 2000,
];
