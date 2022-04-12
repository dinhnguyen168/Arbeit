<?php


class ReportShowMissingColumnsCest
{
    public function _fixtures()
    {
        return [
            'users' => [
                'class' => \app\tests\fixtures\UserFixture::class,
                // fixture data located in tests/_data/user.php
                'dataFile' => codecept_data_dir() . 'user.php'
            ],
            'projectExpedition' => [
                'class' => \app\tests\fixtures\ProjectExpeditionFixture::class,
                // fixture data located in tests/_data/user.php
                'dataFile' => codecept_data_dir() . 'project_expedition.php'
            ],
        ];
    }

    public function _before(\FunctionalTester $I, \ApiTester $AI)
    {
        $AI->haveInDatabase('auth_assignment', array('user_id' => 1, 'item_name' => 'sa', 'created_at' => 1606839528));
        \Yii::$app->getUser()->login(\app\models\core\User::findIdentity(1));
    }


    public function showSelectedColumns(\FunctionalTester $I)
    {
        $I->amOnRoute('report/List', ['model' => 'ProjectExpedition', 'columns' => 'id,program_id,exp_name,expedition,exp_acronym']);

        $I->see('List of Expedition records', 'span.report-name');
        $I->see('Acronym', 'th');
        $I->dontSee('Chief Scientists', 'th');
    }

    public function reportMissingColumn(\FunctionalTester $I)
    {
        $I->amOnRoute('report/List', ['model' => 'ProjectExpedition', 'columns' => 'id,program_id,exp_name,expedition,exp_acronym,MISSING_COLUMN']);
        $I->see('Problems in report', 'h1');
        $I->see('MISSING_COLUMN', 'div.alert ul li ul li');
    }

}
