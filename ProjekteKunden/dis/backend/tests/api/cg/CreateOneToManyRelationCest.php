<?php namespace cg;


use app\models\core\User;
use app\tests\fixtures\UserFixture;
use yii\helpers\Json;

class CreateOneToManyRelationCest
{
    protected static $templatesPath = __DIR__ . '/../../../dis_templates/models';

    protected function _getTemplate($includeNewRelation = false) {
        $relationColumn = [
            'autoInc' => false,
            'calculate' => '',
            'defaultValue' => '',
            'description' => '',
            'displayColumn' => 'hole',
            'importSource' => '',
            'label' => 'Hole',
            'name' => 'hole_id',
            'primaryKey' => false,
            'relatedTable' => 'project_hole',
            'required' => false,
            'searchable' => false,
            'selectListName' => '',
            'size' => NULL,
            'type' => 'one_to_many',
            'unit' => '',
            'validator' => '',
            'validatorMessage' => '',
        ];
        $index = [
            'columns' => [
                0 => 'hole_id',
            ],
            'isLocked' => true,
            'name' => 'hole_id',
            'type' => 'KEY',
        ];
        $relation = [
                'displayColumns' => [
                    0 => 'hole',
                ],
                'foreignColumns' => [
                    0 => 'id',
                ],
                'foreignTable' => 'project_hole',
                'localColumns' => [
                    0 => 'hole_id',
                ],
                'name' => 'project_site__project_hole__hole_id__1n',
                'relationType' => '1n',
        ];

        $template = [
            'template' => [
                'module' => 'Project',
                'name' => 'Site',
                'table' => 'project_site',
                'importTable' => 'EXP_PROJECT_SITE',
                'parentModel' => 'ProjectExpedition',
                'columns' => [
                    'id' => [
                        'name' => 'id',
                        'importSource' => 'SKEY',
                        'type' => 'integer',
                        'size' => 11,
                        'required' => false,
                        'primaryKey' => true,
                        'autoInc' => true,
                        'label' => '#',
                        'description' => '#; auto incremented id',
                        'validator' => '',
                        'validatorMessage' => '',
                        'unit' => '',
                        'selectListName' => '',
                        'calculate' => '',
                        'defaultValue' => '',
                        'pseudoCalc' => '',
                        'isLocked' => NULL,
                        'searchable' => true,
                    ],
                    'expedition_id' => [
                        'name' => 'expedition_id',
                        'importSource' => 'return function($aImportedRecord) {
$cParentFilterValue = preg_replace("/_[^_]+$/", "", $aImportedRecord["combined_id"]); 
return \\app\\dis_migration\\Module::lookupValue("project_expedition", "id", ["expedition" => $cParentFilterValue]);
};',
                        'type' => 'integer',
                        'size' => 11,
                        'required' => true,
                        'primaryKey' => false,
                        'autoInc' => false,
                        'label' => 'Expedition ID',
                        'description' => '',
                        'validator' => '',
                        'validatorMessage' => '',
                        'unit' => '',
                        'selectListName' => '',
                        'calculate' => '',
                        'defaultValue' => '',
                        'pseudoCalc' => '',
                        'isLocked' => NULL,
                        'searchable' => true,
                    ],
                    'combined_id' => [
                        'name' => 'combined_id',
                        'importSource' => 'LTRIM(CAST([EXPEDITION] AS varchar)) + \'_\' + LTRIM(CAST([SITE] AS varchar))',
                        'type' => 'string',
                        'size' => '',
                        'required' => false,
                        'primaryKey' => false,
                        'autoInc' => false,
                        'label' => 'Combined ID',
                        'description' => '',
                        'validator' => '',
                        'validatorMessage' => '',
                        'unit' => '',
                        'selectListName' => '',
                        'calculate' => '',
                        'defaultValue' => '',
                        'pseudoCalc' => '',
                        'isLocked' => NULL,
                        'searchable' => true,
                    ],
                    'site' => [
                        'name' => 'site',
                        'importSource' => 'SITE',
                        'type' => 'string',
                        'size' => 20,
                        'required' => false,
                        'primaryKey' => false,
                        'autoInc' => false,
                        'label' => 'Site',
                        'description' => 'Site Code',
                        'validator' => '',
                        'validatorMessage' => '',
                        'unit' => '',
                        'selectListName' => '',
                        'calculate' => '',
                        'defaultValue' => '',
                        'pseudoCalc' => '',
                        'isLocked' => NULL,
                        'searchable' => true,
                    ],
                    'site_name' => [
                        'name' => 'site_name',
                        'importSource' => '',
                        'type' => 'string',
                        'size' => NULL,
                        'required' => false,
                        'primaryKey' => false,
                        'autoInc' => false,
                        'label' => 'Name of site',
                        'description' => '',
                        'validator' => '',
                        'validatorMessage' => '',
                        'unit' => '',
                        'selectListName' => '',
                        'calculate' => '',
                        'defaultValue' => '',
                        'pseudoCalc' => '',
                        'isLocked' => NULL,
                        'searchable' => true,
                    ],
                    'site_name_alt' => [
                        'name' => 'site_name_alt',
                        'importSource' => '',
                        'type' => 'string',
                        'size' => NULL,
                        'required' => false,
                        'primaryKey' => false,
                        'autoInc' => false,
                        'label' => 'Alternative Name/Code of Site',
                        'description' => '',
                        'validator' => '',
                        'validatorMessage' => '',
                        'unit' => '',
                        'selectListName' => '',
                        'calculate' => '',
                        'defaultValue' => '',
                        'pseudoCalc' => '',
                        'isLocked' => NULL,
                        'searchable' => true,
                    ],
                    'type_drilling_location' => [
                        'name' => 'type_drilling_location',
                        'importSource' => '',
                        'type' => 'string',
                        'size' => NULL,
                        'required' => false,
                        'primaryKey' => false,
                        'autoInc' => false,
                        'label' => 'Type of Drilling Location',
                        'description' => '',
                        'validator' => '',
                        'validatorMessage' => '',
                        'unit' => '',
                        'selectListName' => '',
                        'calculate' => '',
                        'defaultValue' => '',
                        'pseudoCalc' => '',
                        'isLocked' => NULL,
                        'searchable' => true,
                    ],
                    'description' => [
                        'name' => 'description',
                        'importSource' => '',
                        'type' => 'string',
                        'size' => NULL,
                        'required' => false,
                        'primaryKey' => false,
                        'autoInc' => false,
                        'label' => 'Description of site',
                        'description' => '',
                        'validator' => '',
                        'validatorMessage' => '',
                        'unit' => '',
                        'selectListName' => '',
                        'calculate' => '',
                        'defaultValue' => '',
                        'pseudoCalc' => '',
                        'isLocked' => NULL,
                        'searchable' => true,
                    ],
                    'city' => [
                        'name' => 'city',
                        'importSource' => '',
                        'type' => 'string',
                        'size' => NULL,
                        'required' => false,
                        'primaryKey' => false,
                        'autoInc' => false,
                        'label' => 'City Nearby Drill Site',
                        'description' => '',
                        'validator' => '',
                        'validatorMessage' => '',
                        'unit' => '',
                        'selectListName' => '',
                        'calculate' => '',
                        'defaultValue' => '',
                        'pseudoCalc' => '',
                        'isLocked' => NULL,
                        'searchable' => true,
                    ],
                    'county' => [
                        'name' => 'county',
                        'importSource' => '',
                        'type' => 'string',
                        'size' => NULL,
                        'required' => false,
                        'primaryKey' => false,
                        'autoInc' => false,
                        'label' => 'County of Drill Site',
                        'description' => '',
                        'validator' => '',
                        'validatorMessage' => '',
                        'unit' => '',
                        'selectListName' => '',
                        'calculate' => '',
                        'defaultValue' => '',
                        'pseudoCalc' => '',
                        'isLocked' => NULL,
                        'searchable' => true,
                    ],
                    'state' => [
                        'name' => 'state',
                        'importSource' => '',
                        'type' => 'string',
                        'size' => NULL,
                        'required' => false,
                        'primaryKey' => false,
                        'autoInc' => false,
                        'label' => 'State',
                        'description' => 'State of Drill Site',
                        'validator' => '',
                        'validatorMessage' => '',
                        'unit' => '',
                        'selectListName' => '',
                        'calculate' => '',
                        'defaultValue' => '',
                        'pseudoCalc' => '',
                        'isLocked' => NULL,
                        'searchable' => true,
                    ],
                    'country' => [
                        'name' => 'country',
                        'importSource' => '',
                        'type' => 'string',
                        'size' => NULL,
                        'required' => false,
                        'primaryKey' => false,
                        'autoInc' => false,
                        'label' => 'Country of Drill site',
                        'description' => '',
                        'validator' => '',
                        'validatorMessage' => '',
                        'unit' => '',
                        'selectListName' => '',
                        'calculate' => '',
                        'defaultValue' => '',
                        'pseudoCalc' => '',
                        'isLocked' => NULL,
                        'searchable' => true,
                    ],
                    'comment' => [
                        'name' => 'comment',
                        'importSource' => '',
                        'type' => 'string',
                        'size' => NULL,
                        'required' => false,
                        'primaryKey' => false,
                        'autoInc' => false,
                        'label' => 'Additional Information',
                        'description' => '',
                        'validator' => '',
                        'validatorMessage' => '',
                        'unit' => '',
                        'selectListName' => '',
                        'calculate' => '',
                        'defaultValue' => '',
                        'pseudoCalc' => '',
                        'isLocked' => NULL,
                        'searchable' => true,
                    ],
                    'lithology' => [
                        'name' => 'lithology',
                        'importSource' => '',
                        'type' => 'string_multiple',
                        'size' => NULL,
                        'required' => false,
                        'primaryKey' => false,
                        'autoInc' => false,
                        'label' => 'Lithology',
                        'description' => 'Lithologies of Drilling Target',
                        'validator' => '',
                        'validatorMessage' => '',
                        'unit' => '',
                        'selectListName' => '',
                        'calculate' => '',
                        'defaultValue' => '',
                        'pseudoCalc' => '',
                        'isLocked' => NULL,
                        'searchable' => true,
                    ],
                    'geological_age' => [
                        'name' => 'geological_age',
                        'importSource' => '',
                        'type' => 'string_multiple',
                        'size' => NULL,
                        'required' => false,
                        'primaryKey' => false,
                        'autoInc' => false,
                        'label' => 'Geological Age',
                        'description' => 'Geological Age of Drilling Target',
                        'validator' => '',
                        'validatorMessage' => '',
                        'unit' => '',
                        'selectListName' => '',
                        'calculate' => '',
                        'defaultValue' => '',
                        'pseudoCalc' => '',
                        'isLocked' => NULL,
                        'searchable' => true,
                    ],
                ],
                'indices' => [
                    'id' => [
                        'name' => 'id',
                        'type' => 'PRIMARY',
                        'columns' => [
                            0 => 'id',
                        ],
                        'isLocked' => NULL,
                    ],
                    'expedition_id' => [
                        'name' => 'expedition_id',
                        'type' => 'KEY',
                        'columns' => [
                            0 => 'expedition_id',
                        ],
                        'isLocked' => NULL,
                    ],
                    'expedition_id__site' => [
                        'name' => 'expedition_id__site',
                        'type' => 'UNIQUE',
                        'columns' => [
                            0 => 'expedition_id',
                            1 => 'site',
                        ],
                        'isLocked' => NULL,
                    ],
                ],
                'relations' => [
                    'project_site__project_expedition__parent' => [
                        'name' => 'project_site__project_expedition__parent',
                        'foreignTable' => 'project_expedition',
                        'localColumns' => [
                            0 => 'expedition_id',
                        ],
                        'foreignColumns' => [
                            0 => 'id',
                        ],
                        'isLocked' => NULL,
                    ],
                ],
                'behaviors' => [
                    0 => [
                        'behaviorClass' => 'app\\behaviors\\template\\UniqueCombinationAutoIncrementBehavior',
                        'parameters' => [
                            'searchFields' => [
                                0 => 'expedition_id',
                            ],
                            'fieldToFill' => 'site',
                            'useAlphabet' => false,
                        ],
                    ],
                ],
                'createdAt' => 1583843094,
                'modifiedAt' => 1636700855,
                'generatedAt' => 1636700861,
                'fullName' => 'ProjectSite',
            ],
        ];

