<?php namespace list_values;

use app\models\core\User;
use app\modules\api\common\controllers\FormController;
use app\tests\fixtures\UserFixture;

class basicListValuesCest
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
        /** @var User $user */
        $I->haveInDatabase('auth_assignment', array('user_id' => 1, 'item_name' => 'sa', 'created_at' => 1606839528));
        $user = $I->grabRecord(User::class, ['username' => 'sa']);
        $I->amBearerAuthenticated($user->api_token);
    }

    // tests
    public function tryTestIndexAction(\ApiTester $I)
    {
        $I->wantTo('ensure that api returns a specific list items');
        $I->sendGet('api/v1/list-values', [
            'name' => 'ListValues',
            'filter[listname]' => 'ANALYST'
        ]);
        $I->canSeeResponseCodeIs(\Codeception\Util\HttpCode::OK);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            "items" => [
                ["display" => "CK", "remark" => "Cindy Kunkel", "sort" => "1"],
                ["display" => "KH", "remark" => "Katja Heeschen", "sort" => "1"],
                ["display" => "KB", "remark" => "Knut Behrends", "sort" => "1"],
                ["display" => "TG", "remark" => "Thomas Gibson", "sort" => "0"],
                ["display" => "SPH", "remark" => "Steve Hesselbo", "sort" => "0"]
            ]
        ]);
    }

    // tests
    public function tryTestCreateAction(\ApiTester $I)
    {
        $I->wantTo('ensure that api creates a list item');
        $I->sendPost('api/v1/list-values?name=ListValues', [
            "display" => "AAA",
            "listname" => "ANALYST",
            "remark" => "AAAAAAA",
            "sort" => 13,
        ]);
        $I->canSeeResponseCodeIs(\Codeception\Util\HttpCode::CREATED);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            "display" => "AAA",
            "list_id" => 2,
            "remark" => "AAAAAAA",
            "sort" => 13,
        ]);
    }

    public function tryTestPutAction(\ApiTester $I)
    {
        $I->wantTo('ensure that api updates list item');
        $idToUpdate = 1;
        $I->sendPut("api/v1/list-values/$idToUpdate?name=ListValues", [
            "display" => "new display",
            "remark" => "new remark",
            "sort" => 0,
        ]);
        $I->canSeeResponseCodeIs(\Codeception\Util\HttpCode::OK);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            "display" => "new display",
            "remark" => "new remark",
            "sort" => 0,
        ]);
    }

    public function tryTestDeleteAction(\ApiTester $I)
    {
        $I->wantTo('ensure that api deletes list item');
        $idToDelete = 1;
        $I->sendDelete("api/v1/list-values/$idToDelete?name=ListValues");
        $I->canSeeResponseCodeIs(\Codeception\Util\HttpCode::NO_CONTENT);
    }

    public function tryTestGetListNamesAction(\ApiTester $I)
    {
        $I->wantTo('ensure that api returns all lists names');
        $I->sendGet("api/v1/list-values/list-names");
        $I->canSeeResponseCodeIs(\Codeception\Util\HttpCode::OK);
        $I->seeResponseIsJson();
        $I->seeResponseEquals('["ANALYST","CORE_TYPE","LONG_LIST","SPECIAL"]');
    }

    public function tryTestGetListInfo(\ApiTester $I)
    {
        $I->wantTo('ensure that api returns list info');
        $I->sendGet("api/v1/list-values/list", [
            'listname' => 'ANALYST'
        ]);
        $I->canSeeResponseCodeIs(\Codeception\Util\HttpCode::OK);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(['id' => 2, "list_name" => "ANALYST", "is_locked" => 0]);
    }

    public function tryTestUpdateListInfo(\ApiTester $I)
    {
        $I->wantTo('ensure that api updates list info');
        $I->sendPUT("api/v1/list-values/list?listname=ANALYST", [
            "is_locked" => true,
            "list_uri" => 'http://uri.example.de'
        ]);
        $I->canSeeResponseCodeIs(\Codeception\Util\HttpCode::OK);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(["list_uri" => 'http://uri.example.de', "is_locked" => 1]);
    }

    public function doesNotLimitItemsListTo50(\ApiTester $I)
    {
        $I->wantTo('ensure that api does not limit list items to 50 items');
        $I->sendGet("api/v1/list-values", [
            'filter[listname]' => 'LONG_LIST'
        ]);
        $I->canSeeResponseCodeIs(\Codeception\Util\HttpCode::OK);
        $I->seeResponseIsJson();
        $result = json_decode($I->grabResponse(), true);
        $I->assertCount(200, $result['items']);
    }
}
