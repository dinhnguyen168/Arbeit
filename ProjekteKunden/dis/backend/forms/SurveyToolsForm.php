<?php

namespace app\forms;

use Yii;

/**
* @inheritdoc
*/
class SurveyToolsForm extends \app\models\AuxiliaryTablesSurveyTools
{

    const FORM_NAME = 'survey-tools';

    public function scenarios() {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_DEFAULT] = ['method_short', 'tool_name', 'organisaton_name_abbreviation', 'comment'];
        return $scenarios;
    }

    /**
    * {@inheritdoc}
    */
    public function attributeLabels()
    {
      return [
        'method_short' => Yii::t('app', 'Method'),
            'tool_name' => Yii::t('app', 'Name of Tool'),
            'organisaton_name_abbreviation' => Yii::t('app', 'Abbreviation of Organisaton'),
            'comment' => Yii::t('app', 'Additional Information'),
          ];
    }

}
