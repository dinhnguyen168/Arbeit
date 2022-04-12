<?php

namespace app\forms;

use Yii;

/**
* @inheritdoc
*/
class CoreboxForm extends \app\models\CurationCorebox
{

    const FORM_NAME = 'corebox';

    public function scenarios() {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_DEFAULT] = ['corebox', 'corebox_combined_id', 'storage_id', 'comment', 'hole_id'];
        return $scenarios;
    }

    /**
    * {@inheritdoc}
    */
    public function attributeLabels()
    {
      return [
        'corebox' => Yii::t('app', 'Corebox'),
            'corebox_combined_id' => Yii::t('app', 'Corebox Combined ID'),
            'contained_section_splits' => Yii::t('app', 'Contained Section Splits'),
            'storage_id' => Yii::t('app', 'Storage ID'),
            'storage_combined_id' => Yii::t('app', 'Storage'),
            'comment' => Yii::t('app', 'Additional Information'),
          ];
    }

}
