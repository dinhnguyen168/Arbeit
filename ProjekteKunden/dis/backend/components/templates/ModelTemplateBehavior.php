<?php
/**
 * Created by PhpStorm.
 * User: alali
 * Date: 24.01.2019
 * Time: 14:28
 */

namespace app\components\templates;


use app\behaviors\template\TemplateManagerBehaviorInterface;
use app\migrations\Migration;
use yii\base\Model;

/**
 * to represent the behavior of a model template
 * Class ModelTemplateBehavior
 * @package app\components\templates
 */
class ModelTemplateBehavior extends Model
{
    /**
     * must be unique
     * @var string name of the key
     */
    public $behaviorClass;
    /**
     * the foreign table of the key
     * @var array ["param1" => "value1", ...]
     */
    public $parameters;

    /**
     * @var ModelTemplate the parent model
     */
    private $_model;

    public function __construct($parentModel, array $config = [])
    {
        $this->_model = $parentModel;
        if (isset($config['behaviorClass']) && class_exists($config['behaviorClass'])) {
            /* @var $behaviorClass TemplateManagerBehaviorInterface */
            $behaviorClass = $config['behaviorClass'];
            if (isset($config['parameters'])) {
                $config['parameters'] = $behaviorClass::parseParameters($config['parameters']);
            }
        }
        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['behaviorClass'], 'required']
        ];
    }

    public function validate($attributeNames = null, $clearErrors = true)
    {
        $isValid = parent::validate($attributeNames, $clearErrors);
        /* @var $behaviorClass TemplateManagerBehaviorInterface */
        $behaviorClass = $this->behaviorClass;
        if (class_exists($behaviorClass)){
            $errors = [];
            $behaviorClass::validateParametersValues($this->_model, $this->parameters, $errors);
            if (count($errors) > 0) {
                $isValid = false;
                foreach ($errors as $error) {
                    $this->addError('parameters', $error['message']);
                }
            }
        }
        return $isValid;
    }
}
