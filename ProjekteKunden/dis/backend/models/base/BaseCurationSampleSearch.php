<?php

namespace app\models\base;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use \app\models\CurationSample;

/**
* This is the search base model class for model "CurationSample".
* DO NOT EDIT THIS FILE.
*/
abstract class BaseCurationSampleSearch extends CurationSample
{
    use \app\models\core\SearchModelTrait;

    public $program_id;
    public $expedition_id;
    public $site_id;
    public $hole_id;
    public $core_id;
    public $section_id;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'section_split_id', 'sample_request_id', 'sample_combined_id', 'igsn', 'sample_date', 'top', 'sample_length', 'split_fraction_taken', 'sample_size', 'sample_size_unit', 'sample_material', 'curator', 'comment', 'purpose', 'combined_id', 'program_id', 'expedition_id', 'site_id', 'hole_id', 'core_id', 'section_id'],'safe'],
        ];
    }

    public function getExtraSortAttributes () {
        return [
        ];
    }

    protected function addQueryColumns($query) {
        $this->addQueryColumn($query, 'id', 'number');
        $this->addQueryColumn($query, 'section_split_id', 'number');
        $this->addQueryColumn($query, 'sample_request_id', 'string');
        $this->addQueryColumn($query, 'sample_combined_id', 'string');
        $this->addQueryColumn($query, 'igsn', 'string');
        $this->addQueryColumn($query, 'sample_date', 'string');
        $this->addQueryColumn($query, 'top', 'number');
        $this->addQueryColumn($query, 'sample_length', 'number');
        $this->addQueryColumn($query, 'split_fraction_taken', 'number');
        $this->addQueryColumn($query, 'sample_size', 'number');
        $this->addQueryColumn($query, 'sample_size_unit', 'string');
        $this->addQueryColumn($query, 'sample_material', 'string');
        $this->addQueryColumn($query, 'curator', 'string');
        $this->addQueryColumn($query, 'comment', 'string');
        $this->addQueryColumn($query, 'purpose', 'string');
        $this->addQueryColumn($query, 'combined_id', 'string');
    }

    protected function addQuerySearchAttributes($query) {
        $joins = [];
        $this->addQuerySearchAttribute($query, 'program_id', 'project_expedition', 'project_site.expedition_id', 'project_expedition.id', $joins);
        $this->addQuerySearchAttribute($query, 'expedition_id', 'project_site', 'project_hole.site_id', 'project_site.id', $joins);
        $this->addQuerySearchAttribute($query, 'site_id', 'project_hole', 'core_core.hole_id', 'project_hole.id', $joins);
        $this->addQuerySearchAttribute($query, 'hole_id', 'core_core', 'core_section.core_id', 'core_core.id', $joins);
        $this->addQuerySearchAttribute($query, 'core_id', 'core_section', 'curation_section_split.section_id', 'core_section.id', $joins);
        $this->addQuerySearchAttribute($query, 'section_id', 'curation_section_split', 'curation_sample.section_split_id', 'curation_section_split.id', $joins);
        $this->createSearchJoins($query, $joins);
    }

}