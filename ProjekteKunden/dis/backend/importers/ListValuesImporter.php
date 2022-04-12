<?php
/**
 * Created by PhpStorm.
 * User: reckert
 * Date: 21.01.2019
 * Time: 14:59
 */

namespace app\importers;

use app\models\core\DisList;
use app\models\core\DisListItem;

/**
 * Class CsvImporter
 * @package app\importers
 *
 * Importer for CSV files
 *
 * The first row of the CSV file has the names of the columns. These must be identical to the columns in the data table.
 * Every row is one record.
 * A column "id" is ignored, this class appends new records.
 * Every row has to have:
 * - an ID of the parent record (i.e. core_id in an import CSV file for sections) OR
 * - a combined id of the parent record in a column named 'parent_combined_id' OR
 * - a combined id of the record in a column named 'combined_id'
 * All required columns in the data model have to exist and must not be empty.
 *
 */
class ListValuesImporter extends Base
{
    /**
     * Displayed name of the Importer
     */
    const TITLE = 'Import CSV records for list values';

    /**
     * This reports fits for input files with extension ".csv"
     */
    const FILE_EXTENSION_REGEXP = "\\.csv$";

    /**
     * CSV Delimiter, Enclosure, Escape and Newline characters
     */
    const CSV_DELIMITER = ";";
    const CSV_ENCLOSURE = '"';
    const CSV_ESCAPE = "\\";

    public $headerColumns = [];
    private $ignoredColumns = ['id'];

    /**
     * Start the import from the CSV file
     * @return string HTML Output to show to the user
     * @throws \yii\web\NotFoundHttpException
     */
    public function run()
    {
        $this->modelName = 'core\DisListItem';
        parent::run();

        $fileHandle = fopen($this->filename, "r");
        if ($fileHandle === FALSE) {
            $this->error("CSV file cannot be opened for reading: " . $this->filename);
            return;
        }

        $class = $this->getModelClass();
        // Read head line, check columns, determine parentID column and/or column to find parent
        $this->readHeaders($fileHandle);
        // Read until end of file or abort
        while (!feof($fileHandle) && !$this->abort) {
            // Avoid PHP timeout
            set_time_limit(30);

            // Read next line into array
            $rowData = $this->readRow($fileHandle);
            if ($rowData) {
                if (isset($rowData['listname'])) {
                    // ensure list exists
                    $listId = $this->getList($rowData['listname']);
                    if ($listId) $rowData['list_id'] = $listId;
                }
                else {
                    $listId = $this->getList(null, intval($rowData['list_id']));
                    if ($listId) $rowData['list_id'] = $listId;
                }

                if ($listId > 0) {
                    // Create model from row data
                    $model = new $class;
                    $model->setAttributes($rowData, false);

                    if ($this->deleteRecords) {
                        $this->deleteRecord($model);
                    } else {
                        $this->importModel($model);
                    }
                }
            }
        }
        fclose($fileHandle);

        // Write summary
        $this->echo($this->getSummary());

        $this->finish();
    }

    protected $listIds = [];
    protected $listIdsByName = [];

    protected function getList($listName, $listId = null) {
        if ($listName != null) {
            if (!isset($this->listIdsByName[$listName])) {
                $list = DisList::findOne(['list_name' => $listName]);
                if ($list) {
                    if (!$this->checkListAccess($list)) return 0;
                    $listId = intval($list->id);
                    $this->listIds[] = $listId;
                    $this->listIdsByName[$listName] = $listId;
                }
                else
                    $listId = $this->createList($listName);
            }
            else
                $listId = $this->listIdsByName[$listName];
        }
        else {
            $listId = intval($listId);
            if (!isset($this->listIds[$listId])) {
                $list = DisList::findOne(['id' => $listId]);
                if ($list) {
                    if (!$this->checkListAccess($list)) return 0;
                    $this->listIds[$listId] = $listId;
                }
                else {
                    $listName = 'Imported_List_' . date("Y-m-d_H:i");
                    $listId = $this->createList($listName, $listId);
                }
            }
            else
                $listId = $this->listIds[$listId];
        }
        return $listId;
    }

    protected function checkListAccess($list) {
        if (!$list->is_locked || \Yii::$app->user->can('sa'))
            return true;
        else {
            $this->error('The value list "' . $list->list_name. '" may only be edited by administrators.');
            return false;
        }
    }

    protected function createList($listName, $listId = 0) {
        $newListId = 0;
        if (!$this->dryRun) {
            $attributes = [
                'list_name' => $listName,
                'is_locked' => 0
            ];
            if ($listId > 0 && !DisList::findOne(['id' => $listId])) $attributes['id'] = $listId;
            $list = new DisList($attributes);
            $success = $list->save();
            if ($success) {
                $newListId = $list->id;
                $this->warning ('New value list "' . $listName . '" [ID=' . $newListId . '] has been created.');
            }
            else {
                $newListId = 0;
                $this->error ('Value list "' . $listName . '" could not be created.');
            }
        }
        else {
            $this->warning ('New value list "' . $listName . '" WOULD be created.');
            if (!DisList::findOne(['id' => $listId]))
                $newListId = $listId;
            else {
                $newListId = 99990;
                while (in_array($newListId, $this->listIds) || isset($this->listIds[$newListId])) {
                    $newListId++;
                }
            }
        }

        if ($newListId > 0) {
            $this->listIds[$listId ? $listId : $newListId] = $newListId;
            $this->listIdsByName[$listName] = $newListId;
        }
        return intval($newListId);
    }

