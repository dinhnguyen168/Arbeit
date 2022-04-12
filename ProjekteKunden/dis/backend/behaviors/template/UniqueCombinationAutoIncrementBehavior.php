<?php
namespace app\behaviors\template;

use app\components\templates\ModelTemplate;
use yii\base\InvalidConfigException;
use yii\behaviors\AttributeBehavior;
use yii\db\ActiveRecord;
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
 */
class UniqueCombinationAutoIncrementBehavior extends AttributeBehavior implements TemplateManagerBehaviorInterface
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
     * @var bool whether to use A-Z increment or 1-9 increment
     */
    public $useAlphabet = false;

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
     * @throws InvalidConfigException
     */
    protected function getValue($event)
    {
        /* @var $owner ActiveRecord */
        $owner = $this->owner;
        $lastRecord = $owner::find()
            ->where($this->getSearchCondition())
            ->orderBy([$this->fieldToFill => SORT_DESC])
            ->one();
        if ($owner->isNewRecord) {
            $tableSchema = $owner::getTableSchema();
            $columnSchema = $tableSchema->getColumn($this->fieldToFill);
            $isStringColumn = in_array($columnSchema->type, ['char', 'string']);;
            if ($isStringColumn && $this->useAlphabet) {
                if ($lastRecord != null) {
                    return chr(ord($lastRecord->attributes[$this->fieldToFill]) + 1);
                } else {
                    return 'A';
                }
            }
            else {
                if ($lastRecord != null) {
                    $val = intval($lastRecord->attributes[$this->fieldToFill]) + 1;
                } else {
                    $val = 1;
                }
                if ($isStringColumn) $val = strval($val);
                return $val;
            }
        } else {
            return $owner[$this->fieldToFill];
        }
    }

    /**
     * Get the name of the behavior to show in the behaviors list
     * in the model template form
     * @return string the behavior name
     */
    static function getName()
    {
        return "Unique Combination Auto Increment";
    }

    /**
     * Get a list of parameters names which should be defined by
     * the user in the model template form
     * @return string[] list of the behavior parameters
     */
    static function getParameters()
    {
        return [
            [
                'name' => 'searchFields',
                'hint' => 'columns names (comma separated) that build the group in which a unique value should be created'
            ],
            [
                'name' => 'fieldToFill',
                'hint' => 'column name to be filled by the calculated value'
            ],
            [
                'name' => 'useAlphabet',
                'hint' => 'use A-Z values for auto increment? (y/n) or (true/false)'
            ]
        ];
    }

    /**
     * Validates the user input for the parameters values
     * @param $modelTemplate ModelTemplate
     * @param $params array ['param1' => 'value1', ...]
     * @param $errors array holds the validation errors
     * @return boolean whether the parameters are valid
     */
    static function validateParametersValues($modelTemplate, $params, &$errors)
    {
        $isValid = true;
        if (empty($params['searchFields'])) {
            $errors[] = [
                'param' => 'searchFields',
                'message' => 'searchFields cannot be empty'
            ];
            $isValid = false;
        } else {
            foreach ($params['searchFields'] as $field) {
                if (!$modelTemplate->hasColumn($field)) {
                    $errors[] = [
                        'param' => $field,
                        'message' => $field . ' does not exist in the current model'
                    ];
                    $isValid = false;
                }
            }
        }
        if (empty($params['fieldToFill'])) {
            $errors[] = [
                'param' => 'fieldToFill',
                'message' => 'fieldToFill cannot be empty'
            ];
            $isValid = false;
        } else {
            if (!$modelTemplate->hasColumn($params['fieldToFill'])) {
                $errors[] = [
                    'param' => $params['fieldToFill'],
                    'message' => $params['fieldToFill'] . ' does not exist in the current model'
                ];
                $isValid = false;
            }
        }
        if (!is_bool($params['useAlphabet'])) {
            $errors[] = [
                'param' => $params['useAlphabet'],
                'message' => $params['useAlphabet'] . ' should be either "y" for "Yes" or "n" for "No"'
            ];
            $isValid = false;
        }

        return $isValid;
    }

    /**
     * Converts/cast the parameters values if necessary
     * @param $params array ['param1' => 'value1', ...]
     * @return array
     */
    static function parseParameters($params)
    {
        if (isset($params['searchFields']) && is_string($params['searchFields'])) {
            $params['searchFields'] = array_map('trim', explode(',', $params['searchFields']));
        }
        if (isset($params['useAlphabet']) && !is_bool($params['useAlphabet'])) {
            $params['useAlphabet'] = strtr($params['useAlphabet'], ['y' => 'true', 'n' => 'false']);
            $params['useAlphabet'] = filter_var($params['useAlphabet'], FILTER_VALIDATE_BOOLEAN);
        }
        return $params;
    }
}
