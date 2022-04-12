<?php

namespace app\models\core;

use Yii;

/**
 * This is the model class for table "dis_list_item".
 *
 * @property int $id
 * @property string $igsn IGSN number
 * @property string $class Class where the IGSN is used
 * @property int $recordId Id of the record in the class
 * @property boolean $registered Has the IGSN number been registered?
 */
class DisIgsn extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'dis_igsn';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        $rules = [
            [['igsn', 'model', 'model_id'], 'required'],
            [['igsn'], 'string'],
            [['model'], 'string'],
            [['model_id'], 'integer']
        ];

        $igsnMaxLength = \Yii::$app->igsn->maxLength;
        if ($igsnMaxLength) $rules[] = ['igsn', 'string', 'max' => $igsnMaxLength];
        return $rules;
    }

}
