<?php

namespace app\forms;

use Yii;

/**
* @inheritdoc
*/
class OrganisationForm extends \app\models\ContactOrganisation
{

    const FORM_NAME = 'organisation';

    public function scenarios() {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_DEFAULT] = ['organisation_name', 'organisation_name_abbreviation', 'department', 'street', 'city', 'postal_code', 'state', 'country', 'website', 'comment'];
        return $scenarios;
    }

    /**
    * {@inheritdoc}
    */
    public function attributeLabels()
    {
      return [
        'organisation_name' => Yii::t('app', 'Name of Organisation '),
            'organisation_name_abbreviation' => Yii::t('app', 'Organisation Name Abbreviation'),
            'department' => Yii::t('app', 'Department'),
            'street' => Yii::t('app', 'Street Address'),
            'city' => Yii::t('app', 'City'),
            'postal_code' => Yii::t('app', 'Postal Code'),
            'state' => Yii::t('app', 'State'),
            'country' => Yii::t('app', 'Country'),
            'website' => Yii::t('app', 'Website'),
            'comment' => Yii::t('app', 'Additional Information'),
          ];
    }

}
