<?php namespace app;


use app\models\core\User;
use app\tests\fixtures\UserFixture;

class basicCest
{
    public function _fixtures () {
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
            'coreboxes' => [
                'class' => \app\tests\fixtures\CurationCoreboxFixture::class,
                'dataFile' => codecept_data_dir() . 'curation_corebox.php'
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
        $I->haveInDatabase('auth_item', [ "name" => "viewLimitedExpedition", "data" => serialize(["form" => "expedition","combined_id" => "5065_"]), "type" => "2", "rule_name" => "hasLimitedAccess", ]);
        $I->haveInDatabase('auth_item', [ "name" => "viewLimitedSite", "data" => serialize(["form" => "expedition","combined_id" => "5065_1"]), "type" => "2", "rule_name" => "hasLimitedAccess", ]);
        $I->haveInDatabase('auth_item_child', ["parent" => "viewLimitedExpedition", "child" => "form-ALL:view"]);
        $I->haveInDatabase('auth_item_child', ["parent" => "viewLimitedSite", "child" => "form-ALL:view"]);
    }

    // tests
    public function returnsAllAppForms(\ApiTester $I)
    {
        /** @var User $user */
        $I->haveInDatabase('auth_assignment', array('user_id' => 1, 'item_name' => 'sa', 'created_at' => 1606839528));
        $user = $I->grabRecord(User::class, ['username' => 'sa']);
        $I->amBearerAuthenticated($user->api_token);
        $I->sendGet('/api/v1/app/forms');
        $I->canSeeResponseCodeIs(\Codeception\Util\HttpCode::OK);
        $I->seeResponseIsJson();
        $I->assertEquals($I->grabDataFromResponseByJsonPath('$.Project[*].key'), ["expedition", "hole", "program", "site"]);
        $I->assertEquals($I->grabDataFromResponseByJsonPath('$.Core[*].key'), ["core", "section"]);
        $I->assertEquals($I->grabDataFromResponseByJsonPath('$.Curation[*].key'), ["corebox", "cuttings", "sample", "sample-request", "split", "storage"]);
    }

    public function returnsAssignedAppForms(\ApiTester $I)
    {
        /** @var User $user */
        $user = $I->grabRecord(User::class, ['username' => 'user']);
        $I->amBearerAuthenticated($user->api_token);
        $I->sendGet('/api/v1/app/forms');
        $I->canSeeResponseCodeIs(\Codeception\Util\HttpCode::OK);
        $I->seeResponseIsJson();
        $I->seeResponseEquals('[]');
        $I->haveInDatabase('auth_assignment', array('user_id' => 5, 'item_name' => 'form-core:view', 'created_at' => 1606839528));
        $I->sendGet('/api/v1/app/forms');
        $I->canSeeResponseCodeIs(\Codeception\Util\HttpCode::OK);
        $I->assertEquals($I->grabDataFromResponseByJsonPath('$.Project'), []);
        $I->assertEquals($I->grabDataFromResponseByJsonPath('$.Core[*].key'), ["core"]);
        $I->assertEquals($I->grabDataFromResponseByJsonPath('$.Curation'), []);
        $I->haveInDatabase('auth_assignment', array('user_id' => 5, 'item_name' => 'form-hole:view', 'created_at' => 1606839528));
        $I->sendGet('/api/v1/app/forms');
        $I->canSeeResponseCodeIs(\Codeception\Util\HttpCode::OK);
        $I->assertEquals($I->grabDataFromResponseByJsonPath('$.Project[*].key'), ["hole"]);
        $I->assertEquals($I->grabDataFromResponseByJsonPath('$.Core[*].key'), ["core"]);
        $I->assertEquals($I->grabDataFromResponseByJsonPath('$.Curation'), []);
    }

    public function returnsLimitedAccessAppForms(\ApiTester $I)
    {
        /** @var User $user */
        $user = $I->grabRecord(User::class, ['username' => 'user']);
        $I->amBearerAuthenticated($user->api_token);
        $I->sendGet('/api/v1/app/forms');
        $I->canSeeResponseCodeIs(\Codeception\Util\HttpCode::OK);
        $I->seeResponseIsJson();
        $I->seeResponseEquals('[]');
        $I->haveInDatabase('auth_assignment', array('user_id' => 5, 'item_name' => 'viewLimitedSite', 'created_at' => 1606839528));
        $I->sendGet('/api/v1/app/forms');
        $I->canSeeResponseCodeIs(\Codeception\Util\HttpCode::OK);
        $I->assertEquals($I->grabDataFromResponseByJsonPath('$.Project[*].key'), ["expedition", "hole", "program", "site"]);
        $I->assertEquals($I->grabDataFromResponseByJsonPath('$.Core[*].key'), ["core", "section"]);
        $I->assertEquals($I->grabDataFromResponseByJsonPath('$.Curation[*].key'), ["corebox", "cuttings", "sample", "sample-request", "split", "storage"]);
    }

    public function searchForRecordByIgsn(\ApiTester $I)
    {
        /** @var User $user */
        $I->haveInDatabase('auth_assignment', array('user_id' => 1, 'item_name' => 'sa', 'created_at' => 1606839528));
        $user = $I->grabRecord(User::class, ['username' => 'sa']);
        $I->amBearerAuthenticated($user->api_token);
        $I->sendGet('/api/v1/app/find-igsn/ICDP5065EC10001');
        $I->canSeeResponseCodeIs(\Codeception\Util\HttpCode::OK);
        $I->seeResponseIsJson();
        $r = $I->grabResponse();
        $I->assertEquals($I->grabDataFromResponseByJsonPath('$[*].data_model'), ["CoreCore"]);
        $I->assertEquals($I->grabDataFromResponseByJsonPath('$[*].id'), [1]);
        $I->sendGet('/api/v1/app/find-igsn/ICDP5065ES10001');
        $I->canSeeResponseCodeIs(\Codeception\Util\HttpCode::OK);
        $I->seeResponseIsJson();
        $I->assertEquals($I->grabDataFromResponseByJsonPath('$[*].data_model'), ["CurationSectionSplit"]);
        $I->assertEquals($I->grabDataFromResponseByJsonPath('$[*].id'), [1]);
    }
}
