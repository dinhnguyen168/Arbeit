<?php

namespace app\models\core;

use Yii;

/**
 * This is the model class for table "dis_list_item".
 *
 * @property int $id
 * @property int $list_id
 * @property string $display Value to be shown
 * @property string|null $remark Additionally show information
 * @property string|null $uri special value uri
 * @property int|null $sort Optional: Order of values in the list; otherwise sorted by display
 *
 * @property DisList $list
 */
class DisListItem extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'dis_list_item';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['list_id', 'display'], 'required'],
            [['list_id', 'sort'], 'integer'],
            [['display'], 'string', 'max' => 100],
            [['remark', 'uri'], 'string', 'max' => 255],
            [['list_id', 'display'], 'unique', 'targetAttribute' => ['list_id', 'display']],
            [['list_id'], 'exist', 'skipOnError' => true, 'targetClass' => DisList::className(), 'targetAttribute' => ['list_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'list_id' => 'List ID',
            'display' => 'Display',
            'remark' => 'Remark',
            'uri' => 'Uri',
            'sort' => 'Sort',
        ];
    }

    public function load($data, $formName = null)
    {
        $loaded = parent::load($data, $formName);
        if ($loaded) {
            // value could be sent as number
            $this->display = strval($this->display);
        }
        return $loaded;
    }


    /**
     * Gets query for [[List]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getList()
    {
        return $this->hasOne(DisList::className(), ['id' => 'list_id']);
    }
}
