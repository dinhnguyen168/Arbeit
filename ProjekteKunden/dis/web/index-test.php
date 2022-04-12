<?php
defined('DOTENV_PATH') or define('DOTENV_PATH', __DIR__ . DIRECTORY_SEPARATOR . '..');
// NOTE: Make sure this file is not accessible when deployed to production
if (!in_array(@$_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1'])) {
    die('You are not allowed to access this file.');
}

defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'test');

require __DIR__ . '/../backend/vendor/autoload.php';
require __DIR__ . '/../backend/vendor/codeception/codeception/autoload.php';
require __DIR__ . '/../backend/vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/../backend/config/test.php';

include __DIR__ . '/../c3.php';

(new yii\web\Application($config))->run();
