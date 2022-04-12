<?php namespace models;

use app\behaviors\template\LeadingZerosBehavior;
use app\models\core\Base;
use yii\db\ActiveRecord;

class LeadingZerosBehaviorTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;
    protected $owner;
    protected $behavior;


    protected function _before()
    {
        $this->owner = new LeadingZerosBehaviorTestModel();

    }

    protected function _after()
    {
    }

    protected function getModelTemplateJson() {
        return <<<'EOD'
{
    "module": "None",
    "name": "LeadingZerosBehaviorTest",
    "table": "None",
    "columns": {
        "testColumn": {
            "name": "testColumn",
            "type": "string",
            "size": 25,
            "required": false,
            "primaryKey": false,
            "autoInc": false,
            "label": "testColumn",
            "description": "",
            "validator": "",
            "validatorMessage": "",
            "unit": "",
            "selectListName": "",
            "calculate": "",
            "defaultValue": ""
        }
    },
    "indices": {},
    "foreignkeys": {},
    "behaviors": [],
    "createdAt": 0,
    "modifiedAt": 0,
    "generatedAt": 0,
    "fullName": "NoneLeadingZerosBehaviorTest"
}
EOD;

    }

    // tests
    public function testValidateParams()
    {
        $modelTemplate = new \app\components\templates\ModelTemplate();
        $modelTemplateColumn = new \app\components\templates\ModelTemplateColumn([
            'name' => 'testColumn'
        ]);
        $modelTemplate->columns[] = $modelTemplateColumn;
        $errors = [];


        self::assertTrue(LeadingZerosBehavior::validateParametersValues($modelTemplate, [
            'column' => 'testColumn',
            'length' => '3'
        ], $errors));


        self::assertFalse(LeadingZerosBehavior::validateParametersValues($modelTemplate, [
            'column' => '',
            'length' => '3'
        ], $errors));

/*
        self::assertFalse(LeadingZerosBehavior::validateParametersValues($modelTemplate, [
            'column' => 'notExistingColumn',
            'length' => '3'
        ], $errors));
*/

        self::assertFalse(LeadingZerosBehavior::validateParametersValues($modelTemplate, [
            'column' => 'testColumn',
            'length' => ''
        ], $errors));

        self::assertFalse(LeadingZerosBehavior::validateParametersValues($modelTemplate, [
            'column' => 'testColumn',
            'length' => '1'
        ], $errors));

  }

  protected function tryValue ($expected, $value) {

      $event = new \yii\base\ModelEvent(['name' => ActiveRecord::EVENT_BEFORE_UPDATE]);
      $this->owner->testColumn = $value;
      $this->behavior->evaluateAttributes($event);
      self::assertSame($expected, $this->owner->testColumn);
  }

    // tests
    public function testTestValues()
    {
        $this->behavior = \Yii::createObject([
            'class' => 'app\behaviors\template\LeadingZerosBehavior',
            'owner' => $this->owner,
            'column' => 'testColumn',
            'length' => '4',
            'skipUpdateOnClean' => false
        ]);

        $this->tryValue('0000', '0');
        $this->tryValue('0023', '23');
        $this->tryValue('2345', '2345');
        $this->tryValue('34567', '34567');
        $this->tryValue('a', 'a');
        $this->tryValue('0023a', '23a');
        $this->tryValue('0023.456', '23.456');
        $this->tryValue('0023,456', '23,456');
        $this->tryValue('0000', 0);
        $this->tryValue('0023', 23);
    }

}

class LeadingZerosBehaviorTestModel extends \yii\base\Model {

    public $testColumn = 0;

    public function rules() {
        return [['testColumn', safe]];
    }

}
