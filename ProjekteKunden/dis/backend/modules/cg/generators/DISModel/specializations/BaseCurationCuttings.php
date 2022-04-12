<?php

namespace app\modules\cg\generators\DISModel\specializations;

/**
 * Class BaseCurationCuttings
 * Extra properties added to the generated class BaseCurationCuttings
 * - calculateCombinedId(): different content of combined id
 * 
 * Two options: Combined ID based on A) depth or B) time.
 * Comment in/out the respective line and generate model. 
 */

class BaseCurationCuttings
{


    /**
     * @inheritDoc
     * The combined_id is built differently
     */
    public function calculateCombinedId ($combinedIdField = "combined_id") {
        if ($combinedIdField == 'sample_combined_id')
            $combinedId = $this->getParentCombinedId('combined_id') . '_CUT_' . $this->owner->drillers_top_depth . '-' . $this->owner->drillers_bottom_depth; //combined_id with depth
//            $combinedId = $this->getParentCombinedId('combined_id') . '_CUT_' . $this->owner->sample_date; //combined_id with sampling time
        else
            $combinedId = parent::calculateCombinedId ($combinedIdField);
        return $combinedId;
    }


    public static function __processGeneratorParams ($params)
    {
        /**
         * This method is not inserted into the generated class file "backend/models/base/BaseCoreCore"
         * but could be used to modify the parameters from the generator. These parameters are
         * used in the template "default/baseModel.php" to generate the source code of the class.
         */
        return $params;
    }

}

