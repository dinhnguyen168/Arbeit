<?php namespace templates\models;

use app\components\templates\BaseTemplate;
use app\components\templates\ModelTemplate;
use app\components\templates\ModelTemplateBehavior;
use app\components\templates\ModelTemplateColumn;
use app\components\templates\ModelTemplateRelations;
use app\components\templates\ModelTemplateIndex;

class ModelTemplateTest extends \Codeception\Test\Unit
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
    public function testModelAcceptsPseudoAsColumnType()
    {
        $modelTemplate = new ModelTemplate([
            'scenario' => BaseTemplate::SCENARIO_CREATE
        ]);
        $templateArray = [
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
                "section_id" => [
                    "name" => "section_id",
                    "type" => "integer",
                    "size" => 11,
                    "required" => false,
                    "primaryKey" => false,
                    "autoInc" => false,
                    "label" => "Section",
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
                    "label" => "Pseudo Site Name",
                ],
                "pseudo_core_name" => [
                    "name" => "pseudo_core_name",
                    "type" => "string",
                    "size" => null,
                    "required" => false,
                    "primaryKey" => false,
                    "autoInc" => false,
                    "label" => "Pseudo Core Name",
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
                ],
                "project_test_hole__core_core" => [
                    "name" => "project_test_hole__core_section",
                    "foreignTable" => "core_section",
                    "localColumns" => ["section_id"],
                    "foreignColumns" => ["id"],
                ]
            ],
            "behaviors" => [],
            "createdAt" => 1604663034,
            "modifiedAt" => 1604663034,
            "generatedAt" => null,
            "fullName" => "ProjectTestHole",
        ];

        $modelTemplate->load($templateArray, '');
        expect_not($modelTemplate->validate());
        // test non existing column
        $templateArray['columns']['pseudo_site_name']['pseudoCalc'] = 'parent.name_not_exist';
        $modelTemplate->load($templateArray, '');
        expect_not($modelTemplate->validate());

        // test non existing parent
        $templateArray['columns']['pseudo_site_name']['pseudoCalc'] = 'parent.parent.parent.parent.name';
        $modelTemplate->load($templateArray, '');
        expect_not($modelTemplate->validate());
        $templateArray['columns']['pseudo_site_name']['pseudoCalc'] = 'parent.site_name';
        expect_that($modelTemplate->columns['pseudo_site_name']->searchable);
        $modelTemplate->load($templateArray, '');
        expect_that($modelTemplate->validate(), "Could not validate template: " . print_r($modelTemplate->errors, true));

        // test php pseudo calc
        $templateArray['columns']['pseudo_site_name']['type'] = 'pseudo';
        $templateArray['columns']['pseudo_site_name']['pseudoCalc'] = 'return $model->parent->site_name';
        $modelTemplate->load($templateArray, '');
        expect_not($modelTemplate->validate());
        $templateArray['columns']['pseudo_site_name']['pseudoCalc'] = '$model->parent->site_name';
        $modelTemplate->load($templateArray, '');
        expect_that($modelTemplate->validate(), "Could not validate template: " . print_r($modelTemplate->errors, true));
        expect_not($modelTemplate->columns['pseudo_site_name']->searchable);


        // test non-parent relation
        $templateArray['columns']['pseudo_site_name']['type'] = 'string';

        $templateArray['columns']['pseudo_core_name']['type'] = 'pseudo';
        $templateArray['columns']['pseudo_core_name']['pseudoCalc'] = 'section.parent.core';
        $modelTemplate->load($templateArray, '');
        expect_that($modelTemplate->validate());
        expect_that($modelTemplate->columns['pseudo_core_name']->searchable);

        $templateArray['columns']['pseudo_core_name']['pseudoCalc'] = 'section.parent.non_existant_column';
        $modelTemplate->load($templateArray, '');
        expect_not($modelTemplate->validate());

        $templateArray['columns']['pseudo_core_name']['pseudoCalc'] = 'section.non_existant_relation.name';
        $modelTemplate->load($templateArray, '');
        expect_not($modelTemplate->validate());

        $templateArray['columns']['pseudo_core_name']['type'] = 'string';
        $templateArray['columns']['pseudo_core_name']['pseudoCalc'] = '';
        $modelTemplate->load($templateArray, '');
        expect_that($modelTemplate->validate());

    }

    public function testDetectsDirtyColumns() {
        $holeTemplate = \Yii::$app->templates->getModelTemplate('ProjectHole');
        $dirtyAttributes = $holeTemplate->getDirtyAttributes();
        self::assertCount(0, $dirtyAttributes);
        // change a column
        $holeTemplate->columns['combined_id'] = new ModelTemplateColumn($holeTemplate, array_merge(
            $holeTemplate->columns['combined_id']->attributes,
            ["label" => "new label"]
        ));

        $dirtyAttributes = $holeTemplate->getDirtyAttributes();
        self::assertCount(1, $dirtyAttributes);
        self::assertEquals([
            'combined_id' => $holeTemplate->columns['combined_id']
        ], $dirtyAttributes['columns']);
    }

    public function testDetectsDeletedColumns() {
        $holeTemplate = \Yii::$app->templates->getModelTemplate('ProjectHole');
        $combinedIdColumn = $holeTemplate->columns['combined_id'];
        unset($holeTemplate->columns['combined_id']);

        $dirtyAttributes = $holeTemplate->getDirtyAttributes();
        self::assertCount(0, $dirtyAttributes);

        $deletedAttributes = $holeTemplate->getDeletedAttributes();
        self::assertCount(3, $deletedAttributes);
        self::assertCount(1, $deletedAttributes['columns']);
        self::assertCount(0, $deletedAttributes['indices']);
        self::assertCount(0, $deletedAttributes['relations']);
        self::assertEquals(['combined_id' => $combinedIdColumn], $deletedAttributes['columns']);
    }

    public function testAddsErrorsForLockedColumns()
    {
        $holeTemplate = \Yii::$app->templates->getModelTemplate('ProjectHole');
        // change a column
        $holeTemplate->columns['id'] = new ModelTemplateColumn($holeTemplate, array_merge(
            $holeTemplate->columns['id']->attributes,
            ["label" => "Something different"]
        ));
        expect_not($holeTemplate->validate());
        $errors = $holeTemplate->getErrors();
        self::assertCount(1, $errors);
        self::assertEquals(['This column is locked and can\'t be updated'], $errors['columns.id']);
    }

    public function testDetectsDirtyIndices() {
        $holeTemplate = \Yii::$app->templates->getModelTemplate('ProjectHole');
        $dirtyAttributes = $holeTemplate->getDirtyAttributes();
        self::assertCount(0, $dirtyAttributes);

        // change an index
        $holeTemplate->indices['site_id__hole'] = new ModelTemplateIndex($holeTemplate, array_merge(
            $holeTemplate->indices['site_id__hole']->attributes,
            ["columns" => [
                'name'
            ]]
        ));

        $dirtyAttributes = $holeTemplate->getDirtyAttributes();
        self::assertCount(1, $dirtyAttributes);
        self::assertEquals([
            'site_id__hole' => $holeTemplate->indices['site_id__hole']
        ], $dirtyAttributes['indices']);
    }

    public function testDetectsDeletedIndices() {
        $holeTemplate = \Yii::$app->templates->getModelTemplate('ProjectHole');
        $deletedIndex = $holeTemplate->indices['site_id__hole'];
        unset($holeTemplate->indices['site_id__hole']);

        $dirtyAttributes = $holeTemplate->getDirtyAttributes();
        self::assertCount(0, $dirtyAttributes);

        $deletedAttributes = $holeTemplate->getDeletedAttributes();
        self::assertCount(3, $deletedAttributes);
        self::assertCount(0, $deletedAttributes['columns']);
        self::assertCount(1, $deletedAttributes['indices']);
        self::assertCount(0, $deletedAttributes['relations']);
        self::assertEquals(['site_id__hole' => $deletedIndex], $deletedAttributes['indices']);
    }

    public function testAddsErrorsForLockedIndices()
    {
        $holeTemplate = \Yii::$app->templates->getModelTemplate('ProjectHole');
        // change a column
        $holeTemplate->indices['site_id__hole'] = new ModelTemplateIndex($holeTemplate, array_merge(
            $holeTemplate->indices['site_id__hole']->attributes,
            ["columns" => [
                'name'
            ]]
        ));
        expect_not($holeTemplate->validate());
        $errors = $holeTemplate->getErrors();
        self::assertCount(1, $errors);
        self::assertEquals(['This index is locked and can\'t be updated'], $errors['indices.site_id__hole']);
    }


    public function testDetectsDirtyForeignKeys() {
        $holeTemplate = \Yii::$app->templates->getModelTemplate('ProjectHole');
        $dirtyAttributes = $holeTemplate->getDirtyAttributes();
        self::assertCount(0, $dirtyAttributes);

        // change an index
        $holeTemplate->relations['project_hole__project_site__parent'] = new ModelTemplateRelations($holeTemplate, array_merge(
            $holeTemplate->relations['project_hole__project_site__parent']->attributes,
            ["localColumns" => [
                'name'
            ]]
        ));

        $dirtyAttributes = $holeTemplate->getDirtyAttributes();
        self::assertCount(1, $dirtyAttributes);
        self::assertEquals([
            'project_hole__project_site__parent' => $holeTemplate->relations['project_hole__project_site__parent']
        ], $dirtyAttributes['relations']);
    }

    public function testDetectsDeletedForeignKeys() {
        $holeTemplate = \Yii::$app->templates->getModelTemplate('ProjectHole');
        $deletedFK = $holeTemplate->relations['project_hole__project_site__parent'];
        unset($holeTemplate->relations['project_hole__project_site__parent']);

        $dirtyAttributes = $holeTemplate->getDirtyAttributes();
        self::assertCount(0, $dirtyAttributes);

        $deletedAttributes = $holeTemplate->getDeletedAttributes();
        self::assertCount(3, $deletedAttributes);
        self::assertCount(0, $deletedAttributes['columns']);
        self::assertCount(0, $deletedAttributes['indices']);
        self::assertCount(1, $deletedAttributes['relations']);
        self::assertEquals(['project_hole__project_site__parent' => $deletedFK], $deletedAttributes['relations']);
    }

