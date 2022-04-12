<?php

namespace app\models\core;

use app\behaviors\JsonFieldBehavior;
use Yii;

/**
 * This is the model class for table "widgets".
 *
 * @property int $id
 * @property string $type
 * @property string $title
 * @property string $subtitle
 * @property int $active
 * @property int $xs_size
 * @property int $sm_size
 * @property int $md_size
 * @property int $lg_size
 * @property int $order
 * @property string $extraSettings
 * @property string $color
 * @property int $is_dark
 * @property int $deletable
 * @property int $cloneable
 */
class Widget extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'widgets';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['active', 'xs_size', 'sm_size', 'md_size', 'lg_size', 'order', 'is_dark'], 'integer'],
            [['extraSettings'], 'validateIsArray'],
            [['type'], 'string', 'max' => 128],
            [['title', 'subtitle'], 'string', 'max' => 256],
            [['color'], 'string', 'max' => 50],
        ];
    }

    public function validateIsArray () {
        if(!is_array($this->extraSettings)){
            $this->addError('extraSettings','extraSettings is not array!');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'type' => 'Type',
            'title' => 'Title',
            'subtitle' => 'Subtitle',
            'active' => 'Active',
            'xs_size' => 'Xs Size',
            'sm_size' => 'Sm Size',
            'md_size' => 'Md Size',
            'lg_size' => 'Lg Size',
            'order' => 'Order',
            'extraSettings' => 'Extra Settings',
            'color' => 'Color',
            'is_dark' => 'Is Dark',
            'deletable' => 'Deletable',
            'cloneable' => 'Cloneable',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return array_merge(parent::behaviors(), [
            [
                'class' => JsonFieldBehavior::class,
                'fields' => ['extraSettings']
            ]
        ]);
    }

    public function afterFind()
    {
        parent::afterFind();
        if ($this->extraSettings == null) $this->extraSettings = json_decode('{}');
    }

    public function beforeSave($insert)
    {
        $valid =parent::beforeSave($insert);
        if ($valid && json_decode(substr($this->extraSettings, 0, 16777215)) == null) {
            $this->addError('extraSettings', 'Error serializing extra settings.');
            $valid = false;
        }
        return $valid;
    }
}
