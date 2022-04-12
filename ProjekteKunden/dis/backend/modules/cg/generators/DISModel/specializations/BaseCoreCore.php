<?php

namespace app\modules\cg\generators\DISModel\specializations;

/**
 * Class BaseCoreCore
 * Extra properties added to the generated class BaseCoreCore
 * - getSplitStatus(): can be used in pseudoFields
 * - calculateCombinedId(): Core number extended to 3 digits
 */

class BaseCoreCore
{

    /**
     * This method is inserted into the generated class file "backend/models/base/BaseCoreCore".
     *
     * Get split status of all section splits in this core
     * @return string "none" | "partly" | "all"
     */
    protected function getSplitStatus () {
        $status = "error";
        if (class_exists("\\app\\models\\CurationSectionSplit") && class_exists("\\app\\models\\CoreSection")) {
            $nCntSections = \app\models\CoreSection::find()
                ->andWhere(["core_id" => $this->id])
                ->count();
            $nCntSplits = \app\models\CurationSectionSplit::find()
                ->innerJoinWith("section")
                ->andWhere(["type" => "A"])
                ->andWhere(["core_id" => $this->id])
                ->count();
            $status = ($nCntSplits == 0 ? "none" : ($nCntSplits == $nCntSections ? "all" : "partly"));
        }
        return $status;
    }

    /**
     * @inheritDoc
     * The core number shall be extended to 3 digits in the combined_id
     */
    public function calculateCombinedId ($combinedIdField = "combined_id") {
        $combinedId = $this->getParentCombinedId($combinedIdField);
        $attribute = $this::NAME_ATTRIBUTE;
        if ($attribute > "") {
            $value = $this->owner->{$attribute};
            if ($attribute == "core") $value = str_pad (strval($value), 3, "0", STR_PAD_LEFT);
            $combinedId .= ($combinedId > "" ? '_' : "") . $value;
        }
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

