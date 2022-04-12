<?php
namespace app\behaviors\template;

use app\components\templates\ModelTemplate;
use app\models\core\Base;
use app\models\CurationSectionSplit;
use yii\base\Behavior;
use yii\base\InvalidConfigException;
use yii\db\ActiveRecord;
use yii\web\HttpException;


/**
 * Class SiblingsLimitBehavior
 * Limits the number of records with a same column value. Right now, it is used to limit the number
 * of sections for a core depending on the the value of column 'last_section' in the core table.
 * @package app\behaviors
 */
class SplittableSectionBehavior extends Behavior implements TemplateManagerBehaviorInterface
{
    /**
     * @var integer An integer number that defines the maximum number of records or a callback function that gets the object
     * of the current record and delivers the maximum number of records with the same value in column $parentRefColumn.
     */
    public $splitsModel;

    /**
     * {@inheritdoc}
     * Checks the parameters.
     * @throws InvalidConfigException
     */
    public function init()
    {
        if (empty($this->splitsModel)) {
            throw new InvalidConfigException('splitModel property is required');
        }
        parent::init();
    }

    /**
     * {@inheritdoc}
     * @return array
     */
    public function events()
    {
        return [
            Base::EVENT_AFTER_INSERT => 'createDefaultSplit'
        ];
    }

    /**
     * Checks if there are more records than allowed with the same value in column $parentRefColumn.
     * @throws HttpException 409 exception
     */
    public function createDefaultSplit () {
        /* @var $owner ActiveRecord*/
        $owner = $this->owner;
        $splitsModel = '\\app\\models\\' . $this->splitsModel;
        $newSplit = new $splitsModel;
        $newSplit->section_id = $owner->id;
        $newSplit->type = 'WR';
        $newSplit->still_exists = 1;
        $newSplit->sampleable = 0;
        $newSplit->percent=100;
        $selectAttributes = array('id' => 0, 'curator' => 0);
        $attributesToCopy = array_intersect_key($this->owner->attributes, $selectAttributes);
        $newSplit->trigger(Base::EVENT_DEFAULTS);
        $newSplit->setAttributes($attributesToCopy);
        $newSplit->validate();
        $newSplit->save();
    }

    /**
     * Get the name of the behavior to show in the behaviors list
     * in the model template form
     * @return string the behavior name
     */
    static function getName()
    {
        return "Splittable Section";
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
                'name' => 'splitsModel',
                'hint' => 'The table where the splits will be saved e.g. CurationSectionSplit'
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
        if ($modelTemplate->getFullName() !== "CoreSection") {
            $errors[] = [
                'param' => 'owner',
                'message' => 'This behavior can only be added to CoreSection'
            ];
            $isValid = false;
        } elseif (empty($params['splitsModel'])) {
            $errors[] = [
                'param' => 'splitsModel',
                'message' => 'splitsModel cannot be empty'
            ];
            $isValid = false;
        } else {
            $splitsModelTemplate = \Yii::$app->templates->getModelTemplate($params['splitsModel']);
            if ($splitsModelTemplate) {
                if (!$splitsModelTemplate->hasColumn('section_id') ||
                    !$splitsModelTemplate->hasColumn('origin_split_id') ||
                    !$splitsModelTemplate->hasColumn('still_exists') ||
                    !$splitsModelTemplate->hasColumn('sampleable') ||
                    !$splitsModelTemplate->hasColumn('type') ||
                    !$splitsModelTemplate->hasColumn('percent')) {
                    $errors[] = [
                        'param' => 'splitsModel',
                        'message' => $params['splitsModel'] .' must have these columns: percent, type, sampleable, still_exists, origin_split_id and section_id'
                    ];
                }
            } else {
                $errors[] = [
                    'param' => 'splitsModel',
                    'message' => $params['splitsModel'] .' does not exist'
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
        if (isset($params['limit'])) {
            $params['limit'] = intval($params['limit']);
        }
        return $params;
    }
}
