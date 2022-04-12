<?php

/**
 * Handles the creation of table `{{%widgets}}`.
 */
class m190709_135452_create_widgets_table extends \app\migrations\Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%widgets}}', [
            'id' => $this->primaryKey(),
            'type' => $this->string('128'),
            'title' => $this->string('256'),
            'subtitle' => $this->string('256'),
            'active' => $this->boolean()->defaultValue(1),
            'xs_size' => $this->tinyInteger()->unsigned()->defaultValue(12),
            'sm_size' => $this->tinyInteger()->unsigned()->defaultValue(6),
            'md_size' => $this->tinyInteger()->unsigned()->defaultValue(4),
            'lg_size' => $this->tinyInteger()->unsigned()->defaultValue(3),
            'order' => $this->tinyInteger()->unsigned(),
            'extraSettings' => \Yii::$app->db->driverName == 'mysql' ? 'MEDIUMTEXT' : 'NTEXT'
        ]);
        // will be inserted with SQL script
    /*
        // add  ProjectInformationWidget
        $this->insert('{{%widgets}}', [
            'type' => 'ProjectInformationWidget',
            'title' => 'ProjectInformationWidget Title',
            'subtitle' => 'ProjectInformationWidget Subtitle',
            'order' => 0
        ]);

        // add InstructionsWidget
        $this->insert('{{%widgets}}', [
            'type' => 'InstructionsWidget',
            'title' => 'InstructionsWidget Title',
            'subtitle' => 'InstructionsWidget Subtitle',
            'order' => 1
        ]);

        // add MessageOfTheDayWidget
        $this->insert('{{%widgets}}', [
            'type' => 'MessageOfTheDayWidget',
            'title' => 'MessageOfTheDayWidget Title',
            'subtitle' => 'MessageOfTheDayWidget Subtitle',
            'order' => 2
        ]);

        // add PostBoxWidget
        $this->insert('{{%widgets}}', [
            'type' => 'PostBoxWidget',
            'title' => 'PostBoxWidget Title',
            'subtitle' => 'PostBoxWidget Subtitle',
            'order' => 3
        ]);

        // add SitesAndHolesMapWidget
        $this->insert('{{%widgets}}', [
            'type' => 'SitesAndHolesMapWidget',
            'title' => 'SitesAndHolesMapWidget Title',
            'subtitle' => 'SitesAndHolesMapWidget Subtitle',
            'order' => 4
        ]);

        // add DrillingProgressWidget
        $this->insert('{{%widgets}}', [
            'type' => 'DrillingProgressWidget',
            'title' => 'DrillingProgressWidget Title',
            'subtitle' => 'DrillingProgressWidget Subtitle',
            'order' => 5
        ]);
    */
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%widgets}}');
    }
}
