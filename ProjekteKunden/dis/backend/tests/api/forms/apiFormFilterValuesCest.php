<?php namespace forms;


use app\models\core\User;
use app\modules\api\common\controllers\FormController;
use app\tests\fixtures\UserFixture;

class apiFormFilterValuesCest
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
        $I->haveInDatabase('auth_assignment', array('user_id' => 1, 'item_name' => 'sa', 'created_at' => 1606839528));
        $user = $I->grabRecord(User::class, ['username' => 'sa']);
        $I->amBearerAuthenticated($user->api_token);
    }

    protected function getFilterRecords (\ApiTester $I, $filter = [], $expectFail = false)
    {
        $conditions = ['name' => 'core'];
        foreach ($filter AS $key => $value) $conditions['filter[' . $key . ']'] = $value;
        $I->sendGet('/api/v1/form', $conditions);

        if ($expectFail)
            $I->canSeeResponseCodeIs(\Codeception\Util\HttpCode::BAD_REQUEST);
        else
            $I->canSeeResponseCodeIs(\Codeception\Util\HttpCode::OK);

        $response = json_decode($I->grabResponse(), true);
        if (isset($response["items"]))
            return $response["items"];
        else
            return [];
    }

    // tests
    public function filterAll(\ApiTester $I)
    {
        $records = $this->getFilterRecords($I, []);
        $I->assertCount(2, $records);
    }

    public function filterNull(\ApiTester $I)
    {
        $records = $this->getFilterRecords($I, ['core_diameter' => '==NULL']);
        $I->assertCount(1, $records);
        $I->assertEquals(2, $records[0]["id"]);

        $records = $this->getFilterRecords($I, ['core_diameter' => '!==NULL']);
        $I->assertCount(1, $records);
        $I->assertEquals(1, $records[0]["id"]);

        $records = $this->getFilterRecords($I, ['core_diameter' => 'NULL'], true);

        $records = $this->getFilterRecords($I, ['core_diameter' => '=NULL'], true);
    }


    public function filterNumber(\ApiTester $I)
    {
        $records = $this->getFilterRecords($I, ['drillers_top_depth' => '3.5']);
        $I->assertCount(1, $records);
        $I->assertEquals(1, $records[0]["id"]);

        $records = $this->getFilterRecords($I, ['drillers_top_depth' => '=3.5']);
        $I->assertCount(1, $records);
        $I->assertEquals(1, $records[0]["id"]);

        $records = $this->getFilterRecords($I, ['drillers_top_depth' => '!=3.5']);
        $I->assertCount(1, $records);
        $I->assertEquals(2, $records[0]["id"]);

        $records = $this->getFilterRecords($I, ['drillers_top_depth' => '<=3.5']);
        $I->assertCount(1, $records);
        $I->assertEquals(1, $records[0]["id"]);

        $records = $this->getFilterRecords($I, ['drillers_top_depth' => '>=3.5']);
        $I->assertCount(2, $records);

        $records = $this->getFilterRecords($I, ['drillers_top_depth' => '>3.5']);
        $I->assertCount(1, $records);
        $I->assertEquals(2, $records[0]["id"]);

        $records = $this->getFilterRecords($I, ['drillers_top_depth' => '<3.5']);
        $I->assertCount(0, $records);
    }


    public function filterDate(\ApiTester $I)
    {
        $records = $this->getFilterRecords($I, ['core_ondeck' => '<2020-11-02']);
        $I->assertCount(0, $records);

        $records = $this->getFilterRecords($I, ['core_ondeck' => '<2020-11-03']);
        $I->assertCount(1, $records);
        $I->assertEquals(1, $records[0]["id"]);

        $records = $this->getFilterRecords($I, ['core_ondeck' => '<2020-11-04']);
        $I->assertCount(2, $records);

        // regex; see more examples in string test below
        $records = $this->getFilterRecords($I, ['core_ondeck' => '2020-11-03 10:09:00']);
        $I->assertCount(1, $records);
        $I->assertEquals(2, $records[0]["id"]);

        // not regex
        $records = $this->getFilterRecords($I, ['core_ondeck' => '!=2020-11-03 10:09:00']);
        $I->assertCount(1, $records);
        $I->assertEquals(1, $records[0]["id"]);

        // regex; see more examples in string test below
        $records = $this->getFilterRecords($I, ['core_ondeck' => '10:09']);
        $I->assertCount(2, $records);
   }

    public function filterBoolean(\ApiTester $I)
    {
        $records = $this->getFilterRecords($I, ['core_oriented' => 'yes']);
        $I->assertCount(1, $records);
        $I->assertEquals(1, $records[0]["id"]);

        $records = $this->getFilterRecords($I, ['core_oriented' => 'y']);
        $I->assertCount(1, $records);
        $I->assertEquals(1, $records[0]["id"]);

        $records = $this->getFilterRecords($I, ['core_oriented' => 'true']);
        $I->assertCount(1, $records);
        $I->assertEquals(1, $records[0]["id"]);

        $records = $this->getFilterRecords($I, ['core_oriented' => '1']);
        $I->assertCount(1, $records);
        $I->assertEquals(1, $records[0]["id"]);

        $records = $this->getFilterRecords($I, ['core_oriented' => 'n']);
        $I->assertCount(1, $records);
        $I->assertEquals(2, $records[0]["id"]);

        $records = $this->getFilterRecords($I, ['core_oriented' => 'false']);
        $I->assertCount(1, $records);
        $I->assertEquals(2, $records[0]["id"]);

        $records = $this->getFilterRecords($I, ['core_oriented' => 'wrong'], true);
    }


    public function filterString(\ApiTester $I)
    {
        // no regex, compare if value is identical
        $records = $this->getFilterRecords($I, ['igsn' => '==ICDP5065EC10001']);
        $I->assertCount(1, $records);
        $I->assertEquals(1, $records[0]["id"]);

        // no regex, compare if value is identical
        $records = $this->getFilterRecords($I, ['igsn' => '==5065EC']);
        $I->assertCount(0, $records);

        // no regex; compare is value is not identical
        $records = $this->getFilterRecords($I, ['igsn' => '!==5065EC']);
        $I->assertCount(2, $records);

        // no regex, so search for "^ICDP5065EC10001$" in content
        $records = $this->getFilterRecords($I, ['igsn' => '==^ICDP5065EC10001$']);
        $I->assertCount(0, $records);

        // no regex, search for "^ICDP5065EC10001$" NOT in content
        $records = $this->getFilterRecords($I, ['igsn' => '!==^ICDP5065EC10001$']);
        $I->assertCount(2, $records);


        // no regex, compare alphabetically
        $records = $this->getFilterRecords($I, ['igsn' => '>ICDP5065EC10001']);
        $I->assertCount(1, $records);
        $I->assertEquals(2, $records[0]["id"]);

        // no regex, compare alphabetically
        $records = $this->getFilterRecords($I, ['igsn' => '>=ICDP5065EC10001']);
        $I->assertCount(2, $records);

        // no regex, compare alphabetically
        $records = $this->getFilterRecords($I, ['igsn' => '>ICDP5065']);
        $I->assertCount(2, $records);

        // no regex, compare alphabetically
        $records = $this->getFilterRecords($I, ['igsn' => '<ICDP5065']);
        $I->assertCount(0, $records);


        // search by regex
        $records = $this->getFilterRecords($I, ['igsn' => 'ICDP5065EC10001']);
        $I->assertCount(1, $records);
        $I->assertEquals(1, $records[0]["id"]);

        // regex with start and end: last digit (1|2) is missing
        $records = $this->getFilterRecords($I, ['igsn' => '^ICDP5065EC1000$']);
        $I->assertCount(0, $records);

        // search part by regex; prefix of "=" does not change anything
        $records = $this->getFilterRecords($I, ['igsn' => '=5065EC10001']);
        $I->assertCount(1, $records);
        $I->assertEquals(1, $records[0]["id"]);

        // complex regex
        $records = $this->getFilterRecords($I, ['igsn' => '^ICDP.+EC10+(1|2)']);
        $I->assertCount(2, $records);

        // complex regex, prefix with not: search for isgn does not match
        $records = $this->getFilterRecords($I, ['igsn' => '!=^ICDP.+EC10+(1|2)']);
        $I->assertCount(0, $records);

    }

    public function filterBasedOnListValueRemarks(\ApiTester $I)
    {
        // regex match value or display value of ANALYST listvalue
        $this->getFilterRecords($I, ['core_type' => 'Rotary Core']);
        $I->seeResponseContainsJson(['items' => [['core_type' => 'R']]]);

        // no regex, value identical or display value of ANALYST listvalue identical
        $records = $this->getFilterRecords($I, ['core_type' => '==R']);
        $I->seeResponseContainsJson(['items' => [['core_type' => 'R']]]);

        // no regex, value identical or display value of ANALYST listvalue identical
        $records = $this->getFilterRecords($I, ['core_type' => '==Rotary Core Barrel (RCB)']);
        $I->seeResponseContainsJson(['items' => [['core_type' => 'R']]]);

    }


}
