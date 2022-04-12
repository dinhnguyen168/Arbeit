<?php
namespace app\behaviors;

use yii\base\InvalidConfigException;
use yii\base\Behavior;
use yii\db\BaseActiveRecord;

/**
 * Class CurationSectionSplitCoreboxBehavior
 *
 * Removes the corebox_id, corebox, corebox_slot, corebox_position when value of still_exists is changed to false.
 *
 * @package app\behaviors
 * @link https://www.yiiframework.com/doc/api/2.0/yii-behaviors-attributebehavior
 */
class CurationSectionSplitCoreboxBehavior extends Behavior
{

  /**
     * @var string Column to which the calculated ID will be assigned to
     */
    public $coreboxColumns = ['corebox_id', 'corebox', 'corebox_slot', 'corebox_position'];
    public $stillExistsColumn = 'still_exists';

    /**
     * {@inheritdoc}
     */
    public function events()
    {
        return [
            BaseActiveRecord::EVENT_BEFORE_UPDATE  => 'updateCoreboxColumns',
        ];
    }

    /**
     * {@inheritdoc}
     * Sets the columns "corebox_id", "corebox", etc. to null, if still_exists is false
     *
     * @param \yii\base\Event $event
     * @return string Calculated combined ID based on the parent record
     */
    public function updateCoreboxColumns($event)
    {
        if (in_array($this->stillExistsColumn, array_keys($this->owner->dirtyAttributes)) && $this->owner->{$this->stillExistsColumn} == false) {
            foreach ($this->coreboxColumns as $column) {
                $this->owner->{$column} = null;
            }
        }
    }
}
