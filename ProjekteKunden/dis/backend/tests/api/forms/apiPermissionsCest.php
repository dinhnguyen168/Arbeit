<?php namespace forms;


use app\models\core\User;
use app\modules\api\common\controllers\FormController;
use app\tests\fixtures\UserFixture;

class apiPermissionsCest
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
    }

    // tests
    public function testViewerPermissions(\ApiTester $I)
    {
        $I->wantTo('ensure that viewer can view data but cannot update data');

        /** @var User $user */
        $I->haveInDatabase('auth_assignment', array('user_id' => 4, 'item_name' => 'viewer', 'created_at' => 1606839528));
        $user = $I->grabRecord(User::class, ['username' => 'viewer']);
        $I->amBearerAuthenticated($user->api_token);
        $I->sendGet('/api/v1/form', [
            'name' => 'core',
            'filter[core_type]' => 'Rotary Core Barrel'
        ]);
        $I->canSeeResponseCodeIs(\Codeception\Util\HttpCode::OK);
        $I->sendPost('/api/v1/form?name=core', [
            'core_type' => 'Rotary Core Barrel'
        ]);
        $I->canSeeResponseCodeIs(\Codeception\Util\HttpCode::FORBIDDEN);
        $I->sendPut('/api/v1/form/1?name=core', [
            'core_type' => 'Rotary Core Barrel'
        ]);
        $I->canSeeResponseCodeIs(\Codeception\Util\HttpCode::FORBIDDEN);
        $I->sendDelete('/api/v1/form/1?name=core');
        $I->canSeeResponseCodeIs(\Codeception\Util\HttpCode::FORBIDDEN);
    }

    public function viewerCanList(\ApiTester $I)
    {
        /** @var User $user */
        $I->haveInDatabase('auth_assignment', array('user_id' => 4, 'item_name' => 'viewer', 'created_at' => 1606839528));
        $user = $I->grabRecord(User::class, ['username' => 'viewer']);
        $I->amBearerAuthenticated($user->api_token);
        $I->sendGet('/api/v1/form', [
            'name' => 'core',
            'filter[core_type]' => 'Rotary Core Barrel'
        ]);
        $I->canSeeResponseCodeIs(\Codeception\Util\HttpCode::OK);
    }

    public function viewerCannotCreate(\ApiTester $I)
    {
        /** @var User $user */
        $I->haveInDatabase('auth_assignment', array('user_id' => 4, 'item_name' => 'viewer', 'created_at' => 1606839528));
        $user = $I->grabRecord(User::class, ['username' => 'viewer']);
        $I->amBearerAuthenticated($user->api_token);

        $I->sendPost('/api/v1/form?name=core', [
            'core_type' => 'Rotary Core Barrel'
        ]);
        $I->canSeeResponseCodeIs(\Codeception\Util\HttpCode::FORBIDDEN);
    }

    public function viewerCannotUpdate(\ApiTester $I)
    {
        /** @var User $user */
        $I->haveInDatabase('auth_assignment', array('user_id' => 4, 'item_name' => 'viewer', 'created_at' => 1606839528));
        $user = $I->grabRecord(User::class, ['username' => 'viewer']);
        $I->amBearerAuthenticated($user->api_token);
        $I->sendPut('/api/v1/form/1?name=core', [
            'core_type' => 'Rotary Core Barrel'
        ]);
        $I->canSeeResponseCodeIs(\Codeception\Util\HttpCode::FORBIDDEN);
    }

    public function viewerCannotDelete(\ApiTester $I)
    {
        /** @var User $user */
        $I->haveInDatabase('auth_assignment', array('user_id' => 4, 'item_name' => 'viewer', 'created_at' => 1606839528));
        $user = $I->grabRecord(User::class, ['username' => 'viewer']);
        $I->amBearerAuthenticated($user->api_token);
        $I->sendDelete('/api/v1/form/1?name=core');
        $I->canSeeResponseCodeIs(\Codeception\Util\HttpCode::FORBIDDEN);
    }

    public function operatorCanList(\ApiTester $I)
    {
        /** @var User $user */
        $I->haveInDatabase('auth_assignment', array('user_id' => 3, 'item_name' => 'operator', 'created_at' => 1606839528));
        $user = $I->grabRecord(User::class, ['username' => 'operator']);
        $I->amBearerAuthenticated($user->api_token);
        $I->sendGet('/api/v1/form', [
            'name' => 'core',
            'filter[core_type]' => 'Rotary Core Barrel'
        ]);
        $I->canSeeResponseCodeIs(\Codeception\Util\HttpCode::OK);
    }

    public function operatorCanCreate(\ApiTester $I)
    {
        /** @var User $user */
        $I->haveInDatabase('auth_assignment', array('user_id' => 3, 'item_name' => 'operator', 'created_at' => 1606839528));
        $user = $I->grabRecord(User::class, ['username' => 'operator']);
        $I->amBearerAuthenticated($user->api_token);
        $I->sendPost('/api/v1/form?name=core', [
            "hole_id"=>1,
            "core"=>3,
            "curator"=>"",
            "core_ondeck"=>"2020-11-02 10:09:00",
            "core_type"=>"R",
            "drillers_top_depth"=>3.5,
            "drilled_length"=>20,
            "core_recovery"=>20,
            "section_count"=>2,
        ]);
        $I->canSeeResponseCodeIs(\Codeception\Util\HttpCode::CREATED);
    }

    public function operatorCanUpdate(\ApiTester $I)
    {
        /** @var User $user */
        $I->haveInDatabase('auth_assignment', array('user_id' => 3, 'item_name' => 'operator', 'created_at' => 1606839528));
        $user = $I->grabRecord(User::class, ['username' => 'operator']);
        $I->amBearerAuthenticated($user->api_token);
        $I->sendPut('/api/v1/form/1?name=core', [
            'core_type' => 'Rotary Core Barrel'
        ]);
        $I->canSeeResponseCodeIs(\Codeception\Util\HttpCode::OK);
    }

    public function operatorCanDelete(\ApiTester $I)
    {
        /** @var User $user */
        $I->haveInDatabase('auth_assignment', array('user_id' => 3, 'item_name' => 'operator', 'created_at' => 1606839528));
        $user = $I->grabRecord(User::class, ['username' => 'operator']);
        $I->amBearerAuthenticated($user->api_token);
        $I->sendDelete('/api/v1/form/1?name=sample');
        $I->canSeeResponseCodeIs(\Codeception\Util\HttpCode::NO_CONTENT);
    }

    public function singleFormView(\ApiTester $I)
    {
        $I->wantTo('Test if a user with single form view permission can view single form');
        /** @var User $user */
        $user = $I->grabRecord(User::class, ['username' => 'user']);
        $I->amBearerAuthenticated($user->api_token);
        $I->sendGet('/api/v1/form', [
            'name' => 'core',
            'filter[core_type]' => 'Rotary Core Barrel'
        ]);
        $I->canSeeResponseCodeIs(\Codeception\Util\HttpCode::FORBIDDEN);

        $I->haveInDatabase('auth_assignment', array('user_id' => 5, 'item_name' => 'form-core:view', 'created_at' => 1606839528));
        $I->sendGet('/api/v1/form', [
            'name' => 'core',
            'filter[core_type]' => 'Rotary Core Barrel'
        ]);
        $I->canSeeResponseCodeIs(\Codeception\Util\HttpCode::OK);
    }

    public function singleFormEdit(\ApiTester $I)
    {
        $I->wantTo('Test if a user with single form edit permission can view single form');
        /** @var User $user */
        $user = $I->grabRecord(User::class, ['username' => 'user']);
        $I->amBearerAuthenticated($user->api_token);
        $I->sendPost('/api/v1/form?name=core', [
            "hole_id"=>1,
            "core"=>3,
            "curator"=>"",
            "core_ondeck"=>"2020-11-02 10:09:00",
            "core_type"=>"R",
            "drillers_top_depth"=>3.5,
            "drilled_length"=>20,
            "core_recovery"=>20,
            "section_count"=>2,
        ]);
        $I->canSeeResponseCodeIs(\Codeception\Util\HttpCode::FORBIDDEN);

        $I->haveInDatabase('auth_assignment', array('user_id' => 5, 'item_name' => 'form-core:edit', 'created_at' => 1606839528));
        $I->sendPost('/api/v1/form?name=core', [
            "hole_id"=>1,
            "core"=>3,
            "curator"=>"",
            "core_ondeck"=>"2020-11-02 10:09:00",
            "core_type"=>"R",
            "drillers_top_depth"=>3.5,
            "drilled_length"=>20,
            "core_recovery"=>20,
            "section_count"=>2,
        ]);
        $I->canSeeResponseCodeIs(\Codeception\Util\HttpCode::CREATED);
    }

    public function singleFormPermissionGetListThroughGlobalController(\ApiTester $I)
    {
        $I->wantTo('Test if a user with single form view permission can get list using global controller');
        /** @var User $user */
        $user = $I->grabRecord(User::class, ['username' => 'user']);
        $I->amBearerAuthenticated($user->api_token);
        $I->sendGet('/api/v1/global?name=CoreCore', [
            "per-page"=>-1,
            "sort"=>'combined_id',
            "fields"=>'combined_id,id'
        ]);
        $I->canSeeResponseCodeIs(\Codeception\Util\HttpCode::FORBIDDEN);

        $I->haveInDatabase('auth_assignment', array('user_id' => 5, 'item_name' => 'form-core:view', 'created_at' => 1606839528));
        $I->sendGet('/api/v1/global?name=CoreCore', [
            "per-page"=>-1,
            "sort"=>'combined_id',
            "fields"=>'combined_id,id'
        ]);
        $I->canSeeResponseCodeIs(\Codeception\Util\HttpCode::OK);
    }
}
