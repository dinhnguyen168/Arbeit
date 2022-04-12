<?php

namespace app\forms;

use Yii;

/**
* @inheritdoc
*/
class LithologicalUnitForm extends \app\models\GeologyLithoUnits
{

    const FORM_NAME = 'lithological-unit';

    public function scenarios() {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_DEFAULT] = ['lithological_unit', 'lithological_unit_name', 'description', 'expedition_id'];
        return $scenarios;
    }

    /**
    * {@inheritdoc}
    */
    public function attributeLabels()
    {
      return [
        'lithological_unit' => Yii::t('app', 'Lithological Unit'),
            'lithological_unit_name' => Yii::t('app', 'Full Lithological Unit Name'),
            'description' => Yii::t('app', 'Description'),
          ];
    }

}
