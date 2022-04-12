<?php

namespace app\models\core;

use Yii;

/**
 * This is the model class for table "image_of_the_day".
 *
 * @property int $id
 * @property int $message_id
 * @property int $image_id
 * @property string $caption
 *
 * @property MessageOfTheDay $message
 * @property ArchiveFile $image
 */
class ImageOfTheDay extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'image_of_the_day';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['message_id', 'image_id'], 'integer'],
            [['caption'], 'string'],
            [['message_id'], 'exist', 'skipOnError' => true, 'targetClass' => MessageOfTheDay::className(), 'targetAttribute' => ['message_id' => 'id']],
            [['image_id'], 'exist', 'skipOnError' => true, 'targetClass' => ArchiveFile::className(), 'targetAttribute' => ['image_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'message_id' => 'Message ID',
            'image_id' => 'Image ID',
            'caption' => 'Caption',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMessage()
    {
        return $this->hasOne(MessageOfTheDay::className(), ['id' => 'message_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImage()
    {
        return $this->hasOne(ArchiveFile::className(), ['id' => 'image_id']);
    }
}
