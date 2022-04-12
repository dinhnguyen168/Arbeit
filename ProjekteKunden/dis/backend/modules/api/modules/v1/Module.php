<?php

namespace app\modules\api\modules\v1;

use yii\base\BootstrapInterface;
use yii\base\Module as BaseModule;

/**
 * Class Module
 * @package app\modules\api\modules\v1
 *
 * The first version of the api (url=v1) is identical to the common version. So this controller
 * only extends from the BaseModule.
 */
class Module extends BaseModule
{
    public $controllerNamespace = 'app\modules\api\modules\v1\controllers';
}