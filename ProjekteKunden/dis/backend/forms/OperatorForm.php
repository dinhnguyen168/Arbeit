<?php

namespace app\forms;

use Yii;

/**
* @inheritdoc
*/
class OperatorForm extends \app\models\ContactOperator
{

    const FORM_NAME = 'operator';

    public function scenarios() {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_DEFAULT] = ['operator_name', 'street', 'city', 'postal_code', 'state', 'country', 'comment', 'contact_phone', 'contact_email', 'website'];
        return $scenarios;
    }

    /**
    * {@inheritdoc}
    */
    public function attributeLabels()
    {
      return [
        'operator_name' => Yii::t('app', 'Name of Operator'),
            'street' => Yii::t('app', 'Street'),
            'city' => Yii::t('app', 'City'),
            'postal_code' => Yii::t('app', 'Postal Code'),
            'state' => Yii::t('app', 'State'),
            'country' => Yii::t('app', 'Country'),
            'comment' => Yii::t('app', 'Additional Information'),
            'contact_phone' => Yii::t('app', 'Contact Phone'),
            'contact_email' => Yii::t('app', 'Contact Email'),
            'website' => Yii::t('app', 'Website'),
          ];
    }

}
