<?php
namespace app\behaviors\template;

use app\components\templates\ModelTemplate;
use yii\db\ActiveRecord;
use yii\base\InvalidConfigException;
use yii\base\Behavior;


/**
 * Class DefaultFromOtherColumnBehavior
 * @package app\behaviors\template
 *
 * The value of one column (sourceColumn) should be used as the default value of a different column (targetColumn).
 * But the values of both columns may be different.
 * (Example in CoreSection: section_length and curated_length)
 *
 * In the following cases, the value from sourceColumn is copied to targetColumn:
 * - the targetColumn is empty
 * - sourceColumn is changed AND targetColumn is not changed AND the old values of both columns where the same
 */
class DefaultFromOtherColumnBehavior extends Behavior implements TemplateManagerBehaviorInterface
{
    /**
     * @var string Soruce column (see above)
     */
    public $sourceColumn;
    /**
     * @var string Target column (see above)
     */
    public $targetColumn;

    /**
     * {@inheritdoc}
     * @throws InvalidConfigException
     */
    public function init()
    {
        if (empty($this->sourceColumn)) {
            throw new InvalidConfigException("The property 'sourceColumn' is required");
        }
        else if ($this->owner && !$this->owner->hasProperty($this->sourceColumn)) {
            throw new InvalidConfigException("The sourceColumn '" . $this->sourceColumn . "' does not exist");
        }
        else if (empty($this->targetColumn)) {
            throw new InvalidConfigException("The property 'targetColumn' is required");
        }
        else if ($this->owner && !$this->owner->hasProperty($this->targetColumn)) {
            throw new InvalidConfigException("The targetColumn '" . $this->targetColumn . "' does not exist");
        }

        parent::init();
    }

    /**
     * {@inheritdoc}
     */
    public function events()
    {
        return [
            ActiveRecord::EVENT_BEFORE_UPDATE  => 'updateTargetColumn',
            ActiveRecord::EVENT_BEFORE_INSERT  => 'updateTargetColumn',
        ];
    }


    public function updateTargetColumn($event)
    {
        $dirtyAttributes = $this->owner->getDirtyAttributes();

        $sourceValue = $this->owner->{$this->sourceColumn};
        $sourceChanged = isset($dirtyAttributes[$this->sourceColumn]);
        $oldSourceValue = $this->owner->getOldAttribute($this->sourceColumn);

        $targetValue = $this->owner->{$this->targetColumn};
        $targetChanged = isset($dirtyAttributes[$this->targetColumn]);
        $oldTargetValue = $this->owner->getOldAttribute($this->targetColumn);

        if (empty($targetValue) ||
            ($sourceChanged && !$targetChanged && $oldSourceValue == $oldTargetValue)) {
            $this->owner->{$this->targetColumn} = $sourceValue;
        }
    }


    /**
     * Get the name of the behavior to show in the behaviors list
     * in the model template form
     * @return string the behavior name
     */
    static function getName()
    {
        return "Default value from other column";
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
                'name' => 'sourceColumn',
                'hint' => 'The column from where to copy the default value'
            ],
            [
                'name' => 'targetColumn',
                'hint' => 'The column to copy the value to if it is empty or the sourceColumn value was changed'
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
        if (trim($params['sourceColumn']) == '') {
            $errors[] = [
                'param' => 'sourceColumn',
                'message' => "Please enter a column name for sourceColumn"
            ];
            $isValid = false;
        }
        else if (!$modelTemplate->hasColumn($params['sourceColumn'])) {
            $errors[] = [
                'param' => 'sourceColumn',
                'message' => "Column '" . $params['sourceColumn'] . "' does not exist in the model"
            ];
            $isValid = false;
        }

        if (trim($params['targetColumn']) == '') {
            $errors[] = [
                'param' => 'targetColumn',
                'message' => "Please enter a column name for targetColumn"
            ];
            $isValid = false;
        }
        else if (!$modelTemplate->hasColumn($params['targetColumn'])) {
            $errors[] = [
                'param' => 'targetColumn',
                'message' => "Column '" . $params['targetColumn'] . "' does not exist in the model"
            ];
            $isValid = false;
        }

        if ($isValid && $params['sourceColumn'] == $params['targetColumn']) {
            $errors[] = [
                'param' => 'targetColumn',
                'message' => "Please enter different columns for source and target"
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
