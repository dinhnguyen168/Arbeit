<?php

namespace app\forms;

use Yii;

/**
* @inheritdoc
*/
class PersonForm extends \app\models\ContactPerson
{

    const FORM_NAME = 'person';

    public function scenarios() {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_DEFAULT] = ['role', 'first_name', 'last_name', 'title', 'gender', 'orcid', 'comment', 'person_acronym', 'phone', 'mobile', 'email', 'affiliation'];
        return $scenarios;
    }

    /**
    * {@inheritdoc}
    */
    public function attributeLabels()
    {
      return [
        'role' => Yii::t('app', 'Role in mDIS'),
            'first_name' => Yii::t('app', 'First Name'),
            'last_name' => Yii::t('app', 'Last Name'),
            'full_name' => Yii::t('app', 'Full Name'),
            'title' => Yii::t('app', 'Title'),
            'gender' => Yii::t('app', 'Gender'),
            'orcid' => Yii::t('app', 'Orcid'),
            'comment' => Yii::t('app', 'Additional Information'),
            'person_acronym' => Yii::t('app', 'Person Acronym'),
            'phone' => Yii::t('app', 'Phone'),
            'mobile' => Yii::t('app', 'Mobile'),
            'email' => Yii::t('app', 'Email'),
            'affiliation' => Yii::t('app', 'Affiliation'),
          ];
    }

}
