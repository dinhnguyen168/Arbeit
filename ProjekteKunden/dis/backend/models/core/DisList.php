<?php

namespace app\models\core;

use Yii;

/**
 * This is the model class for table "dis_list".
 *
 * @property int $id
 * @property string $list_name Name of list)
 * @property int $is_locked
 * @property string|null $list_uri special value uri
 *
 * @property DisListItem[] $disListItems
 */
class DisList extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'dis_list';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['list_name'], 'required'],
            [['is_locked'], 'boolean'],
            [['list_name'], 'string', 'max' => 50],
            [['list_uri'], 'string', 'max' => 255],
            [['list_name'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'list_name' => 'List Name',
            'is_locked' => 'Is Locked',
            'list_uri' => 'List Uri',
        ];
    }

    /**
     * Gets query for [[DisListItems]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getDisListItems()
    {
        return $this->hasMany(DisListItem::className(), ['list_id' => 'id']);
    }
}
