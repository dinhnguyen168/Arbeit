<?php

namespace app\forms;

use Yii;

/**
* @inheritdoc
*/
class LithologyForm extends \app\models\GeologyLithology
{

    const FORM_NAME = 'lithology';

    public function scenarios() {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_DEFAULT] = ['litho_unit', 'top_depth', 'unit_length', 'bottom_depth', 'curator', 'rock_class', 'rock_type', 'color', 'composition', 'description', 'section_split_id'];
        return $scenarios;
    }

    /**
    * {@inheritdoc}
    */
    public function attributeLabels()
    {
      return [
        'split_combined_id' => Yii::t('app', 'Split Combined ID'),
            'section_length' => Yii::t('app', 'Length of Section [m]'),
            'litho_unit' => Yii::t('app', 'Lithological Unit '),
            'top_depth' => Yii::t('app', 'Unit Top Depth [cm]'),
            'unit_length' => Yii::t('app', 'Unit Length [cm]'),
            'bottom_depth' => Yii::t('app', 'Unit Bottom Depth [cm]'),
            'curator' => Yii::t('app', 'Curator'),
            'rock_class' => Yii::t('app', 'Rock Class'),
            'rock_type' => Yii::t('app', 'Type of Lithology'),
            'color' => Yii::t('app', 'Primary Color'),
            'composition' => Yii::t('app', 'Composition'),
            'description' => Yii::t('app', 'Description'),
            'mcd_bottom_depth_unit' => Yii::t('app', 'MCD Bottom Depth Unit [m]'),
            'mcd_top_depth_unit' => Yii::t('app', 'MCD Top Depth Unit [m]'),
          ];
    }

}
