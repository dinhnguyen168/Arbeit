<?php


namespace app\modules\api\common\controllers\interfaces;


use app\components\templates\FormTemplate;
use app\components\templates\ModelTemplate;

interface ITemplatedFormActiveController
{
    /**
     * @return string class name of the tempalted class
     */
    public function getDataFormClassName(): string;

    /**
     * @return string namespace of the templated class
     */
    public function getDataFormNameSpace(): string;

    /**
     * @return FormTemplate
     */
    public function getDataFormTemplate(): FormTemplate;

    /**
     * @return ModelTemplate
     */
    public function getFormDataModelTemplate(): ModelTemplate;
}