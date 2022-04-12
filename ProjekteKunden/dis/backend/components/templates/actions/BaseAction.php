<?php
/**
 * Created by PhpStorm.
 * User: alali
 * Date: 28.01.2019
 * Time: 13:23
 */

namespace app\components\templates\actions;


use yii\base\Action;
use yii\base\InvalidConfigException;
use app\components\templates\BaseTemplate;
use yii\web\NotFoundHttpException;

class BaseAction extends Action
{
    /**
     * @var string class name of the model which will be handled by this action.
     * The model class must extend [[BaseTemplate]].
     * This property must be set.
     */
    public $templateClass;

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        if ($this->templateClass === null) {
            throw new InvalidConfigException(get_class($this) . '::$templateModelClass must be set.');
        }
    }

    /**
     * Returns the data model based on the name given.
     * If the data model is not found, a 404 HTTP exception will be raised.
     * @param string $name the name of the template file to be loaded
     * @return BaseTemplate the model found
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function findTemplate($name)
    {
        /* @var $modelClass BaseTemplate */
        $modelClass = $this->templateClass;
        $model = $modelClass::find($name);

        if (isset($model)) {
            return $model;
        }

        throw new NotFoundHttpException("Object not found: $name");
    }
}