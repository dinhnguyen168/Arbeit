<?php

namespace app\forms;

use Yii;

/**
* @inheritdoc
*/
class SampleForm extends \app\models\CurationSample
{

    const FORM_NAME = 'sample';

    public function scenarios() {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_DEFAULT] = ['sample_request_id', 'sample_combined_id', 'igsn', 'sample_date', 'sample_material', 'top', 'sample_length', 'sample_size', 'sample_size_unit', 'split_fraction_taken', 'curator', 'purpose', 'comment', 'section_split_id'];
        return $scenarios;
    }

    /**
    * {@inheritdoc}
    */
    public function attributeLabels()
    {
      return [
        'sample_request_id' => Yii::t('app', 'Sample Request ID'),
            'sample_combined_id' => Yii::t('app', 'Sample Combined ID'),
            'igsn' => Yii::t('app', 'IGSN'),
            'sample_date' => Yii::t('app', 'Date of Sampling'),
            'sample_material' => Yii::t('app', 'Sample Material'),
            'top' => Yii::t('app', 'Sample Top [cm]'),
            'sample_length' => Yii::t('app', 'Sample Length [cm]'),
            'bottom' => Yii::t('app', 'Sample Bottom [cm]'),
            'sample_size' => Yii::t('app', 'Sample Size'),
            'sample_size_unit' => Yii::t('app', 'Sample Size Unit'),
            'split_fraction_taken' => Yii::t('app', 'Fraction of Section Split [%]'),
            'mcd_top_depth' => Yii::t('app', 'MCD Sample Top Depth [mcd]'),
            'mcd_bottom_depth' => Yii::t('app', 'MCD Sample Bottom Depth [mcd]'),
            'csfb_top_depth' => Yii::t('app', 'CSF_B Sample Top Depth [m]'),
            'csfb_bottom_depth' => Yii::t('app', 'CSF_B Sample Bottom Depth [m]'),
            'curator' => Yii::t('app', 'Curator'),
            'purpose' => Yii::t('app', 'Purpose/Usage'),
            'comment' => Yii::t('app', 'Additional Information'),
          ];
    }

}
