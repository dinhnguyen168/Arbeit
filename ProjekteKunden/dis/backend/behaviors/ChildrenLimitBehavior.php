<?php
namespace app\behaviors;

use app\models\core\Base;
use yii\base\Behavior;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\web\HttpException;


/**
 * Class ChildrenLimitBehavior
 * Limits the number of records with a same column value. Right now, it is used to limit the number
 * of sections for a core depending on the the value of column 'last_section' in the core table.
 * @package app\behaviors
 */
class ChildrenLimitBehavior extends Behavior
{
    /**
     * @var The column that contains the id of the parent record. The number of identical values for this column is
     * limited by this behavior. The maximum number of records for one value is determined by $limit
     */
    public $parentRefColumn;
    /**
     * @var An integer number that defines the maximum number of records or a callback function that gets the object
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
        $siblingsCount = $this->owner::find()->select(['id'])->andWhere([
            $this->parentRefColumn => $this->owner->{$this->parentRefColumn}
        ])->distinct()->count();
        if ($siblingsCount >= $this->getLimitValue()) {
            throw new HttpException(409, "cannot create more than $siblingsCount with the same $this->parentRefColumn value.");
        }
    }
}
