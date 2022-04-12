<?php

use yii\db\Migration;

/**
 * Class m210605_214919_add_ldap_field_to_user
 */
class m210605_214919_add_ldap_field_to_user extends Migration
{
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {
        $this->addColumn('{{%user}}', 'is_ldap_user', $this->tinyInteger(1));
    }

    public function down()
    {
        $this->dropColumn('{{%user}}', 'is_ldap_user');
    }
}