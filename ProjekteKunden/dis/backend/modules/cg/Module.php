<?php
/**
 * Created by PhpStorm.
 * User: alali
 * Date: 24.09.2018
 * Time: 11:51
 */

namespace app\modules\cg;

use app\modules\cg\console\GenerateController;
use Yii;
use yii\gii\Module as BaseGii;

class Module extends BaseGii
{
    public $controllerNamespace = 'app\modules\cg\controllers';

    public $removeGenerators = [];

    public function bootstrap($app)
    {
        if ($app instanceof \yii\web\Application) {
            $app->getUrlManager()->addRules([
                ['class' => 'yii\web\UrlRule', 'pattern' => $this->id, 'route' => $this->id . '/default/index'],
                ['class' => 'yii\web\UrlRule', 'pattern' => $this->id . '/<id:\w+>', 'route' => $this->id . '/default/view'],
                ['class' => 'yii\web\UrlRule', 'pattern' => $this->id . '/<controller:[\w\-]+>/<action:[\w\-]+>', 'route' => $this->id . '/<controller>/<action>'],
            ], false);
        } elseif ($app instanceof \yii\console\Application) {
            $app->controllerMap[$this->id] = [
                'class' => GenerateController::class,
                'generators' => array_merge($this->coreGenerators(), $this->generators),
                'module' => $this,
            ];
        }
    }

    /**
     * @inheritdoc
     */
    protected function coreGenerators()
    {
        $coreGenerators = parent::coreGenerators();
        foreach ($this->removeGenerators as $id) {
            unset($coreGenerators[$id]);
        }
        return $coreGenerators;
    }


    protected function checkAccess()
    {
        $controller =Yii::$app->controller->id;
        if (Yii::$app->controller->id === 'default') {
            return Yii::$app->user->can('developer');
        }
        return true;
    }

    public function afterAction($action, $result)
    {
        if (Yii::$app instanceof \yii\web\Application) {
            $user = Yii::$app->user;
            if ($user->identity) {
                $user->identity->extendTokenLifetime();
            }
        }
        return parent::afterAction($action, $result);
    }
}