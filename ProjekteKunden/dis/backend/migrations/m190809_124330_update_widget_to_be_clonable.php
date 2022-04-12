<?php

use yii\db\Migration;

/**
 * Class m190809_124330_update_widget_to_be_clonable
 */
class m190809_124330_update_widget_to_be_clonable extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $migration = new \app\migrations\Migration();
        $this->addColumn('{{%widgets}}', 'deletable', $migration->getBooleanType(true).' DEFAULT 0');
        $this->addColumn('{{%widgets}}', 'cloneable', $migration->getBooleanType(true).' DEFAULT 0');
        $this->update('{{%widgets}}', ['type' => 'SimpleChartWidget', 'cloneable' => 1], ['type' => 'DrillingProgressWidget']);
        $this->update('{{%widgets}}', ['title' => 'SimpleChartWidget Title'], [
            'type' => 'SimpleChartWidget',
            'title' => 'DrillingProgressWidget Title'
        ]);
        $this->update('{{%widgets}}', ['subtitle' => 'SimpleChartWidget Subtitle'], [
            'type' => 'SimpleChartWidget',
            'subtitle' => 'DrillingProgressWidget Subtitle'
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->update('{{%widgets}}', ['subtitle' => 'DrillingProgressWidget Subtitle'], [
            'type' => 'SimpleChartWidget',
            'subtitle' => 'SimpleChartWidget Subtitle'
        ]);
        $this->update('{{%widgets}}', ['title' => 'DrillingProgressWidget Title'], [
            'type' => 'SimpleChartWidget',
            'title' => 'SimpleChartWidget Title'
        ]);
        $this->update('{{%widgets}}', ['type' => 'DrillingProgressWidget'], ['type' => 'SimpleChartWidget']);
        $this->dropColumn('{{%widgets}}', 'deletable');
        $this->dropColumn('{{%widgets}}', 'cloneable');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190809_124330_update_widget_to_be_clonable cannot be reverted.\n";

        return false;
    }
    */
}
