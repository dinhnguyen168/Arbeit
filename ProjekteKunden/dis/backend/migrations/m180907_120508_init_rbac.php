<?php

use app\migrations\Migration;

/**
 * Class m180907_120508_init_rbac
 */
class m180907_120508_init_rbac extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        try {
            // create roles
            $auth = Yii::$app->authManager;
            $viewer = $auth->createRole('viewer');
            $auth->add($viewer);
            $operator = $auth->createRole('operator');
            $auth->add($operator);
            $auth->addChild($operator, $viewer);
            $developer = $auth->createRole('developer');
            $auth->add($developer);
            $auth->addChild($developer, $operator);
            $auth->addChild($developer, $viewer);
            $sa = $auth->createRole('sa');
            $auth->add($sa);
            $auth->addChild($sa, $developer);
            $auth->addChild($sa, $operator);
            $auth->addChild($sa, $viewer);
        } catch (\yii\base\Exception $e) {
            echo $e->getMessage();
            return false;
        } catch (\Exception $e) {
            echo "PHP exception\n";
            echo $e->getMessage();
            return false;
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m180907_120508_init_rbac cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m180907_120508_init_rbac cannot be reverted.\n";

        return false;
    }
    */
}
