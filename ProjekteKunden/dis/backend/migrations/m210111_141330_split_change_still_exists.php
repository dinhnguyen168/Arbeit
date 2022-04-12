<?php

use yii\db\Migration;

/**
 * Class m210111_141330_split_change_still_exists
 *
 * Rename column curation_section_split.exists to curation_section_split.still_exists
 */
class m210111_141330_split_change_still_exists extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function up()
    {
        try {
            $table = Yii::$app->db->schema->getTableSchema('curation_section_split');
            if (isset($table->columns['exists'])) {
                $this->renameColumn('curation_section_split', 'exists', 'still_exists');
            }
        } catch (\Exception $e) {
            echo $e->getMessage();
            return false;
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function down()
    {
        try {
            $table = Yii::$app->db->schema->getTableSchema('curation_section_split');
            if (isset($table->columns['still_exists'])) {
                $this->renameColumn('curation_section_split', 'still_exists', 'exists');
            }
        } catch (\Exception $e) {
            echo $e->getMessage();
            return false;
        }
        return true;
    }

}
