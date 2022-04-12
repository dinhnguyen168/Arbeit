<?php
namespace app\behaviors\template;

use app\components\templates\ModelTemplate;
use app\models\core\Base;
use app\models\base\BaseCurationSectionSplit;
use yii\base\Behavior;
use yii\base\InvalidConfigException;
use yii\web\HttpException;


/**
 * Class ValidateSplitOriginTypeBehavior
 * Validates the origin split type in CurationSectionSplit records
 * @package app\behaviors
 */
class ValidateSplitOriginTypeBehavior extends Behavior implements TemplateManagerBehaviorInterface
{
    /**
     * {@inheritdoc}
     * @return array
     */
    public function events()
    {
        return [
            Base::EVENT_AFTER_VALIDATE => 'checkOriginSplitTypeValue'
        ];
    }

    /**
     * Checks if the origin split type value exists in one of the existing splits
     */
    public function checkOriginSplitTypeValue () {
        /* @var $owner BaseCurationSectionSplit*/
        $owner = $this->owner;
        if ($owner !== null && !($owner instanceof BaseCurationSectionSplit)) {
            throw new InvalidConfigException('ValidateSplitOriginTypeBehavior can only be added to CurationSectionSplit class.');
        }
        $className = get_class($owner);
        if (!preg_match("/.+Search$/", $className )) {
            $query = $owner::find()->where(['section_id' => $owner->section_id]);
            $count = $query->count();
            if ($count == 0 && $owner->type !== "WR") {
                $this->owner->addError('type', "First section split must be of a type 'WR'");
                // throw new HttpException(409, "First section split must be of a type 'WR'.");
            } elseif ($count != 0 && $owner->type !== "WR") {
                $originTypeExists = $query->andWhere(['type' => $owner->origin_split_type])->exists();
                if (!$originTypeExists) {
                    $this->owner->addError('origin_split_type', "origin type does not exist in one of this section splits.");
//                    throw new HttpException(409, "origin type does not exist in one of this section splits.");
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
        return "Validate Origin Split Type Value";
    }

    /**
     * Get a list of parameters names which should be defined by
     * the user in the model template form
     * @return string[] list of the behavior parameters
     */
    static function getParameters()
    {
        return [];
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
        return true;
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
