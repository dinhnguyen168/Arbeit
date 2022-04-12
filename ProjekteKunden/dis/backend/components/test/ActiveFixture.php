<?php
namespace app\components\test;

class ActiveFixture extends \yii\test\ActiveFixture
{
    /**
     * Removes all existing data from the specified table and resets sequence number to 0 (if any).
     * This method is called before populating fixture data into the table associated with this fixture.
     */
    protected function resetTable()
    {
        $table = $this->getTableSchema();
        $this->db->createCommand()->delete($table->fullName)->execute();
        if ($table->sequenceName !== null) {
            $this->db->createCommand()->executeResetSequence($table->fullName, 0);
        }
    }
}