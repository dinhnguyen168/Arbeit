<?php

use app\migrations\Migration;

/**
 * Class m181015_162413_list_values_table
 * Creates table "list_values"
 */
class m181015_162413_list_values_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('list_values', [
            'id'         => $this->primaryKey()->comment('Id Autoincrement'),
            'listname'         => $this->string(50)->notNull()->comment('Name of list (Name of imported L_-table)'),
            'display'          => $this->string(100)->notNull()->comment('Value to be shown'),
            'remark'          => $this->string(255)->comment('Additionally show information'),
            'sort'          => $this->integer(11)->unsigned()->comment('Optional: Order of values in the list; otherwise sorted by display')
        ]);
        $this->createIndex('Unique display', 'list_values', ['listname', 'display'], true);
        $this->createIndex('listname', 'list_values', ['listname'], false);
        $this->createIndex('Sort', 'list_values', ['sort', 'display'], false);
    }


    /**
     * {@inheritdoc}
     */
    public function safeDown() {
        $this->dropTable('list_values');
    }

}
