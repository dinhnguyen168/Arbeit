<?php namespace templates;

use app\models\ArchiveFile;

class ComponentTest extends \Codeception\Test\Unit
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

    protected $modelNames = [
        'ArchiveFile',
        'CoreCore',
        'CoreSection',
        'CurationCorebox',
        'CurationSample',
        'CurationSectionSplit',
        'ProjectExpedition',
        'ProjectHole',
        'ProjectProgram',
        'ProjectSite'
    ];

    // tests
    public function testGetAllModels()
    {
        $allModels = \Yii::$app->templates->getModelTemplates();
        foreach ($this->modelNames as $modelName) {
            self::assertArrayHasKey($modelName, $allModels);
        }

        $fm = ArchiveFile::getFormFilters();
        self::assertEquals([
            "model" => "ProjectSite",
            "value" => "id",
            "text" => "site",
            "ref" => "site_id",
            "require" => [ "value" => "expedition", "as" => "expedition_id"]
        ],
        $fm['site']);
    }

    public function testValidModelTemplates()
    {

        $templates = \Yii::$app->templates;
        foreach ($templates->modelTemplates as $template) {
            $valid = $template->validate();
            // expect_that($valid);
            self::assertTrue($valid, "Model template '" . $template->name . "' is not valid: \n". print_r($template->errors, true));
        }
    }

    public function testValidFormTemplates ()
    {

        $templates = \Yii::$app->templates;
        foreach ($templates->formTemplates as $template) {
            $valid = $template->validate();
            // expect_that($valid);
            self::assertTrue ($valid, "Form template '" . $template->name . "' is not valid: ". print_r($template->errors, true));
        }
    }

}
