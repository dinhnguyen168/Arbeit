<?php

namespace app\forms;

use Yii;

/**
* @inheritdoc
*/
class CuttingsForm extends \app\models\CurationCuttings
{

    const FORM_NAME = 'cuttings';

    public function scenarios() {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_DEFAULT] = ['igsn', 'cuttings_combined_id', 'curator', 'sampling_datetime', 'drillers_sieve', 'comment_drillers', 'top_depth', 'bottom_depth', 'average_depth', 'sample_weight', 'inferred_lithology', 'sorting', 'max_diameter_rock_clasts', 'ratio_rock_clasts', 'petrology', 'color_munsell', 'shape_clasts', 'minerals', 'fossiles', 'comment_fossiles', 'comment', 'hole_id'];
        return $scenarios;
    }

    /**
    * {@inheritdoc}
    */
    public function attributeLabels()
    {
      return [
        'igsn' => Yii::t('app', 'IGSN'),
            'cuttings_combined_id' => Yii::t('app', 'Cuttings Combined ID'),
            'curator' => Yii::t('app', 'Curator'),
            'sampling_datetime' => Yii::t('app', 'Sampling Datetime'),
            'drillers_sieve' => Yii::t('app', 'Drillers Sieve [mm]'),
            'comment_drillers' => Yii::t('app', 'Additional Information'),
            'top_depth' => Yii::t('app', 'Top Depth [mbs]'),
            'bottom_depth' => Yii::t('app', 'Bottom Depth [mbs]'),
            'average_depth' => Yii::t('app', 'Average Depth [mbs]'),
            'sample_weight' => Yii::t('app', 'Sample Weight [g]'),
            'inferred_lithology' => Yii::t('app', 'Inferred Lithology'),
            'sorting' => Yii::t('app', 'Sorting of the Material'),
            'max_diameter_rock_clasts' => Yii::t('app', 'Maximum Grain Size [mm]'),
            'ratio_rock_clasts' => Yii::t('app', 'Percentage of Rock Clasts [%]'),
            'petrology' => Yii::t('app', 'Petrology of Clasts/Cuttings'),
            'color_munsell' => Yii::t('app', 'Munsell Color of Clasts'),
            'shape_clasts' => Yii::t('app', 'Shape of Clasts'),
            'minerals' => Yii::t('app', 'Major Mineral Composition'),
            'fossiles' => Yii::t('app', 'Macro Fossiles'),
            'comment_fossiles' => Yii::t('app', 'Description Fossiles/Plan Remains'),
            'comment' => Yii::t('app', 'Further Description of Cuttings'),
          ];
    }

}
