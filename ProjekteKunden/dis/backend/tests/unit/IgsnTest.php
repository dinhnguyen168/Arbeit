<?php namespace templates;

use app\components\Igsn;
use app\components\IgsnException;
use app\tests\fixtures\ProjectExpeditionFixture;
use app\tests\fixtures\ProjectSiteFixture;
use app\tests\fixtures\ProjectHoleFixture;
use app\tests\fixtures\CoreCoreFixture;
use app\models\CoreCore;
use app\models\core\DisIgsn;

class IgsnTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $igsn;


    public function _fixtures()
    {
        return [
            'ProjectExpedition' => [
                'class' => ProjectExpeditionFixture::class,
                'dataFile' => codecept_data_dir() . 'project_expedition.php'
            ],
            'ProjectSite' => [
                'class' => ProjectSiteFixture::class,
                'dataFile' => codecept_data_dir() . 'project_site.php'
            ],
            'ProjectHole' => [
                'class' => ProjectHoleFixture::class,
                'dataFile' => codecept_data_dir() . 'project_hole.php'
            ],
            'CoreCore' => [
                'class' => CoreCoreFixture::class,
                'dataFile' => codecept_data_dir() . 'core_core.php'
            ],
        ];
    }

    protected function _before()
    {
        $this->igsn = new Igsn();
        \Yii::$app->setComponents(["igsn" => $this->igsn]);
        \app\models\core\DisIgsn::deleteAll();
    }

    protected function _after()
    {
    }

    protected function setMethodIcdp($saveToDatabase = false) {
        $settings = [
            'method' => Igsn::METHOD_ICDP_CLASSIC,
            'saveToDatabase' => $saveToDatabase
        ];
        $this->igsn->__construct($settings);
    }

    protected function setMethodFixedPrefix($saveToDatabase = false) {
        $settings = [
            'method' => Igsn::METHOD_FIXED_PREFIX,
            'prefix' => 'AW',
            'variablePartLength' => 8,
            'maxLength' => 16
        ];
        $this->igsn->__construct($settings);
        $this->igsn->saveToDatabase = $saveToDatabase;
    }

    public function testSaveIgsn() {
        $this->setMethodIcdp(true);

        $core1 = \app\models\CoreCore::find()->where(['id' => 1])->one();
        $core2 = \app\models\CoreCore::find()->where(['id' => 2])->one();

        // IGSN can be saved
        $this->igsn->saveIgsn ($core1->igsn, $core1);
        $disIgsn = DisIgsn::find()->where(["model" => "CoreCore"])->andWhere(["model_id" => $core1->id])->one();
        self::assertNotNull($disIgsn);
        self::assertEquals($core1->igsn, $disIgsn->igsn);

        // Save same IGSN for the model again
        try {
            $this->igsn->saveIgsn ($core1->igsn, $core1);
        }
        catch (IgsnException $e) {
            self::assertTrue(false, "Exception has been thrown when saving the same IGSN for a model again: " . $e->getMessage());
        }

        // Cannot change IGSN of existing entry
        $thrown = false;
        try {
            $this->igsn->saveIgsn("SomeOtherIgsn", $core1);
        }
        catch (IgsnException $e) {
            self::assertEquals(IgsnException::CODE_IGSN_CHANGED, $e->getCode(), "Exception CODE_IGSN_CHANGED should have been thrown, instead got " . $e->getMessage());
            $thrown = true;
        }
        self::assertTrue($thrown, "No exception has been thrown when changing IGSN of a model");

        // Cannot save same IGSN for other model
        $thrown = false;
        try {
            $this->igsn->saveIgsn($core1->igsn, $core2);
        }
        catch (IgsnException $e) {
            self::assertEquals(IgsnException::CODE_DUPLICATE_IGSN, $e->getCode(), "Exception CODE_DUPLICATE_IGSN should have been thrown, instead got " . $e->getMessage());
            $thrown = true;
        }
        self::assertTrue($thrown, "No exception has been thrown when saving duplicate IGSN");

        // Try to use FIXED_PREFIX mode without activated database
        $this->setMethodFixedPrefix(false);
        $thrown = false;
        try {
            $this->igsn->saveIgsn($core1->igsn, $core1);
        }
        catch (IgsnException $e) {
            self::assertEquals(IgsnException::CODE_SAVE_DISABLED, $e->getCode(), "Exception CODE_SAVE_DISABLED should have been thrown, instead got " . $e->getMessage());
            $thrown = true;
        }
        self::assertTrue($thrown, "No exception has been thrown when trying to use METHOD_FIXED_PREFIX without activated database");

    }



    public function testCreateIgsn() {
        $core1 = \app\models\CoreCore::find()->where(['id' => 1])->one();
        $core2 = \app\models\CoreCore::find()->where(['id' => 2])->one();

        $this->setMethodIcdp(false);

        // Generated IGSN is correct
        $num = $this->igsn->createIgsn($core1, "C");
        self::assertEquals($core1->igsn, $num);

        // Generated IGSN stays the same
        $num2 = $this->igsn->createIgsn($core1, "C");
        self::assertEquals($core1->igsn, $num2);


        // Generate prefix IGSN
        $this->setMethodFixedPrefix(true);
        $num = $this->igsn->createIgsn($core1);
        self::assertStringStartsWith($this->igsn->prefix, $num);

        // Cannot save same IGSN for other model
        $thrown = false;
        try {
            $num2 = $this->igsn->createIgsn($core1);
        }
        catch (IgsnException $e) {
            self::assertEquals(IgsnException::CODE_IGSN_CHANGED, $e->getCode(), "Exception CODE_DUPLICATE_IGSN should have been thrown, instead got " . $e->getMessage());
            $thrown = true;
        }
        self::assertTrue($thrown, "No exception has been thrown when creating new IGSN for model with existing IGSN");

        // Other model gets different IGSN
        $num2 = $this->igsn->createIgsn($core2);
        self::assertNotEquals($num, $num2);
    }

    public function testIgsnBehavior() {
        self::assertNotNull(\Yii::$app);
        self::assertNotNull(\Yii::$app->igsn);

        $core1 = \app\models\CoreCore::find()->where(['id' => 1])->one();
        $core2 = \app\models\CoreCore::find()->where(['id' => 2])->one();

/*
        // Generate prefix IGSN
        $this->setMethodIcdp(true);
        $num = $core1->igsn;
        $core1->igsn = "";
        $core1->save();
        self::assertEquals($num, $core1->igsn, "IGSN has not been created on save of model");
*/

    }

}
