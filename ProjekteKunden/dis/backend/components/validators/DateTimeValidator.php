<?php
/**
 * Created by PhpStorm.
 * User: reckert
 * Date: 26.03.2019
 * Time: 13:35
 */

namespace app\components\validators;

/**
 * Class DateValidator
 * Some browsers deliver datetimes without the seconds part. To save date values in SQL, the seconds part is required.
 * This validator adds a missing seconds part to the value before applying the usual yii date validation.
 * @package app\components\validators
 */
class DateTimeValidator extends \yii\validators\DateValidator
{
    /**
     * @var string For the SQL database we require this format
     */
    public $format = 'yyyy-MM-dd HH:mm:ss';


    /**
     * Add missing seconds part
     * @param $model
     * @param $attribute
     */
    public function validateAttribute($model, $attribute)
    {
        $value = $model->$attribute;
        if (preg_match('/ \d\d:\d\d$/', $value)) {
            $model->$attribute .= ':00';
        }
        parent::validateAttribute($model, $attribute);
    }

}