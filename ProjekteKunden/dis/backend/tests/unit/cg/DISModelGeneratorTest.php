<?php namespace cg;

use app\components\templates\BaseTemplate;
use app\components\templates\ModelTemplate;
use app\migrations\Migration;
use app\modules\cg\generators\DISModel\Generator;

class DISModelGeneratorTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    protected $templateArray = [
        "module" => "Project",
        "name" => "TestHole",
        "table" => "project_test_hole",
        "importTable" => null,
        "parentModel" => "ProjectSite",
        "columns" => [
            "id" => [
                "name" => "id",
                "type" => "integer",
                "size" => 11,
                "required" => false,
                "primaryKey" => true,
                "autoInc" => true,
                "label" => "ID",

            ],
            "site_id" => [
                "name" => "site_id",
                "type" => "integer",
                "size" => 11,
                "required" => true,
                "primaryKey" => false,
                "autoInc" => false,
                "label" => "Site",
            ],
            "title" => [
                "name" => "title",
                "type" => "string",
                "size" => null,
                "required" => false,
                "primaryKey" => false,
                "autoInc" => false,
                "label" => "Title",
            ],
            "pseudo_site_name" => [
                "name" => "pseudo_site_name",
                "type" => "pseudo",
                "size" => null,
                "required" => false,
                "primaryKey" => false,
                "autoInc" => false,
                "label" => "Pseudo Dot Site Name",
                'pseudoCalc' => 'parent.site_name',
            ],
            "pseudo_code_site_name" => [
                "name" => "pseudo_code_site_name",
                "type" => "pseudo",
                "size" => null,
                "required" => false,
                "primaryKey" => false,
                "autoInc" => false,
                "label" => "Pseudo Code Site Name",
                'pseudoCalc' => '$model->parent->site_name',
            ],
        ],
        "indices" => [
            "pk_id" => [
                "name" => "pk_id",
                "type" => "PRIMARY",
                "columns" => ["id"]
            ]
        ],
        "relations" => [
            "project_test_hole__project_site__parent" => [
                "name" => "project_test_hole__project_site__parent",
                "foreignTable" => "project_site",
                "localColumns" => ["site_id"],
                "foreignColumns" => ["id"],
            ]
        ],
        "behaviors" => [],
        "createdAt" => 1604663034,
        "modifiedAt" => 1604663034,
        "generatedAt" => null,
        "fullName" => "ProjectTestHole",
    ];

    /**
     * @var ModelTemplate ModelTemplate
     */
    protected $modelTemplate = null;

    protected function _before()
    {
        // create app with db fixture
        $this->modelTemplate = new ModelTemplate([
            'scenario' => BaseTemplate::SCENARIO_CREATE
        ]);
        $this->modelTemplate->load($this->templateArray, '');
        $valid = $this->modelTemplate->validate();
        self::assertTrue($valid, "Errors in validating modelTemplate: " . print_r($this->modelTemplate->errors, true));

        $this->modelTemplate->save();
        $migration = new Migration();
        self::assertTrue($this->modelTemplate->generateTable($migration));
        $this->modelTemplate->generateForeignKeys($migration);
//        \Yii::$app->db->pdo->exec();
    }

    protected function _after()
    {
        // destroy app
        $this->modelTemplate->dropTableIfEmpty();
        unlink($this->modelTemplate->getFilePath());
    }

    //test
    public function testGeneratesPseudoColumnsGettersAndAttributes() {
        $modelGenerator = new Generator();
        $modelGenerator->templateName = 'ProjectTestHole';
        $files = $modelGenerator->generate();
        expect_that(count($files) == 4);
        $baseClassPath = $this->modelTemplate->getBaseClassFilePath();
        $baseClassContent = current(array_filter($files, function ($file) use ($baseClassPath){
            return $file->path == $baseClassPath;
        }))->content;
        self::assertStringContainsString("'pseudo_site_name' => function(\$model) { return (\$this->parent ? \$this->parent->site_name : null); },", $baseClassContent);
        self::assertStringContainsString("'pseudo_site_name' => Yii::t('app', 'Pseudo Dot Site Name'),", $baseClassContent);

        $baseClassContent = preg_replace("/\\n\\s*/", "", $baseClassContent);
        /**
         * check if generated class has getter for dot separated pseudo calc
         */
        self::assertStringContainsString("public function getPseudo_site_name(){return (\$this->parent ? \$this->parent->site_name : null);}", $baseClassContent);
        /**
         * check if generated class has getter for code pseudo calc
         * public function getPseudo_code_site_name()\n
         * {\n
         *      $model = $this;\n
         *       return $model->parent->model;\n
         * }\n
         */
        self::assertStringContainsString("public function getPseudo_code_site_name(){\$model = \$this;return \$model->parent->site_name;}", $baseClassContent);
    }
    // tests
    public function testGeneratesCodeToFilterAndSortPseudoColumns()
    {
        $modelGenerator = new Generator();
        $modelGenerator->templateName = 'ProjectTestHole';
        $files = $modelGenerator->generate();
        expect_that(count($files) == 4);
        $baseClassPath = $this->modelTemplate->getBaseClassFilePath();
        $baseClassContent = current(array_filter($files, function ($file) use ($baseClassPath){
            return $file->path == $baseClassPath;
        }))->content;
        self::assertStringContainsString("'pseudo_site_name' => function(\$model) { return (\$this->parent ? \$this->parent->site_name : null); },", $baseClassContent);
        $baseSearchClassPath = $this->modelTemplate->getBaseSearchClassFilePath();
        $baseSearchClassContent = current(array_filter($files, function ($file) use ($baseSearchClassPath){
            return $file->path == $baseSearchClassPath;
        }))->content;
        self::assertStringContainsString("\$this->addQueryPseudoColumn(\$query, 'pseudo_site_name', 'string', 'project_site', 'site_name', array ( 0 =>  array ( 'table' => 'project_site', 'on' => 'project_test_hole.site_id = project_site.id', ), ));", $baseSearchClassContent);
        self::assertStringContainsString("'asc' => ['project_site.site_name' => SORT_ASC]", $baseSearchClassContent);
        self::assertStringContainsString("'desc' => ['project_site.site_name' => SORT_DESC]", $baseSearchClassContent);
    }

    public function testExcludeCodePseudoColumnsFromSearchClass()
    {
        $modelGenerator = new Generator();
        $modelGenerator->templateName = 'ProjectTestHole';
        $files = $modelGenerator->generate();
        expect_that(count($files) == 4);
        $baseSearchClassPath = $this->modelTemplate->getBaseSearchClassFilePath();
        $baseSearchClassContent = current(array_filter($files, function ($file) use ($baseSearchClassPath){
            return $file->path == $baseSearchClassPath;
        }))->content;
        // [['id', 'site_id', 'title', 'pseudo_site_name', 'pseudo_code_site_name', 'program_id', 'expedition_id'],'safe']
        self::assertStringContainsString("[['id', 'site_id', 'title', 'pseudo_site_name', 'program_id', 'expedition_id'],'safe']", $baseSearchClassContent);
    }

    public function testSpecializedSourceCodes()
    {
        $modelGenerator = new Generator();
        $modelGenerator->templateName = 'CoreCore';
        $files = $modelGenerator->generate();
        $baseClassContent = current(array_filter($files, function ($file) {
            return preg_match("/BaseCoreCore/", $file->path);
        }))->content;
        self::assertStringContainsString('function getSplitStatus', $baseClassContent);
        self::assertStringNotContainsString('__processGeneratorParams', $baseClassContent);
    }

}
