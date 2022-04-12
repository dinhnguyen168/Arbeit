<?php

namespace app\forms;

use Yii;

/**
* @inheritdoc
*/
class SiteForm extends \app\models\ProjectSite
{

    const FORM_NAME = 'site';

    public function scenarios() {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_DEFAULT] = ['combined_id', 'site', 'site_name', 'site_name_alt', 'type_drilling_location', 'geological_age', 'lithology', 'comment', 'city', 'state', 'county', 'country', 'description', 'expedition_id'];
        return $scenarios;
    }

    /**
    * {@inheritdoc}
    */
    public function attributeLabels()
    {
      return [
        'combined_id' => Yii::t('app', 'Combined Id '),
            'site' => Yii::t('app', 'Site Code'),
            'site_name' => Yii::t('app', 'Name of Site'),
            'site_name_alt' => Yii::t('app', 'Alternative Name or Code of Site'),
            'type_drilling_location' => Yii::t('app', 'Type of Drilling Location'),
            'geological_age' => Yii::t('app', 'Geological Age'),
            'lithology' => Yii::t('app', 'Lithology'),
            'comment' => Yii::t('app', 'Additional Information'),
            'city' => Yii::t('app', 'City Nearby Drill Site'),
            'state' => Yii::t('app', 'State'),
            'county' => Yii::t('app', 'County'),
            'country' => Yii::t('app', 'Country '),
            'description' => Yii::t('app', 'Description of Drill Site'),
          ];
    }

}
