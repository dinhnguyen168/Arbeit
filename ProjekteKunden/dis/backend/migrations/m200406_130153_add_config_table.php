<?php

use yii\db\Migration;

/**
 * Class m200406_130153_add_config_table
 */
class m200406_130153_add_config_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable("{{%app_config}}", [
            'id' => $this->primaryKey(),
            'key' => $this->string(128),
            'value' => $this->text()
        ]);
        $this->createIndex('app_config_key_index', '{{%app_config}}', 'key', true);
        $this->createTable("{{%user_config}}", [
            'id' => $this->primaryKey(),
            'key' => $this->string(128),
            'value' => $this->text(),
            'user_id' => $this->integer()
        ]);
        $this->createIndex('user_config_key_index', '{{%user_config}}', 'key', true);
        $this->addForeignKey('user_config_user_fk', '{{%user_config}}', 'user_id', '{{%user}}', 'id', 'CASCADE', 'CASCADE');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropIndex('user_config_key_index', '{{%user_config}}');
        $this->dropForeignKey('user_config_user_fk', '{{%user_config}}');
        $this->dropTable("{{%user_config}}");
        $this->dropIndex('app_config_key_index', '{{%app_config}}');
        $this->dropTable("{{%app_config}}");
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m200406_130153_add_config_table cannot be reverted.\n";

        return false;
    }
    */
}
