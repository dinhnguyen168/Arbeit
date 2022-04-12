<?php
/**
 * Created by PhpStorm.
 * User: reckert
 * Date: 21.01.2019
 * Time: 14:59
 */

namespace app\importers;

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
class CsvImporter extends Base
{
    /**
     * Displayed name of the Importer
     */
    const TITLE = 'Import CSV records for one table';

    /**
     * This report requires, that the model is selected by the user
     */
    const MODEL_NAME_PARAMETER_REQUIRED = true;

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
    const CSV_NEWLINE = "\n";

    /**
     * Array of column names to ignore from the CSV file
     */
    const IGNORE_COLUMNS = ['id'];

    /**
     * Associative Array of column names from the CSV file to remap to a different name to fit the data model (= table)
     * Every entry should be in the form <CSV column name> => <model column name>
     */
    const REMAP_COLUMNS = [];

    /**
     * Array of column names that are valid in the CSV file, even if they do not exist in the data model (= table).
     * That could be usefull to get colums from the CSV file that are used to calculate other columns of the model.
     */
    const VALID_EXTRA_COLUMNS = ['parent_combined_id', 'combined_id'];

    /**
     * @var string Name of the column to identify the parent record
     * Can be the id of the parent column, a column named "combined_id"
     */
    protected $determineParentModelColumn;
    /**
     * @var string Name of the parentID column
     */
    protected $parentIdColumn;

    /**
     * @var string[] Array of column names from the CSV file to ignore. These are removed from the rowData.
     */
    protected $ignoredColumns = [];

    /**
     * @var string[] Array of the remapped column names for the columns in the CSV file.
     */
    protected $remappedColumns;

    /**
     * @var int Timezone offset in minutes of the input datetime values
     */
    protected $timezoneOffsetMinutes = 0;

    /**
     * @var array Array of column names with date values
     */
    protected $dateColumns = [];

    /**
     * @var array Array of column names with datetime values
     */
    protected $dateTimeColumns = [];
		
    /**
     * @var array Array of column names with float values
     */
    protected $floatColumns = [];
		
		

