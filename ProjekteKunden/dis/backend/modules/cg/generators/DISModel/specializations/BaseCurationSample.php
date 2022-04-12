<?php

namespace app\modules\cg\generators\DISModel\specializations;

/**
 * Class BaseCurationSample
 * Extra properties added to the generated class BaseCurationSample
 * - calculateCombinedId(): different content of combined id
 */

class BaseCurationSample
{


    /**
     * @inheritDoc
     * The combined_id is built differently
     */
    public function calculateCombinedId ($combinedIdField = "combined_id") {
            $combinedId = $this->getParentCombinedId('combined_id') . ':' . $this->top . '-' . ($this->top + $this->sample_length);
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

