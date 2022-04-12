<?php

namespace app\models\base;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use \app\models\GeologyLithoUnits;

/**
* This is the search base model class for model "GeologyLithoUnits".
* DO NOT EDIT THIS FILE.
*/
abstract class BaseGeologyLithoUnitsSearch extends GeologyLithoUnits
{
    use \app\models\core\SearchModelTrait;

    public $program_id;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'expedition_id', 'lithological_unit', 'lithological_unit_name', 'description', 'combined_id', 'program_id'],'safe'],
        ];
    }

    public function getExtraSortAttributes () {
        return [
        ];
    }

    protected function addQueryColumns($query) {
        $this->addQueryColumn($query, 'id', 'number');
        $this->addQueryColumn($query, 'expedition_id', 'number');
        $this->addQueryColumn($query, 'lithological_unit', 'string');
        $this->addQueryColumn($query, 'lithological_unit_name', 'string');
        $this->addQueryColumn($query, 'description', 'string');
        $this->addQueryColumn($query, 'combined_id', 'string');
    }

    protected function addQuerySearchAttributes($query) {
        $joins = [];
        $this->addQuerySearchAttribute($query, 'program_id', 'project_expedition', 'geology_litho_units.expedition_id', 'project_expedition.id', $joins);
        $this->createSearchJoins($query, $joins);
    }

}