<?php

use yii\db\Migration;

/**
 * Class m201124_112123_change_value_list_table
 */
class m201124_112123_change_value_list_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function up()
    {
//        $this->createTable('list_values', [
//            'id'         => $this->primaryKey()->comment('Id Autoincrement'),
//            'listname'         => $this->string(50)->notNull()->comment('Name of list (Name of imported L_-table)'),
//            'display'          => $this->string(100)->notNull()->comment('Value to be shown'),
//            'remark'          => $this->string(255)->comment('Additionally show information'),
//            'sort'          => $this->integer(11)->unsigned()->comment('Optional: Order of values in the list; otherwise sorted by display')
//        ]);
//        $this->createIndex('Unique display', 'list_values', ['listname', 'display'], true);
//        $this->createIndex('listname', 'list_values', ['listname'], false);
//        $this->createIndex('Sort', 'list_values', ['sort', 'display'], false);
        $transaction = $this->db->beginTransaction();
        try {
            $this->createTable("{{%dis_list}}", [
                'id' => $this->primaryKey(),
                'list_name' => $this->string(50)->notNull()->comment('Name of list)'),
                'is_locked' => $this->boolean()->notNull()->defaultValue(0),
                'list_uri' => $this->string(255)->null()->comment('special value uri'),
            ]);
            $this->createIndex('unique_list_name', '{{%dis_list}}', 'list_name', true);
            $this->createTable('{{%dis_list_item}}', [
                'id' => $this->primaryKey(),
                'list_id' => $this->integer()->notNull(),
                'display' => $this->string(100)->notNull()->comment('Value to be shown'),
                'remark' => $this->string(255)->comment('Additionally show information'),
                'uri' => $this->string(255)->null()->comment('special value uri'),
                'sort' => $this->integer(11)->unsigned()->comment('Optional: Order of values in the list; otherwise sorted by display'),
            ]);
            $this->addForeignKey('list__list_value', '{{%dis_list_item}}', 'list_id', '{{%dis_list}}', 'id', 'cascade');
            $this->createIndex('list_unique_items_index', '{{%dis_list_item}}', ['list_id', 'display'], true);
            $this->createIndex('list_item_sort_index', '{{%dis_list_item}}', 'sort', false);
            $transaction->commit();
        } catch (\Exception $e) {
            echo $e->getMessage();
            $transaction->rollBack();
            return false;
        } catch (\Throwable $e) {
            echo $e->getMessage();
            $transaction->rollBack();
            return false;
        }

        Yii::$app->runAction('seed/copy-list-values-to-new-tables');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('list__list_value', '{{%dis_list_item}}');
        $this->dropTable('{{%dis_list}}');
        $this->dropTable('{{%dis_list_item}}');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m201124_112123_change_value_list_table cannot be reverted.\n";

        return false;
    }
    */
}
