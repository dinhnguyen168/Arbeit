<?php
namespace app\behaviors\template;

use app\components\templates\ModelTemplate;
use app\models\core\Base;
use yii\base\InvalidConfigException;
use yii\behaviors\AttributeBehavior;
use yii\db\ActiveRecord;
use yii\web\HttpException;


/**
 * Class DefaultFromParentBehavior
 * @package app\behaviors\template
 */
class DefaultFromParentBehavior extends AttributeBehavior implements TemplateManagerBehaviorInterface
{
    /**
     * @var string parent's column name that contains the value which should be copied
     */
    public $parentSourceColumn;
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
        if (empty($this->parentSourceColumn) || empty($this->destinationColumn)) {
            throw new InvalidConfigException('both parentSourceColumn and destinationColumn properties are required');
        }
        $this->attributes = [
            Base::EVENT_DEFAULTS => $this->destinationColumn
        ];
        parent::init();
    }

    protected function getValue($event)
    {
        return $this->owner->parent->{$this->parentSourceColumn};
    }

    /**
     * Get the name of the behavior to show in the behaviors list
     * in the model template form
     * @return string the behavior name
     */
    static function getName()
    {
        return "Default from Parent";
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
                'name' => 'parentSourceColumn',
                'hint' => 'The parent column that contains the value you want to copy'
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
        if (empty($modelTemplate->parentModel)) {
            $errors[] = [
                'param' => 'parentSourceColumn',
                'message' => 'the current model does not have a parent'
            ];
            $isValid = false;
        } else {
            if (empty($params['parentSourceColumn'])) {
                $errors[] = [
                    'param' => 'parentSourceColumn',
                    'message' => 'parentSourceColumn cannot be empty'
                ];
                $isValid = false;
            }
            $parentModelTemplate = \Yii::$app->templates->getModelTemplate($modelTemplate->parentModel);
            if (!$parentModelTemplate->hasColumn($params['parentSourceColumn'])) {
                $errors[] = [
                    'param' => 'parentSourceColumn',
                    'message' => $params['parentSourceColumn'] . ' does not exist in the parent model'
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
