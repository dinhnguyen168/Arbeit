<?php

/**
 * Class m190717_122306_add_colors_to_widgets
 */
class m190717_122306_add_colors_to_widgets extends \app\migrations\Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%widgets}}', 'color', $this->string(50));
        $this->addColumn('{{%widgets}}', 'is_dark', $this->boolean()->defaultValue(1));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%widgets}}', 'color');
        $this->dropColumn('{{%widgets}}', 'is_dark');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190717_122306_add_colors_to_widgets cannot be reverted.\n";

        return false;
    }
    */
}
