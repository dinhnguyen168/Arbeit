<?php

namespace app\forms;

use Yii;

/**
* @inheritdoc
*/
class StorageForm extends \app\models\CurationStorage
{

    const FORM_NAME = 'storage';

    public function scenarios() {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_DEFAULT] = ['storage', 'parent_id', 'combined_id', 'type', 'comment', 'exp_acronym', 'parent_id'];
        return $scenarios;
    }

    /**
    * {@inheritdoc}
    */
    public function attributeLabels()
    {
      return [
        'storage' => Yii::t('app', 'Storage'),
            'parent_id' => Yii::t('app', 'parent'),
            'combined_id' => Yii::t('app', 'Combined Id'),
            'type' => Yii::t('app', 'Type'),
            'comment' => Yii::t('app', 'Additional Information'),
            'exp_acronym' => Yii::t('app', 'Expedition Acronym'),
          ];
    }

}
