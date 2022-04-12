<?php
namespace app\behaviors;

use yii\base\InvalidConfigException;
use yii\behaviors\AttributeBehavior;
use yii\db\BaseActiveRecord;
use app\models\core\Base;

/**
 * Class UniqueCombinationAutoIncrementBehavior
 * Fills a 'local' auto increment column with a unique value. This is not a real auto increment column, since nonunique
 * values may occur. It is usually used to create unique values for records with the same parent record, like the
 * column "section" in table "core_section".
 *
 * You have to provide the parameter "fieldToFill", which tells the behavior the column to be filled by the calculated
 * value. The second parameter "searchFields" is an array of the columns in the record, that build the group in which a unique value
should be created.
 *
 *
 * @package app\behaviors
 * @link https://www.yiiframework.com/doc/api/2.0/yii-behaviors-attributebehavior
 * @deprecated use template behavior instead
 */
class UniqueCombinationAutoIncrementBehavior extends AttributeBehavior
{
    /**
     * @var array Array of the columns in the record, that build the group in which a unique value should be created,
     * like ["core_id"] for table "core_section".
     */
    public $searchFields = [];

    /**
     * @var string Column to be filled by the calculated value, i.e. "section" in table "core_section".
     */
    public $fieldToFill = '';

    /**
     * {@inheritdoc}
     * @throws InvalidConfigException
     */
    public function init()
    {
        if (empty($this->searchFields) || empty($this->fieldToFill)) {
            throw new InvalidConfigException('both searchFields and fieldToFill properties are required');
        }
        parent::init();
        $this->attributes = [
            Base::EVENT_DEFAULTS => $this->fieldToFill
        ];
    }

    /**
     * Creates a search condition from the list of $searchFields to be used in a call to yii\db\ActiveQuery::where()
     * @return array Array-Condition used for yii\db\ActiveQuery::where()
     */
    protected function getSearchCondition () {
        $condition = [];
        foreach ($this->searchFields as $field) {
            $condition[$field] = $this->owner[$field];
        }
        return $condition;
    }

    /**
     * Calculates new next value based on the biggest existing value of column $fieldToFill in the group
     * defined by $searchFields
     * @param \yii\base\Event $event
     * @return int|mixed|string
     */
    protected function getValue($event)
    {
        /* @var $owner BaseActiveRecord */
        $owner = $this->owner;
        $lastRecord = $owner::find()
            ->where($this->getSearchCondition())
            ->orderBy([$this->fieldToFill => SORT_DESC])
            ->one();
        if ($owner->isNewRecord) {
            $tableSchema = $owner->getTableSchema();
            $columnSchema = $tableSchema->getColumn($this->fieldToFill);
            if (in_array($columnSchema->type, ['char', 'string'])) {
                if ($lastRecord != null) {
                    return chr(ord($lastRecord->attributes[$this->fieldToFill]) + 1);
                } else {
                    return 'A';
                }
            }
            else {
                if ($lastRecord != null) {
                    return $lastRecord->attributes[$this->fieldToFill] + 1;
                } else {
                    return 1;
                }
            }
        } else {
            return $owner[$this->fieldToFill];
        }
    }
}