        if ($includeNewRelation) {
            $template['template']['columns']['hole_id'] = $relationColumn;
            $template['template']['indices']['hole_id'] = $index;
            $template['template']['relations']['project_site__project_hole__hole_id__1n'] = $relation;
        } else {
            if (isset($template['columns']['hole_id'])) {
                unset($template['columns']['hole_id']);
            }
            if (isset($template['indices']['hole_id'])) {
                unset($template['indices']['hole_id']);
            }
            if (isset($template['relations']['project_site__project_hole__hole_id__1n'])) {
                unset($template['relations']['project_site__project_hole__hole_id__1n']);
            }
        }

        return $template;
    }
    public function _fixtures()
    {
        return [
            'users' => [
                'class' => UserFixture::class,
                // fixture data located in tests/_data/user.php
                'dataFile' => codecept_data_dir() . 'user.php'
            ],
            'programs' => [
                'class' => \app\tests\fixtures\ProjectProgramFixture::class,
                'dataFile' => codecept_data_dir() . 'project_program.php'
            ],
            'expeditions' => [
                'class' => \app\tests\fixtures\ProjectExpeditionFixture::class,
                'dataFile' => codecept_data_dir() . 'project_expedition.php'
            ],
            'sites' => [
                'class' => \app\tests\fixtures\ProjectSiteFixture::class,
                'dataFile' => codecept_data_dir() . 'project_site.php'
            ],
            'holes' => [
                'class' => \app\tests\fixtures\ProjectHoleFixture::class,
                'dataFile' => codecept_data_dir() . 'project_hole.php'
            ],
            'cores' => [
                'class' => \app\tests\fixtures\CoreCoreFixture::class,
                'dataFile' => codecept_data_dir() . 'core_core.php'
            ],
            'sections' => [
                'class' => \app\tests\fixtures\CoreSectionFixture::class,
                'dataFile' => codecept_data_dir() . 'core_section.php'
            ],
            'section_splits' => [
                'class' => \app\tests\fixtures\CurationSectionSplitFixture::class,
                'dataFile' => codecept_data_dir() . 'curation_section_split.php'
            ],
            'samples' => [
                'class' => \app\tests\fixtures\CurationSampleFixture::class,
                'dataFile' => codecept_data_dir() . 'curation_sample.php'
            ],
        ];
    }

    public function _before(\ApiTester $I)
    {
    }

    // tests
    public function createOneToManyRelation(\ApiTester $I)
    {
        // $I->setCookie('XDEBUG_SESSION', 'PHPSTORM');
        $I->wantTo('ensure that a one to many relation is created between project_site and project_hole');
        $template = $this->_getTemplate(true);

        /** @var User $user */
        $I->haveInDatabase('auth_assignment', array('user_id' => 1, 'item_name' => 'sa', 'created_at' => 1606839528));
        $user = $I->grabRecord(User::class, ['username' => 'sa']);
        $I->amBearerAuthenticated($user->api_token);
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPost('/cg/api/update-model?name=ProjectSite', $template);
        $I->canSeeResponseCodeIs(\Codeception\Util\HttpCode::OK);

        $I->sendPost('/cg/api/create-model-table', [
            'model' => 'ProjectSite',
        ]);
        $I->canSeeResponseCodeIs(\Codeception\Util\HttpCode::OK);
    }

    public function oneToManyStructureInDatabaseCreated(\ApiTester $I)
    {
        $I->wantTo('ensure that the one to many needed data structure is created in the database');
        $tableSchema = \Yii::$app->db->schema->getTableSchema('project_site', true);
        $I->assertTrue(in_array('hole_id', $tableSchema->getColumnNames()));
        $I->assertTrue(isset($tableSchema->foreignKeys['project_site__project_hole__hole_id__1n']));
    }

    public function deleteOneToManyRelation(\ApiTester $I)
    {
        $I->wantTo('ensure that a one to many relation is deleted between project_site and project_hole');
        $template = $this->_getTemplate();

        /** @var User $user */
        $I->haveInDatabase('auth_assignment', array('user_id' => 1, 'item_name' => 'sa', 'created_at' => 1606839528));
        $user = $I->grabRecord(User::class, ['username' => 'sa']);
        $I->amBearerAuthenticated($user->api_token);
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPost('/cg/api/update-model?name=ProjectSite', $template);
        $I->canSeeResponseCodeIs(\Codeception\Util\HttpCode::OK);

        $I->sendPost('/cg/api/create-model-table', [
            'model' => 'ProjectSite',
        ]);
        $I->canSeeResponseCodeIs(\Codeception\Util\HttpCode::OK);
    }

    public function oneToManyStructureFromDatabaseDeleted(\ApiTester $I)
    {
        $I->wantTo('ensure that the one to many needed data structure is deleted from the database');
        $tableSchema = \Yii::$app->db->schema->getTableSchema('project_site', true);
        $I->assertTrue(!in_array('hole_id', $tableSchema->getColumnNames()));
        $I->assertTrue(!isset($tableSchema->foreignKeys['project_site__project_hole__hole_id__1n']));
    }
}
