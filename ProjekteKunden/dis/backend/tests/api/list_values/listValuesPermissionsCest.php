<?php namespace list_values;


use app\models\core\User;
use app\modules\api\common\controllers\FormController;
use app\tests\fixtures\UserFixture;

class listValuesPermissionsCest
{
    public function _fixtures()
    {
        return [
            'users' => [
                'class' => UserFixture::class,
                // fixture data located in tests/_data/user.php
                'dataFile' => codecept_data_dir() . 'user.php'
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

    // tests
    public function testListValuesDeveloperPermission(\ApiTester $I)
    {
        $I->wantTo('ensure that developers unable to update locked lists');
        /** @var User $user */
        $I->haveInDatabase('auth_assignment', array('user_id' => 2, 'item_name' => 'developer', 'created_at' => 1606839528));
        $developer = $I->grabRecord(User::class, ['username' => 'developer']);
        $I->amBearerAuthenticated($developer->api_token);
        $I->sendPost('api/v1/list-values', [
            "display" => "AAA",
            "listname" => "SPECIAL",
            "remark" => "AAAAAAA",
            "sort" => 13,
        ]);
        $I->canSeeResponseCodeIs(\Codeception\Util\HttpCode::FORBIDDEN);
        $I->sendPut('api/v1/list-values/19', [
            "display" => "AAA",
            "remark" => "AAAAAAA",
            "sort" => 13,
        ]);
        $I->canSeeResponseCodeIs(\Codeception\Util\HttpCode::FORBIDDEN);
        $I->sendPut("api/v1/list-values/list?listname=SPECIAL", [
            "is_locked" => true,
            "list_uri" => 'http://uri.example.de'
        ]);
        $I->canSeeResponseCodeIs(\Codeception\Util\HttpCode::FORBIDDEN);
    }

    public function testListValuesSaPermission (\ApiTester $I) {
        $I->wantTo('ensure that api allows only administrator can update locked lists');
        $I->haveInDatabase('auth_assignment', array('user_id' => 1, 'item_name' => 'sa', 'created_at' => 1606839528));
        $sa = $I->grabRecord(User::class, ['username' => 'sa']);
        $I->amBearerAuthenticated($sa->api_token);
        $I->sendPost('api/v1/list-values?name=ListValues', [
            "display" => "AAA",
            "listname" => "SPECIAL",
            "remark" => "AAAAAAA",
            "sort" => 13,
        ]);
        $I->canSeeResponseCodeIs(\Codeception\Util\HttpCode::CREATED);
        $I->sendPUT("api/v1/list-values/list?listname=ANALYST", [
            "is_locked" => true,
            "list_uri" => 'http://uri.example.de'
        ]);
        $I->canSeeResponseCodeIs(\Codeception\Util\HttpCode::OK);
    }

    public function testOnlySaCanLockList (\ApiTester $I) {
        $I->wantTo('ensure that only administrator can lock a list');
        $I->haveInDatabase('auth_assignment', array('user_id' => 2, 'item_name' => 'developer', 'created_at' => 1606839528));
        $developer = $I->grabRecord(User::class, ['username' => 'developer']);
        $I->amBearerAuthenticated($developer->api_token);
        $I->sendPut("api/v1/list-values/list?listname=ANALYST", [
            "is_locked" => true
        ]);
        $I->canSeeResponseCodeIs(\Codeception\Util\HttpCode::FORBIDDEN);
        $I->haveInDatabase('auth_assignment', array('user_id' => 1, 'item_name' => 'sa', 'created_at' => 1606839528));
        $sa = $I->grabRecord(User::class, ['username' => 'sa']);
        $I->amBearerAuthenticated($sa->api_token);
        $I->sendPut("api/v1/list-values/list?listname=ANALYST", [
            "is_locked" => true
        ]);
        $I->canSeeResponseCodeIs(\Codeception\Util\HttpCode::OK);
    }
}
