<?php

use app\migrations\Migration;

/**
 * Class m181015_162554_create_tables_from_templates
 * Creates table "archive_file"
 */
class m181015_162554_create_tables_from_templates extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function up()
    {
        Yii::$app->runAction('seed/templates-tables');
    }


    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m181015_162554_create_tables_from_templates.\n";

        return false;
    }

}
