<?php

use yii\db\Migration;

/**
 * Class m190218_144637_add_token_expire_field
 */
class m190218_144637_add_token_expire_field extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%user}}', 'token_expire', $this->integer());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%user}}', 'token_expire');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190218_144637_add_token_expire_field cannot be reverted.\n";

        return false;
    }
    */
}