    /**
     * Start the import from the CSV file
     * @return string HTML Output to show to the user
     * @throws \yii\web\NotFoundHttpException
     */
    public function run() {
        parent::run();

        $fileHandle = fopen($this->filename, "r");
        if ($fileHandle === FALSE) {
            $this->error("CSV file cannot be opened for reading: " . $this->filename);
            return;
        }

        $class = $this->getModelClass();
        // Read head line, check columns, determine parentID column and/or column to find parent
        if ($this->readHeaders($fileHandle)) {

            // Read until end of file or abort
            while (!feof($fileHandle) && !$this->abort) {
                // Avoid PHP timeout
                set_time_limit(30);

                // Read next line into array
                $rowData = $this->readRow($fileHandle);
                if ($rowData) {
                   // Create model from row data
                    $model = new $class;
                    $model->setAttributes($rowData, false);

                    if ($this->deleteRecords) {
                        $this->deleteRecord($model);
                    }
                    else {
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


    /**
     * Import model into the database
     * @param $model
     */
    protected function importModel($model) {
        if ($model->validate()) {
            if (!$this->dryRun) {
                // Really save the new record
                $model->save();
                $this->afterInsertModel ($model);
            }
            $this->importedRecords++;
            $this->echoChars(". ");
        }
        else {
            $this->failedRecords++;
            foreach ($model->getErrors() as $column => $errors) {
                foreach ($errors as $error) {
                    $this->error ("CSV line " . $this->rowNo . ": Column '" . $column . "': " . $error . " (value='" . $model->{$column} . "')");
                }
            }
            if ($this->stopOnErrors) $this->abort = true;
        }
    }


    /**
     * Just in case we have to do something with the inserted model
     * @param $model
     */
    protected function afterInsertModel($model) {
        // We could get the id of the newly inserted model
    }


    /**
     * Find existing record corresponding to the model and delete it in the database
     * @param $model
     */
    protected function deleteRecord($model) {
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
                    }
                    else {
                        $failed = true;
                        $this->error ("CSV line " . $this->rowNo . ": Cannot delete record with id" . $record->id);
                    }
                }
                catch (\yii\db\IntegrityException $e) {
                    $failed = true;
                    $this->error ("CSV line " . $this->rowNo . ": Cannot delete record with id=" . $record->id . " because there are related records");
                }
            }
            else {
                // Only dry run
                $this->importedRecords++;
                $this->echoChars(". ");
            }
        }
        else {
            $failed = true;
            // No record or more than one records where found
            if ($records !== null) {
                if (sizeof($records) == 0) {
                    $this->error ("CSV line " . $this->rowNo . ": Cannot find record to delete");
                }
                else {
                    $this->error ("CSV line " . $this->rowNo . ": Cannot find unique record to delete; found " . sizeof($records) . " records");
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
    protected function readHeaders($fileHandle) {
        $this->rowNo++;
        $class = $this->getModelClass();
        $model = new $class;

        $columns = [];
        $headerData = fgetcsv ($fileHandle, 0, static::CSV_DELIMITER, static::CSV_ENCLOSURE, static::CSV_ESCAPE);

        // Lowercase column names to compare with
        $lcExistingColumns = [];
        foreach (array_keys($model->attributes) as $column) {
            $lcExistingColumns[strtolower($column)] = $column;
        }

        foreach ($headerData as $column) {
            $targetColumn = $column;
            if (isset(static::REMAP_COLUMNS[$column])) {
                $targetColumn = static::REMAP_COLUMNS[$column];
            }
            else if (isset($lcExistingColumns[strtolower($column)]) && !in_array($column, $lcExistingColumns)) {
                // Different upper/lower case format of column name
                $targetColumn = $lcExistingColumns[strtolower($column)];
            }
            if (!in_array($column, static::IGNORE_COLUMNS)) {
                if (!in_array($targetColumn, static::VALID_EXTRA_COLUMNS) && !$model->hasAttribute($targetColumn)) {
                    $this->error ("Column '" . $targetColumn . "' cannot be found in the model class '" . $class . "'");
                    $this->ignoredColumns[] = $targetColumn;
                    return false;
                }
            }
            else
                $this->ignoredColumns[] = $targetColumn;
            $columns[$column] = $targetColumn;
        }

        $this->findParentIdModelColumn($model);
        if (!$this->findImportParentModelColumn($headerData, $model)) {
            return false;
        }

        $this->remappedColumns = array_values($columns);

        $this->dateColumns = [];
        $this->dateTimeColumns = [];
        $schema = $model->getTableSchema();
        foreach ($this->remappedColumns AS $column) {
            $columnMeta = $schema->getColumn($column);
            if ($columnMeta) {
							switch ($columnMeta->type) {
								case 'date':
                    $this->dateColumns[] = $column;
										break;
								case 'datetime':
                    $this->dateTimeColumns[] = $column;
										break;
								case 'float':
								case 'decimal':
								case 'double':
                    $this->floatColumns[] = $column;
										break;
							}
            }
        }

        return true;
    }


    /**
     * Find the column with the parent id in the data model (= table)
     * @param $model
     */
    protected function findParentIdModelColumn($model) {
        $class = $this->getModelClass();
        $ancestors = constant($class . "::ANCESTORS");
        if (sizeof($ancestors) > 0) {
           $parent = array_keys($ancestors)[0];
            $parentIdColumn = $parent . "_id";
            if ($model->hasAttribute($parentIdColumn)) {
                $this->parentIdColumn = $parentIdColumn;
            }
        }
    }

    /**
     * Searches for the column to identify the parent record of the new model with
     * @param string[] $headerData Data from the first line of the csv file
     * @param $model empty data model object
     * @return bool Parent record can be determined or there is no parent record
     */
    protected function findImportParentModelColumn($headerData, $model) {
        if ($this->parentIdColumn) {
            if ($this->columnExistsInHeaders($this->parentIdColumn, $headerData)) {
                $this->determineParentModelColumn = $this->parentIdColumn;
            }
            else if ($this->columnExistsInHeaders("parent_combined_id", $headerData)) {
                $this->determineParentModelColumn = "parent_combined_id";
            }
            else if ($this->columnExistsInHeaders("combined_id", $headerData)) {
                $this->determineParentModelColumn = "combined_id";
            }
            else {
                $this->error("Cannot find column to determine parent record");
                return false;
            }
        }
        return true;
    }

    /**
     * Check if a column exist in the CSV file.
     * If the column will be remapped, search for the remapped column
     * @param $columnName Column name to search for
     * @param $headerData Array of column names from the first line of the CSV file
     * @return bool Column was found in the CSV file
     */
    protected function columnExistsInHeaders($columnName, $headerData) {
        $flippedRemapColumns = array_flip(static::REMAP_COLUMNS);
        $searchColumnName = $columnName;
        if (isset($flippedRemapColumns[$columnName])) {
            $searchColumnName = $flippedRemapColumns[$columnName];
        }
        return in_array($searchColumnName, $headerData);
    }


    /**
     * Read the next row from the CSV file, remap the column names, remove the ignored columns.
     * @param $fileHandle File handle of the CSV file
     * @return bool|array Associative array of remapped column names to values from the CSV row
     */
    protected function readRow($fileHandle) {
        $this->rowNo++;
        $data = fgetcsv ($fileHandle, 0, static::CSV_DELIMITER, static::CSV_ENCLOSURE, static::CSV_ESCAPE);
        if ($data === FALSE) return false;

        while (sizeof($data) < sizeof($this->remappedColumns)) {
            $data[] = null;
        }
        $rowData = array_combine($this->remappedColumns, $data);
        foreach ($this->ignoredColumns as $ignoredColumn) {
            unset($rowData[$ignoredColumn]);
        }

        foreach ($this->dateColumns as $column) {
            $rowData[$column] = $this->convertDate($rowData[$column]);
        }
        foreach ($this->dateTimeColumns as $column) {
            $rowData[$column] = $this->convertDateTime($rowData[$column]);
        }
        foreach ($this->floatColumns as $column) {
            $rowData[$column] = $this->convertFloat($rowData[$column]);
        }


        if ($this->parentIdColumn != null) {
             $rowData[$this->parentIdColumn] = $this->getParentId($this->determineParentModelColumn, $rowData[$this->determineParentModelColumn], $rowData);
        }
        return $rowData;
    }

    /**
     * Convert a date value to the input format required in the database
     * The following formats are supported:
     * - American: m/d/Y
     * - German: d.m.Y
     * - Unix timestamp
     * @param $value Input date value
     * @return string Converted date value
     */
    protected function convertDate($value) {
        $matches = [];
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})/', $value, $matches)) {
            // American format m/d/y
            return $matches[3] . '-' . $matches[1] . '-' . $matches[2];
        }
        if (preg_match('/^(\d{1,2})\.(\d{1,2})\.(\d{4})/', $value, $matches)) {
            // German format d.m.y
            return $matches[3] . '-' . $matches[2] . '-' . $matches[1];
        }
        if (preg_match('/^\d{10}$/', $value)) {
            return date('Y-m-d H:i:s', $value);
        }
        return $value;
   }

   private $timezone = null;
   private $timezoneUTC = null;

    /**
     * Convert a datetime value to the input format required in the database
     * The following formats are supported:
     * - American: m/d/Y h:m:s
     * - German: d.m.Y h:m:s
     * - Unix timestamp
     * If $timezoneOffset is not 0, the value is adjusted to UTC.
     * @param $value Input datetime value
     * @return string Converted datetime value
     */
   protected function convertDateTime($value) {
        $matches = [];
        if (preg_match('/^([0-9\/\.]{8,10}) ([0-9\:]{5,8})(\+[0-9\:]{4,5})?/', $value, $matches)) {
            $value = $this->convertDate($matches[1]) . ' ' . $matches[2];
        }
        else if (preg_match('/^\d{10}$/', $value)) {
            $value = date('Y-m-d H:i:s', $value);
        }

        if (preg_match('/ \d{1,2}:\d{1,2}$/', $value)) {
            $value .= ':00';
        }

        if ($this->timezoneOffsetMinutes != 0) {
            // Convert to UTC time
            $h = intval($this->timezoneOffsetMinutes / 60);
            $m = $this->timezoneOffsetMinutes - $h * 60;
            if ($this->timezone == null) {
                $this->timezone = new \DateTimeZone('+' . str_pad("" . $h, 2, "0", STR_PAD_LEFT) . str_pad("" . $m, 2, "0", STR_PAD_LEFT));
                $this->timezoneUTC = new \DateTimeZone('+0000');
            }
            $date = \DateTime::createFromFormat("Y-m-d H:i:s", $value, $this->timezone);
            if ($date) {
                $date->setTimezone ($this->timezoneUTC);
                $value = $date->format("Y-m-d H:i:s");
            }
        }
        return $value;
    }

		protected function convertFloat($value) {
			$commaPos = strpos($value, ',');
			if ($commaPos !== false && strpos($value, '.') === false) {
				$value[$commaPos] = ".";
			}
			return $value;
		}
		
		
    private $parentIdCache = [];
    private $parentClassName = null;
    private $parentClass = null;
    private $parentCombinedIdColumn = null;

    /**
     * Get the id of the parent record.
     *
     * @param mixed $value Value in the column determineParentModelColumn
     * @param array $rowData Associative Array with the data from the CSV file
     * @return int ID of the parent column (or 0 if no record can be found
     */
    protected function getParentId ($determineParentModelColumn, $value, $rowData){
        switch ($determineParentModelColumn) {
            case "combined_id":
                // Remove the last part from a combined_id to get the combined_id of the parent model
                $value = preg_replace("/_[^_]+$/", "", $value);
                // continue below ...

            case "parent_combined_id":
                if ($this->parentClass == null) {
                    $this->parentClassName = constant($this->getModelClass() . "::PARENT_CLASSNAME");
                    $this->parentClass = "app\\models\\" . $this->parentClassName;
                    $this->parentCombinedIdColumn = $this->parentClassName == "ProjectExpedition" ? "expedition" : "combined_id";
                }

                if (isset($this->parentIdCache[$value]))
                    return $this->parentIdCache[$value];
                else {
                    $parentModel = call_user_func([$this->parentClass, 'find'])->andWhere([$this->parentCombinedIdColumn => $value])->one();
                    if (!$parentModel) {
                        $this->error("Parent model '" . $this->parentClassName . "' cannot be found with " . $this->parentCombinedIdColumn . "=" . $value);
                        $this->parentIdCache[$value] = 0;
                        return 0;
                    } else {
                        $this->parentIdCache[$value] = $parentModel->id;
                        return $parentModel->id;
                    }
                }

            default:
                // Third alternative can only be the column holding the parent id
                return $value;
        }
    }


}