<?php

namespace app\modules\cg\generators\DISModel\specializations;

/**
 * Class BaseCurationSampleRequest
 * Extra properties added to the generated class BaseCurationSampleRequest
 * - calculateCombinedId(): different content of combined id
 */

class BaseCurationSampleRequest
{


    /**
     * @inheritDoc
     * The combined_id is built differently
     */
    public function calculateCombinedId ($combinedIdField = "combined_id") {
            $combinedId = $this->getParentCombinedId($combinedIdField) . '_SR-' . $this->request_no . (is_null($this->request_part) ? '' : '-' . $this->request_part);
        return $combinedId;
    }

    /**
     * @inheritDoc
     * ParentCombinedId for "request_combined_id" is based on expedition
     */
    public function getParentCombinedId ($combinedIdField = "combined_id") {
        $parentCombinedId = "";
        if ($combinedIdField == 'request_combined_id') {
            $parent = $this->expedition;
            if ($parent) {
                if ($parent->hasAttribute('expedition'))
                    $parentCombinedId = "" . $parent->expedition;
                else {
                    $attribute = $parent::NAME_ATTRIBUTE;
                    $parentCombinedId = "" . $parent->{$attribute};
                }
            }
        }
        else
            $parentCombinedId = parent::getParentCombinedId($combinedIdField);
        return $parentCombinedId;
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

