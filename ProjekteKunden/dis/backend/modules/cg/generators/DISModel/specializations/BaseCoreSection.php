<?php

namespace app\modules\cg\generators\DISModel\specializations;

/**
 * Class BaseCoreCore
 * Extra properties added to the generated class BaseCoreSection
 * - getSplitStatus(): can be used in pseudoFields
 */

class BaseCoreSection {

    /**
     * This method is inserted into the generated class file "backend/models/base/BaseCoreSection".
     *
     * Has this section been splitted?
     * @return string "yes" | "no"
     */
    protected function getSplitStatus () {
        $status = "error";
        if (class_exists("\\app\\models\\CurationSectionSplit")) {
            $bExists = \app\models\CurationSectionSplit::find()
                ->andWhere(["!=", "type", "WR"])
                ->andWhere(["section_id" => $this->id])
                ->exists();
            $status = $bExists ? "yes" : "no";
        }
        return $status;
    }
    protected function getSectionTop()
    {
        $sectionsAboveKeys = array_keys(array_filter(array_column($this->parent->coreSections, 'section'), function ($x) { return $x < $this->section ; }));
        $arrayAllSections = array_column($this->core->coreSections, 'section_length');
        $arraySectionsAbove = array();
        foreach ($sectionsAboveKeys as $arrayKey) {
            array_push($arraySectionsAbove, $arrayAllSections[$arrayKey]);
        };
        $topDepth = array_sum($arraySectionsAbove) + $this->parent->drillers_top_depth;
        return $topDepth;
    }

}
