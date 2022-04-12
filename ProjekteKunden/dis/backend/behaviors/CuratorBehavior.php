<?php
namespace app\behaviors;

use yii\base\InvalidConfigException;
use yii\behaviors\AttributeBehavior;
use yii\db\BaseActiveRecord;

/**
 * Class AnalystBehavior
 *
 * Inserts the user name of the currently logged in user in the analyst field on save and update if it is empty.
 * This behavior does not have to be assigned manually, it is invoked automatically as soon as there exists a
 * column "analyst" in the mysql datatable.
 *
 * @package app\behaviors
 * @link https://www.yiiframework.com/doc/api/2.0/yii-behaviors-attributebehavior
 */
class CuratorBehavior extends AttributeBehavior
{
    /**
     * @var string Column to which the username will be assigned to
     */
    public $triggerField = 'curator';

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();
        $this->attributes = [
            BaseActiveRecord::EVENT_BEFORE_INSERT => $this->triggerField,
            /** BaseActiveRecord::EVENT_BEFORE_UPDATE => $this->triggerField **/
        ];
    }

    /**
     * {@inheritdoc}
     * Fetches the value for the triggerField
     *
     * @param \yii\base\Event $event
     * @return string username from mDIS login
     */
    protected function getValue($event)
    {
        $value = \Yii::$app->user->identity->id;
        return $value;
    }
}