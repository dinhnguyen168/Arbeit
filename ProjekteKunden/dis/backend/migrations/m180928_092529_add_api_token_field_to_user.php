<?php

use yii\db\Migration;

/**
 * Class m180928_092529_add_api_token_field_to_user
 */
class m180928_092529_add_api_token_field_to_user extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%user}}', 'api_token', $this->string(32)->null());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%user}}', 'api_token');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m180928_092529_add_api_token_field_to_user cannot be reverted.\n";

        return false;
    }
    */
}
