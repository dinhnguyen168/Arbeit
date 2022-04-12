<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%message_of_the_day}}`.
 */
class m190719_133741_create_message_of_the_day_tables extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $migration = new \app\migrations\Migration();
        $this->createTable('{{%message_of_the_day}}', [
            'id' => $this->primaryKey(),
            'message' => $this->text()->notNull(),
            'date' => $this->date()->notNull(),
            'created_by' => $this->integer(),
            'updated_by' => $this->integer(),
            'created_at' => $this->integer(),
            'updated_at' => $this->integer()
        ]);
        $this->addForeignKey('fk-message_of_the_day-created-by-user', '{{%message_of_the_day}}', 'created_by', '{{%user}}', 'id', $migration->getCascade(), $migration->getCascade());
        $this->addForeignKey('fk-message_of_the_day-updated-by-user', '{{%message_of_the_day}}', 'updated_by', '{{%user}}', 'id', 'cascade', 'cascade');

        $this->createTable('{{%image_of_the_day}}', [
            'id' => $this->primaryKey(),
            'message_id' => $this->integer(),
            'image_id' => $this->integer(),
            'caption' => $this->text()
        ]);
        $this->addForeignKey('fk-message-image', '{{%image_of_the_day}}', 'message_id', '{{%message_of_the_day}}', 'id', 'cascade', 'cascade');
        $this->addForeignKey('fk-message-image-image', '{{%image_of_the_day}}', 'image_id', '{{%archive_file}}', 'id', 'cascade', 'cascade');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-message_of_the_day-created-by-user', '{{%message_of_the_day}}');
        $this->dropForeignKey('fk-message_of_the_day-updated-by-user', '{{%message_of_the_day}}');
        $this->dropForeignKey('fk-message-image', '{{%image_of_the_day}}');
        $this->dropForeignKey('fk-message-image-image', '{{%image_of_the_day}}');
        $this->dropTable('{{%image_of_the_day}}');
        $this->dropTable('{{%message_of_the_day}}');
    }
}
