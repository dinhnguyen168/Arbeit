<?php

use yii\db\Migration;

/**
 * Class m201030_115123_create_files_from_templates
 */
class m201030_115123_create_files_from_templates extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function up()
    {
        // Yii::$app->runAction('seed/templates-files');
        Yii::$app->runAction('fix-data/update-models', [null, 1]);
        Yii::$app->runAction('fix-data/update-forms', [null, 1]);
        Yii::$app->runAction('seed/form-permissions');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m201030_115123_create_files_from_templates cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m201030_115123_create_files_from_templates cannot be reverted.\n";

        return false;
    }
    */
}
