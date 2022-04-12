<?php
namespace app\behaviors\template;

use app\models\core\Base;
use yii\base\Behavior;
use app\models\CurationSectionSplit;



/**
 * Class DefaultFromParentBehavior
 * @package app\behaviors\template
 */
class DefaultFromSiblingUnitsBehavior extends Behavior implements TemplateManagerBehaviorInterface
    {

    public $unitsRelationName;

    public $positionOnSectionColumnName;

    public function events() {
        return [
            Base::EVENT_DEFAULTS => 'onDefaultEvent'
        ];
    }

    public function onDefaultEvent ($event) {
        $unitModel=$this->owner;
        $splitModel=$unitModel->parent;
        $sectionModel=$splitModel->parent;
        $coreModel=$sectionModel->parent;
        $firstSectionUnitExist = $unitModel::find()->select(['section_split_id'])->andWhere(['section_split_id' => $this->owner->section_split_id])->count();
        if($sectionModel->section == 1 && $coreModel->core != 1 && $firstSectionUnitExist == 0){
            $previousCore = $coreModel::find()
                    ->where([
                        'hole_id' => $coreModel->hole_id,
                        'core' => $coreModel->core -1
                    ])
                    ->one();
            $previousCoreSections = $sectionModel::find()
                    ->where ([
                        'core_id' => $previousCore->id
                    ])
                    ->all();
            if (count($previousCoreSections) > 0) {
                usort($previousCoreSections, function ($a, $b) { return $a->section < $b->section ? -1 : 1; });
                $lastCoreSection = $previousCoreSections[count($previousCoreSections) -1];
                $lastSectionSplit = $splitModel::find()
                    ->where([
                        'section_id'=> $lastCoreSection->id,
                        'type' => $splitModel->type
                    ])
                ->one();
                $previousCoreLithologies = $lastSectionSplit->{$this->unitsRelationName};
                if (count($previousCoreLithologies) > 0) {
                    usort($previousCoreLithologies, function ($a, $b) { return $a->{$this->positionOnSectionColumnName} < $b->{$this->positionOnSectionColumnName} ? -1 : 1; });
                    $lastCoreLithology = $previousCoreLithologies[count($previousCoreLithologies) -1];
                    $attributes = $lastCoreLithology->attributes;
                    $attributes[$this->positionOnSectionColumnName] = 0;
                    unset($attributes["id"]);
                    foreach ($attributes as $key => $value){
                        if (empty($this->owner->{$key})) {
                            $this->owner->{$key} = $value;
                        }
                    }
                }
            }
        } elseif ($firstSectionUnitExist == 0 && $sectionModel->section != 1) {
            $previousCoreSection = $sectionModel::find()
                ->where([
                    'core_id' => $sectionModel->core_id,
                    'section' => $sectionModel->section -1,
                ])
                ->one();
            $previousSectionSplit = $splitModel::find()
                ->where([
                    'section_id'=> $previousCoreSection->id,
                    'type' => $splitModel->type
                ])
                ->one();
            $previousCoreLithologies = $previousSectionSplit->{$this->unitsRelationName};
            if (count($previousCoreLithologies) > 0) {
                usort($previousCoreLithologies, function ($a, $b) { return $a->{$this->positionOnSectionColumnName} < $b->{$this->positionOnSectionColumnName} ? -1 : 1; });
                $lastCoreLithology = $previousCoreLithologies[count($previousCoreLithologies) -1];
                $attributes = $lastCoreLithology->attributes;
                $attributes[$this->positionOnSectionColumnName] = 0;
                unset($attributes["id"]);
                foreach ($attributes as $key => $value){
                    if (empty($this->owner->{$key})) {
                        $this->owner->{$key} = $value;
                    }
                }
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
        return "Default from Sibling Units";
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
                'name' => 'unitsRelationName',
                'hint' => 'The name of the relation from section-split to unit. See definitions (@) in BaseCurationSectionSplit.php.'
            ],
            [
                'name' => 'positionOnSectionColumnName',
                'hint' => 'Name of the column that holds the position measurement on the core section.'
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

        if (empty($params['positionOnSectionColumnName'])) {
            $errors[]=
                    [
                        'param' => 'positionOnSectionColumnName',
                        'message' => 'positionOnSectionColumnName cannot be empty.'
                    ];
            $isValid = false;
        }
        elseif (!$modelTemplate->hasColumn($params['positionOnSectionColumnName'])) {
            $errors[]=
                    [
                        'param' => 'positionOnSectionColumnName',
                        'message' => $params['positionOnSectionColumnName'] . ' does not exist in the table.'
                    ];
            $isValid = false;
        }

        if (empty($params['unitsRelationName'])) {
            $errors[]=
                    [
                        'param' => 'unitsRelationName',
                        'message' => 'unitsRelationName cannot be empty.'
                    ];
            $isValid = false;
        }
        else {
            $newObject = new CurationSectionSplit();
            $relationMethodName = 'get' . ucfirst($params['unitsRelationName']);
            if (!method_exists($newObject, $relationMethodName)) {
                $errors[]=
                        [
                            'param' => 'unitsRelationName',
                            'message' => $params['unitsRelationName'] . ' does not exist in the model.'
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
