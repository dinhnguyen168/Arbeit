<?php


class DetailsReportCest
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
        ];
    }


    public function _before(\FunctionalTester $I, \ApiTester $AI)
    {
        $AI->haveInDatabase('auth_assignment', array('user_id' => 1, 'item_name' => 'sa', 'created_at' => 1606839528));
        \Yii::$app->getUser()->login(\app\models\core\User::findIdentity(1));
        $I->amOnRoute('report/Details', ['model' => 'ProjectExpedition', 'id' => 1]);
    }

    public function printReportName(\FunctionalTester $I)
    {
        $I->see('Details of Expedition record', 'span.report-name');
    }
}
