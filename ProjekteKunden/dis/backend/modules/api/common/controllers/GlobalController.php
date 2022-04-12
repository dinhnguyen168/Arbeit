<?php
/**
 * Created by PhpStorm.
 * User: alali
 * Date: 24.09.2018
 * Time: 10:17
 */

namespace app\modules\api\common\controllers;

use app\components\templates\ModelTemplate;
use app\modules\api\common\controllers\base\TemplatedClassActiveController;
use app\modules\api\common\controllers\interfaces\ITemplatedModelActiveController;
use Yii;

class GlobalController extends TemplatedClassActiveController implements ITemplatedModelActiveController
{
    public function getDataModelClassName(): string
    {
        return $this->getName();
    }

    public function getDataModelNameSpace(): string
    {
        return Yii::$app->params['modelsClassesNs'];
    }

    public function getDataModelTemplate(): ModelTemplate
    {
        return Yii::$app->templates->getModelTemplate($this->getName());
    }
}