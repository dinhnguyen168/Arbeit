<?php
namespace app\behaviors\template;

use app\components\templates\ModelTemplate;
use app\models\core\Base;
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
class SiblingsLimitBehavior extends Behavior implements TemplateManagerBehaviorInterface
{
    /**
     * @var string The column that contains the id of the parent record. The number of identical values for this column is
     * limited by this behavior. The maximum number of records for one value is determined by $limit
     */
    public $parentRefColumn;
    /**
     * @var integer An integer number that defines the maximum number of records or a callback function that gets the object
     * of the current record and delivers the maximum number of records with the same value in column $parentRefColumn.
     */
    public $limit;

    /**
     * {@inheritdoc}
     * Checks the parameters.
     * @throws InvalidConfigException
     */
    public function init()
    {
        if (empty($this->parentRefColumn) || empty($this->limit)) {
            throw new InvalidConfigException('both parentRefColumn and limit properties are required');
        }
        if (!is_int($this->limit) && !is_callable($this->limit)) {
            throw new InvalidConfigException('limit can only be integer or a callback.');
        }
        parent::init();
    }

    /**
     * Returns the maximum number of records with the same value in $parentRefColumn as the current record
     * @return integer
     */
    protected function getLimitValue () {
        return is_callable($this->limit) ? call_user_func($this->limit, $this->owner) : $this->limit;
    }

    /**
     * {@inheritdoc}
     * @return array
     */
    public function events()
    {
        return [
            Base::EVENT_DEFAULTS => 'checkChildrenCount'
        ];
    }

    /**
     * Checks if there are more records than allowed with the same value in column $parentRefColumn.
     * @throws HttpException 409 exception
     */
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

    /**
     * Get the name of the behavior to show in the behaviors list
     * in the model template form
     * @return string the behavior name
     */
    static function getName()
    {
        return "Siblings Limit";
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
                'name' => 'limit',
                'hint' => 'Maximum number of sibling records'
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
        if ($params['limit'] <= 0) {
            $errors[] = [
                'param' => 'limit',
                'message' => 'limit must be a number bigger than 0'
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
        if (isset($params['limit'])) {
            $params['limit'] = intval($params['limit']);
        }
        return $params;
    }
}
