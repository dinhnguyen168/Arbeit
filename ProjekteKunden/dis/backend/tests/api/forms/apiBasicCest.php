<?php namespace forms;


use app\models\core\User;
use app\modules\api\common\controllers\FormController;
use app\tests\fixtures\UserFixture;

class apiBasicCest
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
            'dis_list' => [
                'class' => \app\tests\fixtures\core\DisListFixture::class,
                'dataFile' => codecept_data_dir() . 'list_values/dis_list.php'
            ],
            'dis_list_item' => [
                'class' => \app\tests\fixtures\core\DisListItemFixture::class,
                'dataFile' => codecept_data_dir() . 'list_values/dis_list_item.php'
            ],
        ];
    }

    public function _before(\ApiTester $I)
    {
        /** TODO: test using fixture for rbac instead of this */
        FormController::updateAccessRights();
    }

    public function returnsFormFilterLists(\ApiTester $I)
    {
        $I->haveInDatabase('auth_assignment', array('user_id' => 1, 'item_name' => 'sa', 'created_at' => 1606839528));
        $user = $I->grabRecord(User::class, ['username' => 'sa']);
        $I->amBearerAuthenticated($user->api_token);
        $I->sendGet('/api/v1/form/filter-lists', [
            'name' => 'core',
            'models' => ['{"model":"expedition","require":null}', '{"model":"site","require":{"as":"expedition_id","value":1}}', '{"model":"hole","require":{"as":"site_id","value":1}}' ]
        ]);
        $I->canSeeResponseCodeIs(\Codeception\Util\HttpCode::OK);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            "expedition" => [
                ["value" => 1, "text" => "GRIND"], ["value" => 2, "text" => "GFZ"]
            ],
            "site" => [
                ["value" => 2, "text" => "2", "expedition_id" => 1], ["value" => 1, "text" => "1", "expedition_id" => 1]
            ],
            "hole" => [
                ["value" => 2, "text" => "B", "site_id" => 1], ["value" => 1, "text" => "A", "site_id" => 1]]
        ]);
    }


    public function returnsFormReports(\ApiTester $I)
    {
        $I->wantTo('ensure that api returns reports associated with form');
        $I->haveInDatabase('auth_assignment', array('user_id' => 1, 'item_name' => 'sa', 'created_at' => 1606839528));
        $user = $I->grabRecord(User::class, ['username' => 'sa']);
        $I->amBearerAuthenticated($user->api_token);
        $I->sendGet('/api/v1/form/reports', [
            'name' => 'core'
        ]);
        $I->canSeeResponseCodeIs(\Codeception\Util\HttpCode::OK);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            "single" => [
                ["name"=>"Details","title"=>"Details of record","model"=>".*","type"=>"report","singleRecord"=>true,"directPrint"=>false,"confirmationMessage"=>false]
            ]
        ]);
        $I->seeResponseContainsJson([
            "multiple"=>[
                ["name"=>"ListAllCsv","title"=>"Export full records as CSV file","model"=>".*","type"=>"export","singleRecord"=>false,"directPrint"=>false,"confirmationMessage"=>false],
                ["name"=>"ListCsv","title"=>"Export records as CSV file","model"=>".*","type"=>"export","singleRecord"=>false,"directPrint"=>false,"confirmationMessage"=>false],
                ["name"=>"List","title"=>"List of records","model"=>".*","type"=>"report","singleRecord"=>false,"directPrint"=>false,"confirmationMessage"=>false]
            ]
        ]);
    }
}
