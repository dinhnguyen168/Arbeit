<?php

namespace app\forms;

use Yii;

/**
* @inheritdoc
*/
class RepositoryForm extends \app\models\ContactRepository
{

    const FORM_NAME = 'repository';

    public function scenarios() {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_DEFAULT] = ['repository_name', 'repository_name_abbreviation', 'organisation_name', 'department', 'street', 'city', 'postal_code', 'state', 'country', 'comment', 'contact_person', 'contact_email', 'contact_phone', 'website'];
        return $scenarios;
    }

    /**
    * {@inheritdoc}
    */
    public function attributeLabels()
    {
      return [
        'repository_name' => Yii::t('app', 'Name of Repository'),
            'repository_name_abbreviation' => Yii::t('app', ' Abbreviation of Repository Name'),
            'organisation_name' => Yii::t('app', 'Name of Organisation'),
            'department' => Yii::t('app', 'Department'),
            'street' => Yii::t('app', 'Street'),
            'city' => Yii::t('app', 'City'),
            'postal_code' => Yii::t('app', 'Postal Code'),
            'state' => Yii::t('app', 'State'),
            'country' => Yii::t('app', 'Country'),
            'comment' => Yii::t('app', 'Additional Information'),
            'contact_person' => Yii::t('app', 'Contact Person'),
            'contact_email' => Yii::t('app', 'Contact Email'),
            'contact_phone' => Yii::t('app', 'Contact Phone'),
            'website' => Yii::t('app', 'Website'),
          ];
    }

}
