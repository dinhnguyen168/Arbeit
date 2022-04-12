<?php
namespace app\behaviors\template;

use app\components\templates\ModelTemplate;
use app\models\core\Base;
use yii\base\Event;
use yii\base\InvalidConfigException;
use yii\behaviors\AttributeBehavior;
use yii\db\ActiveRecord;
use yii\web\HttpException;


/**
 * Class DefaultFromParentBehavior
 * @package app\behaviors\template
 */
class DefaultFromSiblingBehavior extends AttributeBehavior implements TemplateManagerBehaviorInterface
{
    /**
     * @var string The column that contains the id of the parent record.
     */
    public $parentRefColumn;
    /**
     * @var string sibling's column name that contains the value which should be copied
     */
    public $sourceColumn;
    /**
     * @var string column name which the value will be copied into
     */
    public $destinationColumn;

    /**
     * {@inheritdoc}
     * @throws InvalidConfigException
     */
    public function init()
    {
        if (empty($this->parentRefColumn) || empty($this->sourceColumn) || empty($this->destinationColumn)) {
            throw new InvalidConfigException('parentRefColumn, sourceColumn and destinationColumn properties are required');
        }
        $this->attributes = [
            Base::EVENT_DEFAULTS => $this->destinationColumn
        ];
        parent::init();
    }

    protected function getValue($event)
    {
        /* @var $owner ActiveRecord*/
        $owner = $this->owner;
        $lastSibling = $owner::find()->where([
            $this->parentRefColumn => $this->owner->{$this->parentRefColumn}
        ])->orderBy(['id' => SORT_DESC])->one();
        if ($lastSibling) {
            return $lastSibling->{$this->sourceColumn};

        }
        return null; // $this->owner->{$this->destinationColumn} = $this->owner->parent->{$this->sourceColumn};
    }

    /**
     * Evaluates the attribute value and assigns it to the current attributes.
     * If the value is null, it will not be assigned
     * @param Event $event
     */
    public function evaluateAttributes($event)
    {
        if ($this->getValue($event) === null) {
            return;
        }
        parent::evaluateAttributes($event);
    }

    /**
     * Get the name of the behavior to show in the behaviors list
     * in the model template form
     * @return string the behavior name
     */
    static function getName()
    {
        return "Default from Sibling";
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
                'name' => 'parentRefColumn',
                'hint' => 'The column that contains the id of the parent record'
            ],
            [
                'name' => 'sourceColumn',
                'hint' => 'The sibling column that contains the value you want to copy'
            ],
            [
                'name' => 'destinationColumn',
                'hint' => 'the column name that holds the copied value'
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
        if (empty($params['parentRefColumn'])) {
            $errors[] = [
                'param' => 'parentRefColumn',
                'message' => 'parentRefColumn cannot be empty'
            ];
            $isValid = false;
        } elseif (!$modelTemplate->hasColumn($params['parentRefColumn'])) {
            $errors[] = [
                'param' => 'parentRefColumn',
                'message' => $params['parentRefColumn'] . ' does not exist in the current model'
            ];
            $isValid = false;
        }
        if (empty($params['sourceColumn'])) {
            $errors[] = [
                'param' => 'sourceColumn',
                'message' => 'sourceColumn cannot be empty'
            ];
            $isValid = false;
        } elseif (!$modelTemplate->hasColumn($params['sourceColumn'])) {
            $errors[] = [
                'param' => 'sourceColumn',
                'message' => $params['sourceColumn'] . ' does not exist in the parent model'
            ];
            $isValid = false;
        }
        if (empty($params['destinationColumn'])) {
            $errors[] = [
                'param' => 'destinationColumn',
                'message' => 'destinationColumn cannot be empty'
            ];
            $isValid = false;
        } elseif (!$modelTemplate->hasColumn($params['destinationColumn'])) {
            $errors[] = [
                'param' => 'destinationColumn',
                'message' => $params['destinationColumn'] . ' does not exist in the current model'
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
