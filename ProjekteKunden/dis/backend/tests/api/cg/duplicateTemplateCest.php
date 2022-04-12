<?php namespace cg;


use app\models\core\User;
use app\tests\fixtures\UserFixture;

class duplicateTemplateCest
{
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
    public function renamesDataModelTemplateForeignKeys(\ApiTester $I)
    {
        $I->wantTo('ensure that FKs are renamed when duplicating data model template');

        /** @var User $user */
        $I->haveInDatabase('auth_assignment', array('user_id' => 1, 'item_name' => 'sa', 'created_at' => 1606839528));
        $user = $I->grabRecord(User::class, ['username' => 'sa']);
        $I->amBearerAuthenticated($user->api_token);
        $I->sendGet('/cg/api/duplicate', [
            'type' => 'model',
            'oldName' => 'ProjectHole',
            'newName' => 'ProjectHoleDup'
        ]);
        $I->canSeeResponseCodeIs(\Codeception\Util\HttpCode::OK);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(['relations' => [
            "project_hole_dup__project_site__parent" => [
                "name" => "project_hole_dup__project_site__parent",
                "foreignTable" => "project_site",
                "localColumns" => ["site_id"],
                "foreignColumns" => ["id"]
            ]
        ]]);
        $I->wantTo('ensure that duplicated template is deleted');
        $I->sendGet('/cg/api/delete-model', [ 'name' => 'ProjectHoleDup' ]);
        $I->seeResponseCodeIs(204);
    }
}
