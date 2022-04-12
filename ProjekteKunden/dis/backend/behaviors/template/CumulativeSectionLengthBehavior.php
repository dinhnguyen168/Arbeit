<?php
namespace app\behaviors\template;

use app\models\core\Base;
use yii\base\Behavior;
use yii\web\HttpException;



/**
 * Class DefaultFromParentBehavior
 * @package app\behaviors\template
 */
class CumulativeSectionLengthBehavior extends Behavior implements TemplateManagerBehaviorInterface
    {

    public $minRelativeLength;

    public $maxRelativeLength;

    public function events() {
        return [
            Base::EVENT_BEFORE_INSERT => 'onBeforeInsert'
        ];
    }

    public function onBeforeInsert ($event) {
        if ($this->owner->ignore == 0) {
            $sectionLengths = $this->owner::find()->where(['core_id' => $this->owner->core_id])->sum('init_length') + 0.0;
            $newSectionLength = $this->owner->init_length;
            $cumulativeSectionLength = $sectionLengths + $newSectionLength;
            $presentCoreLength = $this->owner->parent->core_recovery;
            if ($presentCoreLength > 0) {
                $sectionCount = $this->owner::find()->andWhere(['core_id' => $this->owner->core_id])->count() + 1;
                if ($sectionCount == $this->owner->parent->last_section && $cumulativeSectionLength/$presentCoreLength > $this->maxRelativeLength) {
                    throw new HttpException(409, "The total length of the registered sections is too long.");
                } elseif
                ($sectionCount == $this->owner->parent->last_section && $cumulativeSectionLength/$presentCoreLength < $this->minRelativeLength) {
                    throw new HttpException(409, "The total length of the registered sections is too short.");
                }
            }
            else {
                throw new HttpException(409, "The core recovery is 0. You can't register sections.");
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
        return "Cumulative section length";
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
                'name' => 'minRelativeLength',
                'hint' => 'The lower cut-off value for core validation (ratio, between 0.8 and 1.0).'
            ],
            [
                'name' => 'maxRelativeLength',
                'hint' => 'The upper cut-off value for core validation (ratio, between 1.0 and 1.2).'
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

        if (empty($params['minRelativeLength'])) {
            $errors[]=
                    [
                        'param' => 'minRelativeLength',
                        'message' => 'minRelativeLength cannot be empty.'
                    ];
            $isValid = false;
        }
        elseif ($params['minRelativeLength'] >= 1.0  || $params['minRelativeLength'] <= 0.8) {
            $errors[]=
                    [
                        'param' => 'minRelativeLength',
                        'message' => 'minRelativeLength has to be a ratio between 0.80 and 1.00.'
                    ];
            $isValid = false;
        }

        if (empty($params['maxRelativeLength'])) {
            $errors[]=
                    [
                        'param' => 'maxRelativeLength',
                        'message' => 'maxRelativeLength cannot be empty.'
                    ];
            $isValid = false;
        }
        elseif ($params['maxRelativeLength'] >= 1.2  || $params['maxRelativeLength'] <= 1.0) {
            $errors[]=
                    [
                        'param' => 'maxRelativeLength',
                        'message' => 'maxRelativeLength has to be a ratio between 1.00 and 1.20.'
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
        $params['minRelativeLength'] = floatval($params['minRelativeLength']);
        $params['maxRelativeLength'] = floatval($params['maxRelativeLength']);
        return $params;
    }
}
