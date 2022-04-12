<?php

namespace app\forms;

use Yii;

/**
* @inheritdoc
*/
class CoreForm extends \app\models\CoreCore
{

    const FORM_NAME = 'core';

    public function scenarios() {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_DEFAULT] = ['core', 'combined_id', 'core_ondeck', 'curator', 'drillers_top_depth', 'drilled_length', 'section_count', 'core_recovery', 'core_loss_reason', 'continuity', 'comment', 'core_type', 'core_diameter', 'core_oriented', 'rqd', 'comment_depth', 'igsn', 'comment_identifier', 'barrel_length', 'bit_size', 'fluid_type', 'comment_drilling', 'hole_id'];
        return $scenarios;
    }

    /**
    * {@inheritdoc}
    */
    public function attributeLabels()
    {
      return [
        'core' => Yii::t('app', 'Core Number'),
            'combined_id' => Yii::t('app', 'Combined Id'),
            'core_ondeck' => Yii::t('app', 'Core on Deck (CoD)'),
            'curator' => Yii::t('app', 'Curator'),
            'drillers_top_depth' => Yii::t('app', 'Drillers Top Depth [mbrf]'),
            'drilled_length' => Yii::t('app', 'Drilled Length [m]'),
            'drillers_bottom_depth' => Yii::t('app', 'Drillers Bottom Depth [mbrf]'),
            'section_count' => Yii::t('app', 'Number of Core Sections '),
            'core_recovery' => Yii::t('app', 'Core Recovery [m]'),
            'core_recovery_pc' => Yii::t('app', 'Core Recovery [%]'),
            'core_loss_reason' => Yii::t('app', 'Core Loss Reason'),
            'continuity' => Yii::t('app', 'Continuity of Cores'),
            'comment' => Yii::t('app', 'Additional Information'),
            'section_split_exist' => Yii::t('app', 'Do splits exist?'),
            'core_type' => Yii::t('app', 'Type of Coring Tool'),
            'core_diameter' => Yii::t('app', 'Core Diameter [mm]'),
            'core_oriented' => Yii::t('app', 'Core Oriented ?'),
            'rqd' => Yii::t('app', 'Rock Quality Designation (RQD)'),
            'core_top_depth' => Yii::t('app', 'Core Top Depth [mbs] or [mbsf]'),
            'core_bottom_depth' => Yii::t('app', 'Core Bottom Depth [mbs] or [mbsf]'),
            'comment_depth' => Yii::t('app', 'Addtional Information'),
            'igsn' => Yii::t('app', 'IGSN'),
            'comment_identifier' => Yii::t('app', 'Additional Information '),
            'barrel_length' => Yii::t('app', 'Barrel Length [m]'),
            'bit_size' => Yii::t('app', 'Bit Size'),
            'fluid_type' => Yii::t('app', 'Drilling Fluid Type'),
            'comment_drilling' => Yii::t('app', 'Additional Information'),
          ];
    }

}
