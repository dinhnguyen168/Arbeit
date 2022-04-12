<?php

namespace app\importers;


/**
 * Class ExampleCsvImporter
 *
 * This shows an example, how the CsvImporter can be specialized.
 *
 * @package app\importers
 */
class ExampleCsvImporter extends CsvImporter
{

    /**
     * Displayed name of the Importer
     */
    const TITLE = 'Example: Specialized CSV importer for CoreCore';

    /**
     * This report expects the data to be used for the CoreCore model,
     * so the user does not have to select a model
     */
    const MODEL_NAME_PARAMETER_REQUIRED = false;

    /**
     * The import files have the extension ".dat"
     */
    const FILE_EXTENSION_REGEXP = "\\.dat$";


    /**
     * There are several columns in the import file, we do not need and dont want to remove before importing
     */
    const IGNORE_COLUMNS = ['id', 'unused_colum_1', 'another_unused_column'];

    /**
     * Some columns have different names than in the data model (= table)
     */
    const REMAP_COLUMNS = [
        "bottom" => "bottom_depth",
        "hole" => "parent_combined_id",
        "type" => "core_type"
    ];

    /**
     * There is a columns "length" in the CSV file we need to calculate another column in the data model (= table).
     * We have to add this here, otherwise we get an error.
     */
    const VALID_EXTRA_COLUMNS = ['parent_combined_id', 'length'];


    /**
     * Set the modelName to "CoreCore"
     */
    protected function beforeRun()
    {
        $this->modelName = "CoreCore";
        return parent::beforeRun();
    }

    /**
     * We post process the rowData delivered from the same method of the normal CsvImporter.
     * @return array|bool
     */
    protected function readRow($fileHandle)
    {

        $rowData = parent::readRow($fileHandle);
        if ($rowData) {
            // Here we can modify the values of rowData, access the extra columns (see above) to calculate others

            // Calculate top_depth based on bottom_depth and extra column length
            $rowData["top_depth"] = $rowData["bottom_depth"] - $rowData["length"];
            $rowData["drilled_length"] = $rowData["length"];

            // We dont have a value for oriented in the CSV file and set this to 1
            $rowData["oriented"] = 1;

            // We do not want value of "UNKNOWN" in column core_loss_reason
            if ($rowData["core_loss_reason"] == "UNKNOWN") {
                unset($rowData["core_loss_reason"]);
            }

            // The missing ISGN number will be automatically generated

            /**
             * If the parent id has to be determined in a more complex way, you could overwrite the method
             * findParentModelColumn($headerData, $model) and just return true.
             * Add the input column names you need to calculate a parent_combined_id to the VALID_EXTRA_COLUMNS, so the get
             * read from the CSV file.
             * Calculate the parent combined id into a variable $parent_combined_id here and then call:
             *      $rowData[$this->parentIdColumn] = $this->getParentId("parent_combined_id", $parent_combined_id, $rowData);
             */
        }
        return $rowData;
    }



}