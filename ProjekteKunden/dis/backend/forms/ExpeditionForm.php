<?php

namespace app\forms;

use Yii;

/**
* @inheritdoc
*/
class ExpeditionForm extends \app\models\ProjectExpedition
{

    const FORM_NAME = 'expedition';

    public function scenarios() {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_DEFAULT] = ['expedition', 'exp_acronym', 'exp_name', 'exp_name_alt', 'start_date', 'end_date', 'chief_scientists', 'contact', 'funding_agency', 'comment', 'country', 'geological_age', 'rock_classification', 'objectives', 'keywords', 'program_id'];
        return $scenarios;
    }

    /**
    * {@inheritdoc}
    */
    public function attributeLabels()
    {
      return [
        'expedition' => Yii::t('app', 'Expedition Code'),
            'exp_acronym' => Yii::t('app', 'Expedition Acronym'),
            'exp_name' => Yii::t('app', 'Full Expedition/Project Name'),
            'exp_name_alt' => Yii::t('app', 'Alternative Name of Expedition'),
            'start_date' => Yii::t('app', 'Start of Expedition'),
            'end_date' => Yii::t('app', 'End of Expedition'),
            'chief_scientists' => Yii::t('app', 'List of Chief Scientists'),
            'contact' => Yii::t('app', 'Contact Person\'s Email'),
            'funding_agency' => Yii::t('app', 'Funding Agencies'),
            'comment' => Yii::t('app', 'Additional Information'),
            'country' => Yii::t('app', 'Country'),
            'geological_age' => Yii::t('app', 'Geological Age'),
            'rock_classification' => Yii::t('app', 'Rock Classification'),
            'objectives' => Yii::t('app', 'Objectives of the Expedition/Project'),
            'keywords' => Yii::t('app', 'Keywords'),
          ];
    }

}