    /**
     * Import model into the database
     * @param $model
     */
    protected function importModel($model)
    {
        $attributeNames = $model->activeAttributes();
        if ($this->dryRun) $attributeNames = array_diff($attributeNames, ["list_id"]);

        if ($model->validate($attributeNames)) {
            if (!$this->dryRun) {
                // Really save the new record
                $model->save();
                $this->afterInsertModel($model);
            }
            $this->importedRecords++;
            $this->echoChars(". ");
        } else {
            $this->failedRecords++;
            foreach ($model->getErrors() as $column => $errors) {
                foreach ($errors as $error) {
                    $this->error("CSV line " . $this->rowNo . ": Column '" . $column . "': " . $error . " (value='" . $model->{$column} . "')");
                }
            }
            if ($this->stopOnErrors) $this->abort = true;
        }
    }


    /**
     * Just in case we have to do something with the inserted model
     * @param $model
     */
    protected function afterInsertModel($model)
    {
        // We could get the id of the newly inserted model
    }

    protected function findRecords($model)
    {
        return DisListItem::find()
            ->where([
                'list_id' => $model->list_id,
                'display' => $model->display
            ])
            ->all();
    }


    /**
     * Find existing record corresponding to the model and delete it in the database
     * @param $model
     */
    protected function deleteRecord($model)
    {
        $failed = false;


        // Find corresponding record(s) in database table
        $records = $this->findRecords($model);

        if ($records !== null && sizeof($records) == 1) {
            // if exactly one record was found ...
            $record = $records[0];
            if (!$this->dryRun) {
                // Really delete record
                try {
                    if ($record->delete()) {
                        $this->importedRecords++;
                        $this->echoChars(". ");
                    } else {
                        $failed = true;
                        $this->error("CSV line " . $this->rowNo . ": Cannot delete record with id" . $record->id);
                    }
                } catch (\yii\db\IntegrityException $e) {
                    $failed = true;
                    $this->error("CSV line " . $this->rowNo . ": Cannot delete record with id=" . $record->id . " because there are related records");
                }
            } else {
                // Only dry run
                $this->importedRecords++;
                $this->echoChars(". ");
            }
        } else {
            $failed = true;
            // No record or more than one records where found
            if ($records !== null) {
                if (sizeof($records) == 0) {
                    $this->error("CSV line " . $this->rowNo . ": Cannot find record to delete");
                } else {
                    $this->error("CSV line " . $this->rowNo . ": Cannot find unique record to delete; found " . sizeof($records) . " records");
                }
            }
        }

        if ($failed) {
            $this->failedRecords++;
            if ($this->stopOnErrors) $this->abort = true;
        }
    }


    /**
     * Read header (first line) from the CSV file, analyze and remap columns, determine column to find parent
     * @param $fileHandle File handle
     * @return bool Headers has been read and columns are sufficient to import the data
     */
    protected function readHeaders($fileHandle)
    {
        $headerData = fgetcsv($fileHandle, 0, static::CSV_DELIMITER, static::CSV_ENCLOSURE, static::CSV_ESCAPE);
        foreach ($headerData as $column) {
            $this->headerColumns[] = $column;
        }
        if (
            (!in_array('listname', $this->headerColumns) && !in_array('list_id', $this->headerColumns)) ||
            !in_array('display', $this->headerColumns) ||
            !in_array('remark', $this->headerColumns) ||
            !in_array('sort', $this->headerColumns)
        ) {
            $this->error("The file does not contain list values. Please make sure that the files contain the columns 'listname', 'display', 'remark' and 'sort'");
        }
        $this->rowNo++;
    }

    /**
     * Read the next row from the CSV file, remap the column names, remove the ignored columns.
     * @param $fileHandle File handle of the CSV file
     * @return bool|array Associative array of remapped column names to values from the CSV row
     */
    protected function readRow($fileHandle)
    {
        $this->rowNo++;
        $data = fgetcsv($fileHandle, 0, static::CSV_DELIMITER, static::CSV_ENCLOSURE, static::CSV_ESCAPE);
        if ($data === FALSE) return false;

        while (sizeof($data) < sizeof($this->headerColumns)) {
            $data[] = null;
        }
        $rowData = array_combine($this->headerColumns, $data);
        foreach ($this->ignoredColumns as $ignoredColumn) {
            unset($rowData[$ignoredColumn]);
        }

        return $rowData;
    }
}