/*
    public function testAddsErrorsForLockedForeignKeys()
    {
        $holeTemplate = \Yii::$app->templates->getModelTemplate('ProjectHole');
        // change a column
        $holeTemplate->relations['project_hole__project_site__parent'] = new ModelTemplateRelations($holeTemplate, array_merge(
            $holeTemplate->relations['project_hole__project_site__parent']->attributes,
            ["localColumns" => [
                'name'
            ]]
        ));
        expect_not($holeTemplate->validate());
        $errors = $holeTemplate->getErrors();
        self::assertCount(1, $errors);
        self::assertEquals(['This foreign key is locked and can\'t be updated'], $errors['foreignkeys.project_hole__project_site__parent']);
    }
*/

    public function testIgnoresDirtyBehaviors() {
        $holeTemplate = \Yii::$app->templates->getModelTemplate('ProjectHole');
        $dirtyAttributes = $holeTemplate->getDirtyAttributes();
        self::assertCount(0, $dirtyAttributes);

        // change an index
        $holeTemplate->behaviors[0] = new ModelTemplateBehavior($holeTemplate, array_merge(
            $holeTemplate->behaviors[0]->attributes,
            ["behaviorClass" => 'someClass']
        ));

        $dirtyAttributes = $holeTemplate->getDirtyAttributes();
        self::assertCount(0, $dirtyAttributes);
    }

    public function testGetsReferencingModels() {
        $holeTemplate = \Yii::$app->templates->getModelTemplate('ProjectHole');
        $referencingModels = $holeTemplate->getReferencingModelTemplates();
        // self::assertCount(3, $referencingModels);
        self::assertEquals([
            'ArchiveFile',
            'CoreCore',
            'CurationCorebox',
            'CurationCuttings'
        ], array_keys($referencingModels));
    }

    public function testGetFormTemplates() {
        $modelTemplate = new ModelTemplate([
            'scenario' => BaseTemplate::SCENARIO_CREATE
        ]);
        $templateArray = [
            "module" => "Project",
            "name" => "TestNotExists",
            "table" => "project_test_not_exists",
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
            "fullName" => "ProjectTestNotExists",
        ];
        $modelTemplate->load($templateArray, '');
        expect_that($modelTemplate->validate());
        self::assertEquals(0, sizeof($modelTemplate->getFormTemplates()));

        $modelTemplate = \Yii::$app->templates->getModelTemplate("ProjectExpedition");
        self::assertNotNull($modelTemplate);
        self::assertGreaterThan (0, sizeof($modelTemplate->getFormTemplates()));
    }

}
