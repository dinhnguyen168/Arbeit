<?php namespace templates\models;

use app\components\templates\BaseTemplate;
use app\components\templates\FormTemplate;

class FormTemplateTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    protected function _before()
    {
    }

    protected function _after()
    {
    }

    // tests
    public function testBasicValidation()
    {
        $formTemplate = new FormTemplate([
            'scenario' => BaseTemplate::SCENARIO_CREATE
        ]);
        $templateArray = [
            "name" => "hole-test",
            "dataModel" => "ProjectHole",
            "fields" => [
                [
                    "name" => "start_date",
                    "label" => "Start Date",
                    "description" => "Start of Drilling Operations",
                    "validators" => [
                        ["type" => "required"]
                    ],
                    "formInput" => [
                        "type" => "datetime",
                        "disabled" => false,
                        "calculate" => ""
                    ],
                    "group" => "-group1",
                    "order" => 0
                ]
            ],
            "availableSubForms" => [],
            "availableSupForms" => []
        ];
        $formTemplate->load($templateArray, '');
        expect_that($formTemplate->validate());
    }

    //test
    public function testRenameFormTemplate () {
        $formTemplate = \Yii::$app->templates->getFormTemplate('hole');
        expect_that($formTemplate);
        expect_that($formTemplate->rename('hole-new-name'));
        $newFormTemplate = \Yii::$app->templates->getFormTemplate('hole-new-name');
        expect_that($newFormTemplate);
        expect_that($newFormTemplate->rename('hole'));
    }
}
