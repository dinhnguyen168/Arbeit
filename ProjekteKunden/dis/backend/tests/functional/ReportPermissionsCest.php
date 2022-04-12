<?php


class ReportPermissionsCest
{
    public function _fixtures()
    {
        return [
            'users' => [
                'class' => \app\tests\fixtures\UserFixture::class,
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
        ];
    }


    public function _before(\FunctionalTester $I, \ApiTester $AI)
    {
        $AI->haveInDatabase('auth_assignment', array('user_id' => 1, 'item_name' => 'sa', 'created_at' => 1606839528));
    }


    public function unauthorizedAccess(\FunctionalTester $I){
        \Yii::$app->getUser()->logout();
        $I->amOnRoute('report/Details', ['model' => 'ProjectExpedition', 'id' => 1]);
        $I->canSeeResponseCodeIs(403);

        \Yii::$app->getUser()->login(\app\models\core\User::findIdentity(1));
        $I->amOnRoute('report/Details', ['model' => 'ProjectExpedition', 'id' => 1]);
        $I->canSeeResponseCodeIs(200);
        \Yii::$app->getUser()->logout();
    }

    public function invalidModel(\FunctionalTester $I){
        \Yii::$app->getUser()->login(\app\models\core\User::findIdentity(1));
        $I->amOnRoute('report/CoreSections', ['model' => 'ProjectExpedition', 'id' => 1]);
        $I->canSeeResponseCodeIs(500);
        $I->see('report can not be used for model');
        \Yii::$app->getUser()->logout();
    }


}
