<?php
/**
 * Created by PhpStorm.
 * User: alali
 * Date: 16.10.2018
 * Time: 12:24
 */

namespace app\modules\api\common\actions;

use app\modules\api\common\controllers\interfaces\ITemplatedFormActiveController;
use app\modules\api\common\controllers\interfaces\ITemplatedModelActiveController;
use Yii;
use app\models\core\Base;
use yii\base\InvalidConfigException;
use yii\base\Model;
use yii\web\BadRequestHttpException;

class DefaultsAction extends \yii\rest\Action
{
    /**
     * trigger the defaults event and returns a model with it's defaults values
     * @return Model
     * @throws \yii\base\InvalidConfigException
     * @throws BadRequestHttpException
     */
    public function run()
    {
        if ($this->checkAccess) {
            call_user_func($this->checkAccess, $this->id);
        }

        $modelClass = $this->modelClass;
        /* @var $model Model */
        $model = new $modelClass();
        $controller = $this->controller;
        if ($controller instanceof ITemplatedModelActiveController) {
            $requiredFilters = $controller->getDataModelTemplate()->getRequiredFilters();
        }
        if ($controller instanceof ITemplatedFormActiveController) {
            $requiredFilters = $controller->getFormDataModelTemplate()->getRequiredFilters();
        }
        if (count($requiredFilters) > 1) {
            throw new InvalidConfigException('A model should not have more than one required filter');
        }
        $requiredFilter = null;
        if (count($requiredFilters) === 1 && !$requiredFilters[0]["skipOnEmpty"]) {
            $requiredFilter = $requiredFilters[0]["value"];
        }
        $model->load(Yii::$app->getRequest()->getBodyParams(), '');
        // check if required filter is loaded
        if ($requiredFilter !== null && $model->{$requiredFilter} === null) {
            throw new BadRequestHttpException("required parameter $requiredFilter is not sent.");
        }
        // trigger defaults behaviors
        $model->trigger(Base::EVENT_DEFAULTS);
        return $model;
    }
}
