<?php namespace forms;


use app\models\core\User;
use app\modules\api\common\controllers\FormController;
use app\tests\fixtures\UserFixture;
use Codeception\Util\HttpCode;

class apiLimitedAccessRuleCest
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
        $I->haveInDatabase('auth_item', [ "name" => "viewLimitedExpedition", "data" => serialize(["combined_id" => "5065"]), "type" => "2", "rule_name" => "hasLimitedAccess", ]);
        $I->haveInDatabase('auth_item', [ "name" => "editLimitedExpedition", "data" => serialize(["combined_id" => "5065"]), "type" => "2", "rule_name" => "hasLimitedAccess", ]);
        $I->haveInDatabase('auth_item', [ "name" => "viewLimitedExpedition2", "data" => serialize(["combined_id" => "4000"]), "type" => "2", "rule_name" => "hasLimitedAccess", ]);
        $I->haveInDatabase('auth_item', [ "name" => "editLimitedExpedition2", "data" => serialize(["combined_id" => "4000"]), "type" => "2", "rule_name" => "hasLimitedAccess", ]);
        $I->haveInDatabase('auth_item', [ "name" => "accessLimitedSite", "data" => serialize(["combined_id" => "5065_1"]), "type" => "2", "rule_name" => "hasLimitedAccess", ]);
        $I->haveInDatabase('auth_item_child', ["parent" => "viewLimitedExpedition", "child" => "form-ALL:view"]);
        $I->haveInDatabase('auth_item_child', ["parent" => "editLimitedExpedition", "child" => "form-ALL:edit"]);
        $I->haveInDatabase('auth_item_child', ["parent" => "accessLimitedSite", "child" => "form-ALL:view"]);
        $I->haveInDatabase('auth_item_child', ["parent" => "accessLimitedSite", "child" => "form-ALL:edit"]);
    }

    public function canListAssignedExpeditionsSitesOnly (\ApiTester $I)
    {
        $user = $I->grabRecord(User::class, ['username' => 'user']);
        $I->amBearerAuthenticated($user->api_token);
        $I->sendGet('/api/v1/form?name=site');
        $I->canSeeResponseCodeIs(HttpCode::FORBIDDEN);
        $I->haveInDatabase('auth_assignment', array('user_id' => $user->id, 'item_name' => 'viewLimitedExpedition', 'created_at' => 1606839528));
        $I->sendGet('/api/v1/form?name=site');
        $I->canSeeResponseCodeIs(HttpCode::OK);
        $I->canSeeResponseIsJson();
        $items = $I->grabDataFromResponseByJsonPath('$.items');
        $I->assertCount(2, $items[0], "ensure only 2 items are returned");
    }

    // tests
    public function userCanUpdateAssignedExpeditionsSiteOnly (\ApiTester $I)
    {
        $user = $I->grabRecord(User::class, ['username' => 'user']);
        $I->amBearerAuthenticated($user->api_token);
        $I->sendPut('/api/v1/form/1?name=site', [
            'name' => 'NewName'
        ]);
        $I->canSeeResponseCodeIs(\Codeception\Util\HttpCode::FORBIDDEN);
        $I->haveInDatabase('auth_assignment', array('user_id' => $user->id, 'item_name' => 'editLimitedExpedition', 'created_at' => 1606839528));
        $I->sendPut('/api/v1/form/1?name=site', [
            'name' => 'NewName'
        ]);
        $I->canSeeResponseCodeIs(\Codeception\Util\HttpCode::OK);
        $I->sendPut('/api/v1/form/3?name=site', [
            'name' => 'NewName'
        ]);
        $I->canSeeResponseCodeIs(\Codeception\Util\HttpCode::FORBIDDEN);
    }

    public function userCanDeleteAssignedExpeditionsSiteOnly (\ApiTester $I)
    {
        $user = $I->grabRecord(User::class, ['username' => 'user']);
        $I->amBearerAuthenticated($user->api_token);
        $I->sendDelete('/api/v1/form/1?name=site');
        $I->canSeeResponseCodeIs(\Codeception\Util\HttpCode::FORBIDDEN);
        $I->haveInDatabase('auth_assignment', array('user_id' => $user->id, 'item_name' => 'editLimitedExpedition', 'created_at' => 1606839528));
        $I->sendDelete('/api/v1/form/1?name=site');
        $I->canSeeResponseCodeIs(\Codeception\Util\HttpCode::CONFLICT);
        $I->sendDelete('/api/v1/form/3?name=site');
        $I->canSeeResponseCodeIs(\Codeception\Util\HttpCode::FORBIDDEN);
    }

    public function userCanViewAssignedExpeditionsSiteOnly (\ApiTester $I)
    {
        $user = $I->grabRecord(User::class, ['username' => 'user']);
        $I->amBearerAuthenticated($user->api_token);
        $I->sendGet('/api/v1/form/1?name=site');
        $I->canSeeResponseCodeIs(\Codeception\Util\HttpCode::FORBIDDEN);
        $I->haveInDatabase('auth_assignment', array('user_id' => $user->id, 'item_name' => 'viewLimitedExpedition', 'created_at' => 1606839528));
        $I->sendGet('/api/v1/form/1?name=site');
        $I->canSeeResponseCodeIs(\Codeception\Util\HttpCode::OK);
        $I->sendGet('/api/v1/form/3?name=site');
        $I->canSeeResponseCodeIs(\Codeception\Util\HttpCode::FORBIDDEN);
    }

    public function userCanCreateSiteUnderAssignedExpeditionsOnly (\ApiTester $I)
    {
        $user = $I->grabRecord(User::class, ['username' => 'user']);
        $I->amBearerAuthenticated($user->api_token);
        $I->sendPost('/api/v1/form?name=site', [
            'expedition_id' =>  1,
            'site' =>  '3',
            'name' =>  'Queen',
            'date_start' =>  '2021-01-02 12:00:00',
        ]);
        $I->canSeeResponseCodeIs(\Codeception\Util\HttpCode::FORBIDDEN);
        $I->haveInDatabase('auth_assignment', array('user_id' => $user->id, 'item_name' => 'editLimitedExpedition', 'created_at' => 1606839528));
        $I->sendPost('/api/v1/form?name=site', [
            'expedition_id' =>  1,
            'site' =>  '3',
            'name' =>  'Queen',
            'date_start' =>  '2021-01-02 12:00:00',
        ]);
        $I->canSeeResponseCodeIs(\Codeception\Util\HttpCode::CREATED);
        $I->sendPost('/api/v1/form?name=site', [
            'expedition_id' =>  2,
            'site' =>  '2',
            'name' =>  'Queen',
            'date_start' =>  '2021-01-02 12:00:00',
        ]);
        $I->canSeeResponseCodeIs(\Codeception\Util\HttpCode::FORBIDDEN);
    }

    public function userCanListAssignedExpeditionsSiteOnly (\ApiTester $I)
    {
        $I->wantTo('Test if a user without limited form view access permission cannot view assigned records');
        /** @var User $user */
        $user = $I->grabRecord(User::class, ['username' => 'user']);
        $I->amBearerAuthenticated($user->api_token);
        $I->sendGet('/api/v1/form', [
            "name" => "core"
        ]);
        $I->canSeeResponseCodeIs(\Codeception\Util\HttpCode::FORBIDDEN);
        $I->haveInDatabase('auth_assignment', array('user_id' => $user->id, 'item_name' => 'viewLimitedExpedition', 'created_at' => 1606839528));
        $I->amBearerAuthenticated($user->api_token);
        $I->sendGet('/api/v1/form', [
            "name" => "core"
        ]);
        $I->canSeeResponseCodeIs(\Codeception\Util\HttpCode::OK);
    }


    public function userCanDuplicateAssignedExpeditionsSitesOnly (\ApiTester $I)
    {
        $user = $I->grabRecord(User::class, ['username' => 'user']);
        $I->amBearerAuthenticated($user->api_token);
        $I->sendPost('/api/v1/form/duplicate?name=site&id=1');
        $I->canSeeResponseCodeIs(\Codeception\Util\HttpCode::FORBIDDEN);
        $I->haveInDatabase('auth_assignment', array('user_id' => $user->id, 'item_name' => 'editLimitedExpedition', 'created_at' => 1606839528));
        $I->sendPost('/api/v1/form/duplicate?name=site&id=1');
        $I->canSeeResponseCodeIs(\Codeception\Util\HttpCode::OK);
        $I->sendPost('/api/v1/form/duplicate?name=site&id=3');
        $I->canSeeResponseCodeIs(\Codeception\Util\HttpCode::FORBIDDEN);
    }

    public function userCanGetDefaultsOfAssignedExpeditionsSitesOnly (\ApiTester $I)
    {
        $user = $I->grabRecord(User::class, ['username' => 'user']);
        $I->amBearerAuthenticated($user->api_token);
        $I->sendPost('/api/v1/form/defaults?name=site', [
            'expedition_id' =>  1
        ]);
        $I->canSeeResponseCodeIs(\Codeception\Util\HttpCode::FORBIDDEN);
        $I->haveInDatabase('auth_assignment', array('user_id' => $user->id, 'item_name' => 'editLimitedExpedition', 'created_at' => 1606839528));
        $I->sendPost('/api/v1/form/defaults?name=site', [
            'expedition_id' =>  1
        ]);
        $I->canSeeResponseCodeIs(\Codeception\Util\HttpCode::OK);
        $I->sendPost('/api/v1/form/defaults?name=site', [
            'expedition_id' =>  2
        ]);
        $I->canSeeResponseCodeIs(\Codeception\Util\HttpCode::FORBIDDEN);
    }

    public function userCanGetFilterListsOfAssignedExpeditionsSitesOnly (\ApiTester $I)
    {
        $user = $I->grabRecord(User::class, ['username' => 'user']);
        $I->amBearerAuthenticated($user->api_token);
        $I->haveInDatabase('auth_assignment', array('user_id' => $user->id, 'item_name' => 'viewLimitedExpedition', 'created_at' => 1606839528));
        $I->sendGet('/api/v1/form/filter-lists?name=site', [
            'models' => ['{"model":"expedition","require":null}']
        ]);
        $I->canSeeResponseCodeIs(HttpCode::OK);
        $I->canSeeResponseIsJson();
        $result = $I->grabDataFromResponseByJsonPath('$');
        $I->assertCount(2, $result[0]["expedition"]);
        $I->haveInDatabase('auth_assignment', array('user_id' => $user->id, 'item_name' => 'viewLimitedExpedition2', 'created_at' => 1606839528));
        $I->sendGet('/api/v1/form/filter-lists', [
            'name' => 'site',
            'models' => ['{"model":"expedition","require":null}']
        ]);
        $I->canSeeResponseCodeIs(HttpCode::OK);
        $I->canSeeResponseIsJson();
        $result = $I->grabDataFromResponseByJsonPath('$');
        $I->assertCount(2, $result[0]["expedition"]);
    }

    public function userCanGetFilterListsOfAssignedSitesOnly (\ApiTester $I)
    {
        $user = $I->grabRecord(User::class, ['username' => 'user']);
        $I->amBearerAuthenticated($user->api_token);
        $I->haveInDatabase('auth_assignment', array('user_id' => $user->id, 'item_name' => 'accessLimitedSite', 'created_at' => 1606839528));
        $I->sendGet('/api/v1/form/filter-lists',[
            'name' => 'hole',
            'models' => ['{"model":"expedition","require":null}', '{"model":"site","require":{"as":"expedition_id","value":1}}' ]
        ]);
        $I->canSeeResponseCodeIs(HttpCode::OK);
        $I->canSeeResponseIsJson();
        $result = $I->grabDataFromResponseByJsonPath('$');
        $I->assertCount(2, $result[0]["expedition"]);
        $I->assertCount(2, $result[0]["site"]);
    }
}
