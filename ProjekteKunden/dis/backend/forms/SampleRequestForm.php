<?php

namespace app\forms;

use Yii;

/**
* @inheritdoc
*/
class SampleRequestForm extends \app\models\CurationSampleRequest
{

    const FORM_NAME = 'sample-request';

    public function scenarios() {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_DEFAULT] = ['request_no', 'request_part', 'request_combined_id', 'sample_material', 'hole_combined_id', 'number_samples', 'sample_size', 'sample_size_unit', 'split_fraction_requested', 'purpose', 'destructive', 'project_phase', 'scientist_1', 'scientist_2', 'scientist_3', 'comment', 'date_submission', 'curator', 'date_approval', 'approved_by', 'date_completion', 'comment_administration', 'expedition_id'];
        return $scenarios;
    }

    /**
    * {@inheritdoc}
    */
    public function attributeLabels()
    {
      return [
        'exp_acronym' => Yii::t('app', 'Expedition Acronym'),
            'request_no' => Yii::t('app', 'Request Number'),
            'request_part' => Yii::t('app', 'Request Part'),
            'request_combined_id' => Yii::t('app', 'Request Combined ID'),
            'sample_material' => Yii::t('app', 'Sample Material Requested'),
            'hole_combined_id' => Yii::t('app', ' List Combined ID\'s  of Holes to be Sampled'),
            'number_samples' => Yii::t('app', 'Number of Samples'),
            'sample_size' => Yii::t('app', 'Size of Sample'),
            'sample_size_unit' => Yii::t('app', 'Unit of Sample Size'),
            'split_fraction_requested' => Yii::t('app', 'Requested Fraction of Section Split [%]'),
            'purpose' => Yii::t('app', 'Purpose / Usage'),
            'destructive' => Yii::t('app', 'Destructive Usage?'),
            'project_phase' => Yii::t('app', 'Project Phase'),
            'scientist_1' => Yii::t('app', 'Scientist 1'),
            'scientist_2' => Yii::t('app', 'Scientist 2'),
            'scientist_3' => Yii::t('app', 'Scientist 3'),
            'comment' => Yii::t('app', 'Additional Information'),
            'date_submission' => Yii::t('app', 'Date of Submission'),
            'curator' => Yii::t('app', 'Curator'),
            'date_approval' => Yii::t('app', 'Date of Approval'),
            'approved_by' => Yii::t('app', 'By whom was the request approved?'),
            'date_completion' => Yii::t('app', 'Request Completed'),
            'comment_administration' => Yii::t('app', 'Additional Information'),
          ];
    }

}
