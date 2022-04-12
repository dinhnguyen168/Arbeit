<?php

namespace app\modules\cg\generators\DISModel\specializations;

/**
 * Class BaseCurationCorebox
 * Extra properties added to the generated class BaseCurationCorebox
 * - getContainedSectionSplits(): can be used in pseudoFields
 */

class BaseCurationCorebox
{

    /**
     * This method is inserted into the generated class file "backend/models/base/BaseCurationCorebox".
     *
     * Get the section splits contained in this corebox
     * @return string Multiline-String of the combined ids of the contained section splits
     */
    protected function getContainedSectionSplits () {
        $splits = [];
        if (class_exists("\\app\\models\\CurationSectionSplit")) {
            foreach (\app\models\CurationSectionSplit::find()->where(["corebox_id" => $this->id])->all() as $split) {
                $splits[] = $split->combined_id;
            }
        }
        return $splits;
    }

    public function calculateCombinedId ($combinedIdField = "combined_id") {
            $combinedId = $this->parent->combined_id . '_CB' . $this->corebox;
        return $combinedId;
    }
    
    public function getStorage()
    {
        return $this->hasOne(CurationStorage::className(), ['id' => 'storage_id']);
    }
    
}

