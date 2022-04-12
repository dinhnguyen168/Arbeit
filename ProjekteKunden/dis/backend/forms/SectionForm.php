<?php

namespace app\forms;

use Yii;

/**
* @inheritdoc
*/
class SectionForm extends \app\models\CoreSection
{

    const FORM_NAME = 'section';

    public function scenarios() {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_DEFAULT] = ['section', 'curator', 'section_length', 'curated_length', 'core_catcher', 'section_condition', 'comment', 'combined_id', 'mcd_offset', 'comment_depth', 'core_id'];
        return $scenarios;
    }

    /**
    * {@inheritdoc}
    */
    public function attributeLabels()
    {
      return [
        'section' => Yii::t('app', 'Section Number'),
            'curator' => Yii::t('app', 'Curator'),
            'top_depth' => Yii::t('app', 'Section Top Depth [mbs] or [mbsf]'),
            'section_length' => Yii::t('app', 'Section Length [m]'),
            'curated_length' => Yii::t('app', 'Curated Length [m]'),
            'bottom_depth' => Yii::t('app', 'Section Bottom Depth [mbs] or [mbsf]'),
            'core_catcher' => Yii::t('app', 'Core Catcher'),
            'section_condition' => Yii::t('app', 'Section Condition'),
            'section_split_exist' => Yii::t('app', 'Do section split exist?'),
            'comment' => Yii::t('app', 'Additional Information'),
            'combined_id' => Yii::t('app', 'Combined Id'),
            'mcd_offset' => Yii::t('app', 'MCD Offset [m]'),
            'mcd_top_depth' => Yii::t('app', 'MCD Top Depth [mcd]'),
            'mcd_bottom_depth' => Yii::t('app', 'MCD Bottom Depth [mcd]'),
            'comment_depth' => Yii::t('app', 'Additional Information'),
          ];
    }

}
