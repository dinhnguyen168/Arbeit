<?php

use yii\db\Migration;

/**
 * Class m181015_152554_copy_dis_templates
 */
class m181015_152554_copy_dis_templates extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        \app\commands\UpgradeController::copyDisTemplatesFiles();
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m220117_135001_copy_dis_templates cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m220117_135001_copy_dis_templates cannot be reverted.\n";

        return false;
    }
    */
}
