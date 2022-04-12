<?php
/**
 * Created by PhpStorm.
 * User: alali
 * Date: 23.05.2019
 * Time: 09:58
 */

namespace app\components\validators;

use Yii;
use yii\validators\Validator;

class MultipleValuesStringValidator extends Validator
{
    const SEPARATOR = ';';
    public $skipOnEmpty = false;

    public function validateAttribute($model, $attribute)
    {
        if (!empty($model->$attribute) || $model->$attribute == []) {
            if (is_array($model->$attribute)) {
                $model->$attribute = join(self::SEPARATOR, $model->$attribute);
            }
        }
    }
}
