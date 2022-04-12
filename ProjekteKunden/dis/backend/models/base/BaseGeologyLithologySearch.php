<?php

namespace app\models\base;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use \app\models\GeologyLithology;

/**
* This is the search base model class for model "GeologyLithology".
* DO NOT EDIT THIS FILE.
*/
abstract class BaseGeologyLithologySearch extends GeologyLithology
{
    use \app\models\core\SearchModelTrait;

    public $program_id;
    public $expedition_id;
    public $site_id;
    public $hole_id;
    public $core_id;
    public $section_id;
    public $section_length;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'section_split_id', 'split_combined_id', 'combined_id', 'curator', 'section_length', 'litho_unit', 'top_depth', 'unit_length', 'bottom_depth', 'rock_class', 'rock_type', 'color', 'composition', 'description', 'program_id', 'expedition_id', 'site_id', 'hole_id', 'core_id', 'section_id'],'safe'],
        ];
    }

    public function getExtraSortAttributes () {
        return [
            'section_length' => [
                'asc' => ['core_section.section_length' => SORT_ASC],
                'desc' => ['core_section.section_length' => SORT_DESC],
            ],
        ];
    }

    protected function addQueryColumns($query) {
        $this->addQueryColumn($query, 'id', 'number');
        $this->addQueryColumn($query, 'curator', 'string');
        $this->addQueryColumn($query, 'section_split_id', 'number');
        $this->addQueryColumn($query, 'combined_id', 'string');
        $this->addQueryColumn($query, 'litho_unit', 'string');
        $this->addQueryColumn($query, 'top_depth', 'number');
        $this->addQueryColumn($query, 'unit_length', 'number');
        $this->addQueryColumn($query, 'bottom_depth', 'number');
        $this->addQueryColumn($query, 'rock_class', 'string');
        $this->addQueryColumn($query, 'rock_type', 'string');
        $this->addQueryColumn($query, 'color', 'string');
        $this->addQueryColumn($query, 'composition', 'string');
        $this->addQueryColumn($query, 'description', 'string');
        $this->addQueryPseudoColumn($query, 'section_length', 'double', 'core_section', 'section_length', array ( 0 =>  array ( 'table' => 'curation_section_split', 'on' => 'geology_lithology.section_split_id = curation_section_split.id', ), 1 =>  array ( 'table' => 'core_section', 'on' => 'curation_section_split.section_id = core_section.id', ), ));
    }

    protected function addQuerySearchAttributes($query) {
        $joins = [];
        $this->addQuerySearchAttribute($query, 'program_id', 'project_expedition', 'project_site.expedition_id', 'project_expedition.id', $joins);
        $this->addQuerySearchAttribute($query, 'expedition_id', 'project_site', 'project_hole.site_id', 'project_site.id', $joins);
        $this->addQuerySearchAttribute($query, 'site_id', 'project_hole', 'core_core.hole_id', 'project_hole.id', $joins);
        $this->addQuerySearchAttribute($query, 'hole_id', 'core_core', 'core_section.core_id', 'core_core.id', $joins);
        $this->addQuerySearchAttribute($query, 'core_id', 'core_section', 'curation_section_split.section_id', 'core_section.id', $joins);
        $this->addQuerySearchAttribute($query, 'section_id', 'curation_section_split', 'geology_lithology.section_split_id', 'curation_section_split.id', $joins);
        $this->createSearchJoins($query, $joins);
    }

}