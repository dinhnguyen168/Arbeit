<?php
namespace app\behaviors\template;

use app\components\templates\ModelTemplate;
use yii\db\ActiveRecord;
use yii\base\InvalidConfigException;
use yii\behaviors\AttributeBehavior;


/**
 * Class LeadingZerosBehavior
 * @package app\behaviors\template
 *
 * If string columns are used to store number value the sorting does not work if the number of digits
 * differs in the values.
 * This behavior extends the values by left padding values with "0"s to a fixed length.
 * We only count the starting digits so this should also work, if a value continues with letters.
 * Values will not be truncated if they are shorted than the given length!
 * If the value does not start with a number nothing will be changed.
 * Examples (length is set to 4):
 * - 0 => 0000
 * - 23 => 0023
 * - 2345 => 2345
 * - 34567 => 34567
 * - a => a
 * - 23a => 0023a
 * - 23.332 => 0023.332
 * - 23,456 => 0023,456
 */
class LeadingZerosBehavior extends AttributeBehavior implements TemplateManagerBehaviorInterface
{
    /**
     * @var string Column name with the value to fix
     */
    public $column;
    /**
     * @var int Fixed length to fill up numbers to
     */
    public $length = 0;

    /**
     * {@inheritdoc}
     * @throws InvalidConfigException
     */
    public function init()
    {
        if (empty($this->column)) {
            throw new InvalidConfigException("The property 'column' is required");
        }
        else if ($this->owner && !$this->owner->hasProperty($this->column)) {
            throw new InvalidConfigException("The column '" . $this->column . "' does not exist");
        }

        $this->attributes = [
            ActiveRecord::EVENT_BEFORE_INSERT => $this->column,
            ActiveRecord::EVENT_BEFORE_UPDATE => $this->column
        ];
        parent::init();
    }

    protected function getValue($event)
    {
        $matches = [];
        $value = $this->owner->{$this->column};
        $minLength = intval($this->length);
        if (preg_match("/^([0-9]+)/", $value, $matches)) {
            $len = strlen($matches[1]);
            if ($len < $minLength) {
                return str_pad($value, strlen($value) + ($minLength - $len), "0", STR_PAD_LEFT);
            }
        }
        return $value;
    }

    /**
     * Get the name of the behavior to show in the behaviors list
     * in the model template form
     * @return string the behavior name
     */
    static function getName()
    {
        return "Leading Zeros";
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
                'name' => 'column',
                'hint' => 'The Column to add leading zeros to'
            ],
            [
                'name' => 'length',
                'hint' => 'The length of the resulting numeric values'
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
        if (trim($params['column']) == '') {
            $errors[] = [
                'param' => 'column',
                'message' => "Please enter a column name"
            ];
            $isValid = false;
        }
        else if (!$modelTemplate->hasColumn($params['column'])) {
            $errors[] = [
                'param' => 'column',
                'message' => "Column '" . $params['column'] . "' does not exist in the model"
            ];
            $isValid = false;
        }

        $length = intval($params['length']);
        if ($length < 2) {
            $errors[] = [
                'param' => 'length',
                'message' => "The property 'length' must be bigger than 1"
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
        return $params;
    }
}
