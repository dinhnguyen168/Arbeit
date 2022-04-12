<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%post}}`.
 */
class m190716_140314_create_post_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $migration = new \app\migrations\Migration();
        $this->createTable('{{%post}}', [
            'id' => $this->primaryKey(),
            'text' => $this->text(),
            'created_by' => $this->integer(),
            'updated_by' => $this->integer(),
            'created_at' => $this->integer(),
            'updated_at' => $this->integer()
        ]);
        $this->addForeignKey('fk-post-created-by-user', '{{%post}}', 'created_by', '{{%user}}', 'id', $migration->getCascade(), $migration->getCascade());
        $this->addForeignKey('fk-post-updated-by-user', '{{%post}}', 'updated_by', '{{%user}}', 'id', 'cascade', 'cascade');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-post-created-by-user', '{{%post}}');
        $this->dropForeignKey('fk-post-updated-by-user', '{{%post}}');
        $this->dropTable('{{%post}}');
    }
}
