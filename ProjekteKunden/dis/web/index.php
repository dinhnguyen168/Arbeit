<?php
defined('DOTENV_PATH') or define('DOTENV_PATH', __DIR__ . DIRECTORY_SEPARATOR . '..');
// comment out the following two lines when deployed to production
require __DIR__ . '/../backend/vendor/autoload.php';

defined('YII_DEBUG') or define('YII_DEBUG', env('YII_DEBUG'));
defined('YII_ENV') or define('YII_ENV', env('YII_ENV'));

require __DIR__ . '/../backend/vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/../backend/config/web.php';

(new yii\web\Application($config))->run();
