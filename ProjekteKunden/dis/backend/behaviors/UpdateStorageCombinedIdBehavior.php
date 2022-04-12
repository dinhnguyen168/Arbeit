<?php
namespace app\behaviors;

use yii\base\InvalidConfigException;
use yii\behaviors\AttributeBehavior;
use yii\db\BaseActiveRecord;

/**
 * Class UpdateStorageCombinedIdBehavior
 *
 * Updates the curation_storage.combined_id of descendant records if the combined_id is changed.
 *
 * @package app\behaviors
 * @link https://www.yiiframework.com/doc/api/2.0/yii-behaviors-attributebehavior
 */
class UpdateStorageCombinedIdBehavior extends AttributeBehavior
{

    const ATTRIBUTE_NAME = "combined_id";

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();
        $this->attributes = [
            BaseActiveRecord::EVENT_AFTER_UPDATE => self::ATTRIBUTE_NAME
        ];
    }

    /**
     * {@inheritdoc}
     * Replaces the changed part of the combined_id in all descendant records.
     *
     * @param \yii\base\Event $event
     * @return string Unchanged combined_id
     */
    protected function getValue($event)
    {
        $value = $this->owner->{self::ATTRIBUTE_NAME};
        if (isset($event->changedAttributes[self::ATTRIBUTE_NAME])) {
            $searchValue = $event->changedAttributes[self::ATTRIBUTE_NAME] . "_";
            $replaceValue = $value . "_";
            \Yii::$app->db->createCommand("UPDATE " . $this->owner->tableName() . " SET " . self::ATTRIBUTE_NAME . " = REPLACE(" . self::ATTRIBUTE_NAME . ", :search, :replace) WHERE " . self::ATTRIBUTE_NAME . " LIKE :like")
                ->bindValue(':search', $searchValue)
                ->bindValue(':replace', $replaceValue)
                ->bindValue(':like', $searchValue . '%')
                ->execute();

        }
        return $value;
    }
}
