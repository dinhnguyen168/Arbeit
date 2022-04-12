<?php

namespace app\forms;

use Yii;

/**
* @inheritdoc
*/
class SplitForm extends \app\models\CurationSectionSplit
{

    const FORM_NAME = 'split';

    public function scenarios() {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_DEFAULT] = ['type', 'percent', 'still_exists', 'sampleable', 'curator', 'comment', 'combined_id', 'igsn', 'comment_identifier', 'corebox_slot', 'corebox_position', 'storage_combined_id', 'comment_storage', 'crate_name', 'weight', 'measurement_exists', 'section_id'];
        return $scenarios;
    }

    /**
    * {@inheritdoc}
    */
    public function attributeLabels()
    {
      return [
        'type' => Yii::t('app', 'Split Type'),
            'percent' => Yii::t('app', 'Percent of Wholeround [%]'),
            'still_exists' => Yii::t('app', 'Does Split still exist?'),
            'origin_split_combined_id' => Yii::t('app', 'Origin Split Combined ID'),
            'sampleable' => Yii::t('app', 'Sampling allowed?'),
            'curated_length' => Yii::t('app', 'Curated Length [cm]'),
            'curator' => Yii::t('app', 'Curator'),
            'comment' => Yii::t('app', 'Additional Information'),
            'combined_id' => Yii::t('app', 'Combined ID'),
            'igsn' => Yii::t('app', 'IGSN'),
            'comment_identifier' => Yii::t('app', 'Additional Information'),
            'mcd_top_depth' => Yii::t('app', 'MCD Top Depth [mbs]'),
            'mcd_bottom_depth' => Yii::t('app', 'MCD Bottom Depth [cm]'),
            'corebox_slot' => Yii::t('app', 'Slot'),
            'corebox_position' => Yii::t('app', 'Position'),
            'storage_combined_id' => Yii::t('app', 'Storage Combined ID'),
            'comment_storage' => Yii::t('app', 'Comment Storage'),
            'crate_name' => Yii::t('app', 'Crate Name'),
            'weight' => Yii::t('app', 'Weight [kg]'),
            'measurement_exists' => Yii::t('app', 'Measurement Exists'),
          ];
    }

}
