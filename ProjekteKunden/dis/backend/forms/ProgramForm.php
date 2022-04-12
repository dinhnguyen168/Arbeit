<?php

namespace app\forms;

use Yii;

/**
* @inheritdoc
*/
class ProgramForm extends \app\models\ProjectProgram
{

    const FORM_NAME = 'program';

    public function scenarios() {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_DEFAULT] = ['program_name', 'program_acronym', 'comment'];
        return $scenarios;
    }

    /**
    * {@inheritdoc}
    */
    public function attributeLabels()
    {
      return [
        'program_name' => Yii::t('app', 'Program Name'),
            'program_acronym' => Yii::t('app', 'Program Acronym'),
            'comment' => Yii::t('app', 'Additional Information'),
          ];
    }

}
