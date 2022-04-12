<?php
namespace app\behaviors\template;

use app\components\templates\ModelTemplate;
use app\models\core\Base;
use yii\base\InvalidConfigException;
use yii\base\Behavior;
use yii\db\ActiveRecord;
use yii\db\BaseActiveRecord;
use yii\web\HttpException;


/**
 * Class CreateUpdateCoreboxBehavior
 * @package app\behaviors\template
 */
class CreateUpdateCoreboxBehavior extends Behavior implements TemplateManagerBehaviorInterface
{
    /**
     * @var string column name which has the value of the corebox name
     */
    public $coreboxNameColumn;

    /**
     * @var string column name of the corebox id
     */
    public $coreboxIdColumn;


    /**
     * {@inheritdoc}
     * @throws InvalidConfigException
     */
    public function init()
    {
        if (empty($this->coreboxNameColumn) || empty($this->coreboxIdColumn)) {
            throw new InvalidConfigException('coreboxNameColumn and coreboxIdColumn properties are required');
        }
        parent::init();
    }


    /**
     * {@inheritdoc}
     */
    public function events()
    {
        if (empty($this->coreboxNameColumn) || empty($this->coreboxIdColumn)) {
            throw new InvalidConfigException('Both coreboxNameColumn and coreboxIdColumn properties are required');
        }

        return [
            BaseActiveRecord::EVENT_AFTER_FIND => 'updateCoreboxName',
            BaseActiveRecord::EVENT_BEFORE_UPDATE  => 'updateCoreboxId',
            BaseActiveRecord::EVENT_BEFORE_INSERT  => 'updateCoreboxId',
        ];
    }


    public function updateCoreboxId($event)
    {
        if (in_array($this->coreboxNameColumn, array_keys($this->owner->dirtyAttributes))) {
            $coreBoxId = null;
            $name = $this->owner->{$this->coreboxNameColumn};
            if ($name > "") {
                $hole = $this->owner->hole;
                if ($hole) {
                    $holeId = $hole->id;
                    $coreBox = \app\models\CurationCorebox::find()
                        ->andWhere(['hole_id' => $holeId])
                        ->andWhere(['corebox' => $name])
                        ->one();

                    if ($coreBox)
                        $coreBoxId = $coreBox->id;
                    else {
                        $coreBox = new \app\models\CurationCorebox();
                        $coreBox->hole_id = $holeId;
                        $coreBox->corebox = $name;
                        if ($coreBox->validate() && $coreBox->save())
                            $coreBoxId = $coreBox->id;
                        else
                            $this->owner->addError($this->coreboxNameColumn, "Could not create new core box '" . $name . "'");
                    }
                }
            }
            $this->owner->{$this->coreboxIdColumn} = $coreBoxId;
        }
    }

    public function updateCoreboxName($event)
    {
        $coreBoxId = $this->owner->{$this->coreboxIdColumn};
        if ($coreBoxId > 0) {
            $coreBox = null;
            if ($this->owner->hasMethod('getCorebox'))
                $coreBox = $this->owner->corebox;
            else {
                $coreBox = \app\models\CurationCorebox::find()
                    ->andWhere(['id' => $coreBoxId])
                    ->one();
            }
            if ($coreBox)
                $this->owner->{$this->coreboxNameColumn} = $coreBox->corebox;
            else {
                $this->owner->{$this->coreboxNameColumn} = "";
                $this->owner->coreBoxId = null;
            }
        }
    }


    /**
     * Get the name of the behavior to show in the behaviors list
     * in the model template form
     * @return string the behavior name
     */
    static function getName()
    {
        return "Create or update core box";
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
                'name' => 'coreboxNameColumn',
                'hint' => 'The column that contains the name of the core box'
            ],
            [
                'name' => 'coreboxIdColumn',
                'hint' => 'The column that holds the id of the generated or assigned core box'
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
        if (empty($params['coreboxNameColumn'])) {
            $errors[] = [
                'param' => 'coreboxNameColumn',
                'message' => 'coreboxNameColumn cannot be empty'
            ];
            $isValid = false;
        }
        if (empty($params['coreboxIdColumn'])) {
            $errors[] = [
                'param' => 'coreboxIdColumn',
                'message' => 'coreboxIdColumn cannot be empty'
            ];
            $isValid = false;
        }
        if (!$modelTemplate->hasColumn($params['coreboxNameColumn'])) {
            $errors[] = [
                'param' => 'coreboxNameColumn',
                'message' => $params['coreboxNameColumn'] . ' does not exist in this model'
            ];
            $isValid = false;
        }
        if (!$modelTemplate->hasColumn($params['coreboxIdColumn'])) {
            $errors[] = [
                'param' => 'coreboxIdColumn',
                'message' => $params['coreboxIdColumn'] . ' does not exist in this model'
            ];
            $isValid = false;
        }

        $ancestorModelTemplate = $modelTemplate;
        while ($ancestorModelTemplate && $ancestorModelTemplate->parentModel !== "ProjectHole") {
            $ancestorModelTemplate = \Yii::$app->templates->getModelTemplate($ancestorModelTemplate->parentModel);
        }
        if ($ancestorModelTemplate->parentModel !== "ProjectHole") {
            $errors[] = [
                'param' => 'coreboxIdColumn',
                'message' => 'This model must have an ancestor model "ProjectHole"'
            ];
            $isValid = false;
        }

        $coreboxModelTemplate = \Yii::$app->templates->getModelTemplate("CurationCorebox");
        if (!$coreboxModelTemplate) {
            $errors[] = [
                'param' => 'coreboxIdColumn',
                'message' => 'The required model "CurationCorebox" does not exist'
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
