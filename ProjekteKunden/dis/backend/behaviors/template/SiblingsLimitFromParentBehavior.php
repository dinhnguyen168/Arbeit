<?php


namespace app\behaviors\template;


use app\components\templates\ModelTemplate;
use app\models\core\Base;
use yii\base\Behavior;
use yii\base\InvalidConfigException;
use yii\db\ActiveRecord;
use yii\web\HttpException;

class SiblingsLimitFromParentBehavior extends Behavior implements TemplateManagerBehaviorInterface
{
    public $parentRefColumn;
    public $parentSourceColumn;

    public function init()
    {
        if (empty($this->parentSourceColumn) || empty($this->parentRefColumn)) {
            throw new InvalidConfigException('both parentSourceColumn and destinationColumn properties are required');
        }
        parent::init();
    }

    public function events()
    {
        return [
            Base::EVENT_DEFAULTS => 'checkChildrenCount'
        ];
    }

    public function checkChildrenCount () {
        /* @var $owner ActiveRecord*/
        $owner = $this->owner;
        $siblingsCount = $owner::find()->select(['id'])->andWhere([
            $this->parentRefColumn => $this->owner->{$this->parentRefColumn}
        ])->distinct()->count();
        if ($siblingsCount >= $this->getLimitValue()) {
            throw new HttpException(409, "cannot create more than $siblingsCount with the same $this->parentRefColumn value.");
        }
    }

    protected function getLimitValue () {
        return $this->owner->parent->{$this->parentSourceColumn};
    }

    /**
     * Get the name of the behavior to show in the behaviors list
     * in the model template form
     * @return string the behavior name
     */
    static function getName()
    {
        return "Children Limit From Parent";
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
                'name' => 'parentSourceColumn',
                'hint' => 'The column in parent that has the limit value'
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
        $errors = [];
        if (empty($params['parentRefColumn'])) {
            $errors[] = [
                'param' => 'parentRefColumn',
                'message' => 'parentRefColumn cannot be empty'
            ];
            $isValid = false;
        }
        if (empty($params['parentSourceColumn'])) {
            $errors[] = [
                'param' => 'parentSourceColumn',
                'message' => 'parentSourceColumn cannot be empty'
            ];
            $isValid = false;
        }
        if (!$modelTemplate->hasColumn($params['parentRefColumn'])) {
            $errors[] = [
                'param' => 'parentRefColumn',
                'message' => 'parentRefColumn does not exist'
            ];
            $isValid = false;
        }
        $parentModelTemplate = \Yii::$app->templates->getModelTemplate($modelTemplate->parentModel);
        if (!$parentModelTemplate->hasColumn($params['parentSourceColumn'])) {
            $errors[] = [
                'param' => 'parentSourceColumn',
                'message' => 'parentSourceColumn does not exist'
            ];
            $isValid = false;
        }
        return $isValid;
    }

    /**
     * Converts/cast the parameters values if necessary. Called when initiating a new instance of
     * a class that implements TemplateManagerBehaviorInterface.
     * @param $params array ['param1' => 'value1', ...]
     * @return array processed parameters
     */
    static function parseParameters($params)
    {
        return $params;
    }
}
