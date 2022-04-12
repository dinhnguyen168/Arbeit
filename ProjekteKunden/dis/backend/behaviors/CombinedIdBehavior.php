<?php
namespace app\behaviors;

use yii\base\InvalidConfigException;
use yii\behaviors\AttributeBehavior;
use yii\db\BaseActiveRecord;

/**
 * Class CombinedIdBehavior
 *
 * Creates a readable ID hierarchy like it was used before in the legacy DIS tables. Every time a record
 * is inserted or updated, the value of the assigned column (usually "combined_id") is being updated.
 * This behavior does not have to be assigned manually, it is invoked automatically as soon as there exists a
 * column "combined_id" in the mysql datatable.
 *
 * @package app\behaviors
 * @link https://www.yiiframework.com/doc/api/2.0/yii-behaviors-attributebehavior
 */
class CombinedIdBehavior extends AttributeBehavior
{

  /**
    * @event Event An event that is triggered to recalculate the combined id.
    */
    const EVENT_RECALCULATE = 'recalculate_combined_id';

    /**
     * @var string Column to which the calculated ID will be assigned to
     */
    public $combinedIdField = 'combined_id';


    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();
        $this->attributes = [
            BaseActiveRecord::EVENT_BEFORE_INSERT => $this->combinedIdField,
            BaseActiveRecord::EVENT_BEFORE_UPDATE => $this->combinedIdField,
            static::EVENT_RECALCULATE => $this->combinedIdField
        ];
    }

    /**
     * {@inheritdoc}
     * Calculates the value for the column $combinedIdField.
     * If the column name is "combined_id", the own ID of the record, found in column with name NAME_ATTRIBUTE will
     * be added.
     *
     * @param \yii\base\Event $event
     * @return string Calculated combined ID based on the parent record
     */
    protected function getValue($event)
    {
        $combinedId = $this->owner->calculateCombinedId ($this->combinedIdField);
        return $combinedId;
    }
}
