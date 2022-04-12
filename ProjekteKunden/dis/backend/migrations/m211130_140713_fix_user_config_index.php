<?php

use yii\db\Migration;

/**
 * Class m211130_140713_fix_user_config_index
 *
 * Fix index in table user_config to be unique over (user_id AND key) and not only over key
 */
class m211130_140713_fix_user_config_index extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->dropIndex('user_config_key_index', '{{%user_config}}');
        $this->createIndex('user_config_key_index', '{{%user_config}}', 'user_id,key', true);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropIndex('user_config_key_index', '{{%user_config}}');
        $this->createIndex('user_config_key_index', '{{%user_config}}', 'key', true);
    }

}
