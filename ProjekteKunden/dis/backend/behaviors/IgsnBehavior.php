<?php
namespace app\behaviors;

use yii\base\InvalidConfigException;
use yii\behaviors\AttributeBehavior;
use yii\db\BaseActiveRecord;

/**
 * Class IgsnBehavior
 *
 * Creates a unique igsn value for a record using the algorithm described in Wiki Article
 * "How to generate igsns (int geo sample nr) in legacy dis". Depending on the table type (Core, Section...), a
 * different "Object Tag" has to be used. The different Object Tags are described in the Wiki Article, or in the PHP
 * source code of the behavior (File backend/behaviors/IgsnBehavior.php).
 *
 * The behavior requires an object tag (one character) in a parameter "objectTag".
 *
 * This behavior does not have to be assigned manually: If a table has a column 'igsn' and the default value for
 * that column is a single letter (H, C, S, X, B, W, U, T, Y, Z, F), then the behavior will be automatically applied
 * and this letter will be used as the object tag for the IGSN algorithm.
 *
 * @package app\behaviors
 * @link https://www.yiiframework.com/doc/api/2.0/yii-behaviors-attributebehavior
 */
class IgsnBehavior extends AttributeBehavior
{
    /**
     * @var string IGSN number will be written into this column (if it is empty)
     */
    public $igsnField = 'igsn';

    /**
     * @var string One character out of the list $objectTags that is used in the calculation of the isgn number
     * Only required if method METHOD_ICDP_CLASSIC or METHOD_ICDP_2021 is used in component igsn.
     */
    public $objectTag = null;


    /**
     * {@inheritdoc}
     * @throws InvalidConfigException
     */
    public function init()
    {
        parent::init();
        if (empty($this->objectTag) && \Yii::$app->igsn->isObjectTagRequired()) {
            throw new InvalidConfigException('objectTag property is missing');
        }
        $this->attributes = [
            BaseActiveRecord::EVENT_AFTER_INSERT => $this->igsnField,
            BaseActiveRecord::EVENT_BEFORE_UPDATE => $this->igsnField
        ];
    }

    /**
     * {@inheritdoc}
     * @param \yii\base\Event $event
     */
    public function evaluateAttributes($event)
    {
        if (empty($this->owner->igsn) || strlen($this->owner->igsn) == 1) {
            parent::evaluateAttributes($event);
            if ($event->name == BaseActiveRecord::EVENT_AFTER_INSERT) {
                $owner = $this->owner;
                $oldBehavior = $owner->detachBehavior("igsn");
                $owner->save();
                if ($oldBehavior) $owner->attachBehavior("igsn", $oldBehavior);
            }
        }
        else if (strlen($this->owner->igsn) > 1)
            \Yii::$app->igsn->saveIgsn($this->owner->igsn, $this->owner);
    }

    /**
     * Calculates the value for the column $igsnField.

     * @param \yii\base\Event $event
     * @return string
     */
    protected function getValue($event)
    {
        // Moved calculation of IGSN numbers with different methods to component Igsn
        return \Yii::$app->igsn->createIgsn ($this->owner, $this->objectTag);
    }

}
