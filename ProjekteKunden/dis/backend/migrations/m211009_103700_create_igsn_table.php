<?php

/**
 * Handles the creation of table `{{%dis_igsn}}`.
 */
class m211009_103700_create_igsn_table extends \app\migrations\Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%dis_igsn}}', [
            'id' => $this->primaryKey(),
            'igsn' => $this->string('50'),
            'model' => $this->string('100'),
            'model_id' => $this->integer()->unsigned(),
            'registered' => $this->tinyInteger()->unsigned()->defaultValue(0)
        ]);

        $this->createIndex('idx_unique_igsn', '{{%dis_igsn}}', ['igsn'], true);
        $this->createIndex('idx_unique_modelId', '{{%dis_igsn}}', ['model', 'model_id'], true);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%dis_igsn}}');
    }
}
