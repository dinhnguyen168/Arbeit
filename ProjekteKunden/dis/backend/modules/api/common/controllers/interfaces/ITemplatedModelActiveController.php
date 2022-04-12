<?php


namespace app\modules\api\common\controllers\interfaces;

use app\components\templates\ModelTemplate;

interface ITemplatedModelActiveController
{
    /**
     * @return string class name of the tempalted class
     */
    public function getDataModelClassName(): string;

    /**
     * @return string namespace of the templated class
     */
    public function getDataModelNameSpace(): string;

    /**
     * @return ModelTemplate
     */
    public function getDataModelTemplate(): ModelTemplate;

}