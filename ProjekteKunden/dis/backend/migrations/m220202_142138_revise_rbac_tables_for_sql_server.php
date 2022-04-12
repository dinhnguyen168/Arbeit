<?php

use yii\db\Migration;

/**
 * Class m220202_142138_revise_rbac_tables_for_sql_server
 */
class m220202_142138_revise_rbac_tables_for_sql_server extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        if ($this->db->driverName == 'sqlsrv') {
            echo "In use sql server, some changes to rbac table will be made \n";
            $this->alterColumn('auth_item', 'data', 'NVARCHAR(MAX)');
            $this->alterColumn('auth_rule', 'data', 'NVARCHAR(MAX)');
            echo "Data type of data columns set to be NVARCHAR instead of VARBINARY \n";
        } else {
            echo "Sql server not in use, no changes required \n";
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m220202_142138_revise_rbac_tables_for_sql_server cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m220202_142138_revise_rbac_tables_for_sql_server cannot be reverted.\n";

        return false;
    }
    */
}
