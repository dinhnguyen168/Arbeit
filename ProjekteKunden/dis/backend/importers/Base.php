<?php

namespace app\importers;

use yii\web\ViewAction;
use app\models\ArchiveFile;
use yii\web\ServerErrorHttpException;

/**
 * Class Base
 *
 * Base class for all importers.
 * Importers inherit from yii\web\ViewAction and are run directly from the yii framework.
 *
 * @package app\importers
 */
abstract class Base extends ViewAction
{
    /**
     * Name of the Importer as shown in the list of available exports
     */
    const TITLE = '';
    /**
     * Does this importer require the user to select the data model (= table)?
     * An Importer could also be only for some specific data model so the user does not have to select it.
     */
    const MODEL_NAME_PARAMETER_REQUIRED = false;
    /**
     * For import files with what file extension should this report be available?
     * This is a regular expression, so you can also make an importer available for multiple file extensions.
     * In fact, this will be matched onto the filename!
     */
    const FILE_EXTENSION_REGEXP = "\\.*$";


    /**
     * @var string The file name of the import file. This file must be available in the directory "backend/data/upload/".
     */
    public $filename;
    /**
     * @var null|string The name of the data model (= table), i.e. "CoreSection". If the importer does not required a modelName
     * (@see MODEL_NAME_PARAMETER_REQUIRED) it can be null.
     */
    public $modelName = null;

    /*
     * @var bool dryRun means the importer runs in test mode. No records are imported (or deleted), it will be only checked if the operations could be done.
     */
    public $dryRun = true;

    /**
     * @var bool If true, the importer will stop once an error occures.
     */
    public $stopOnErrors = true;

    /**
     * @var bool If true, records will be identified by the id, combined_id, etc. and deleted.
     */
    public $deleteRecords = false;

    /**
     * @var int Number of imported (or deleted) records
     */
    protected $importedRecords = 0;
    /**
     * @var int Number of failed records
     */
    protected $failedRecords = 0;
    /**
     * @var bool Should the importer stop
     */
    protected $abort = false;
    /**
     * @var int Row number of the current row in the import file
     */
    protected $rowNo = 0;


    /**
     * @var string Full class name of the data model (= table), i.e. "app/models/CoreSection". Should not be accessed directly, instead
     * the method "getModelClass()" should be used.
     */
    private $class;

    /**
     * Returns the full class name of the data model (= table), i.e. "app/models/CoreSection".
     * @return string full class name of data model
     */
    protected function getModelClass() {
        if ($this->class == null) {
            if ($this->modelName !== null) {
                $className = "app\\models\\" . $this->modelName;
                if (class_exists($className)) $this->class = $className;
            }
        }
        return $this->class;
    }

    /**
     * Starts the import.
     * This should be called at the beginning of the run() method of an inheriting class.
     * Checks if import file is set and exists.
     * Renders the head of the output page.
     * @return void
     * @throws ServerErrorHttpException
     */
    public function run() {
        if ($this->filename == null) {
            throw new ServerErrorHttpException('Parameter filename must be provided');
        }
        $this->filename = str_replace("..", "", urldecode($this->filename));

        $uploadPath = \Yii::getAlias("@app/data/upload");
        if (!file_exists($uploadPath . $this->filename)) {
            throw new ServerErrorHttpException('File not found: ' . $this->filename);
        }
        else {
            $this->filename = $uploadPath . $this->filename;
        }

        if (static::MODEL_NAME_PARAMETER_REQUIRED && $this->modelName == null) {
            throw new ServerErrorHttpException('Parameter modelName must be provided');
        }

        $this->controller->layout = "importer_head.php";
        $this->echoChars ($this->controller->renderContent(""));

        $this->echo ('<h2>' . static::TITLE . '</h2>');
        $this->echo ('<h4>File: ' . basename($this->filename) . ($this->modelName ? ', Model: ' . $this->modelName : '') . '</h4>');
        if ($this->deleteRecords) $this->echo ('<div class="alert alert-info">Deleting records</div>');
        if ($this->dryRun) $this->echo ('<div class="alert alert-info">Only testing data!</div>');

        try {
            ob_end_flush();
        }
        catch (\Exception $e){}
    }


    /**
     * Renders the foot of the output page.
     * This should be called at the end of the run() method of any inheriting class.
     */
    public function finish() {
        $this->controller->layout = "importer_foot.php";
        // Write output collected by calls to "echo()", "info()", "warning()", "error()"
        $this->echoChars ($this->controller->renderContent(""));
        die();
    }

    /**
     * Returns a summary text of the importer process.
     * @return string
     */
    protected function getSummary() {
        $summary = '<br>';
        if ($this->abort) {
            $summary .= '<div class="alert alert-danger">The import has been aborted due to an error!</div>';
        }
        if ($this->failedRecords > 0 || $this->importedRecords > 0) {
            $summary .= '<div class="alert ' . ($this->failedRecords > 0 ? 'alert-warning' : 'alert-info') . '">';
            if ($this->dryRun) {
                $summary .= "Only testing data!<br>";
            }
            $summary .= $this->failedRecords . " records could not be " . ($this->deleteRecords ? "deleted" : "imported") . "<br>";
            $summary .= $this->importedRecords . " records were successfully " . ($this->dryRun ? "tested" : ($this->deleteRecords ? "deleted" : "imported")) . "<br>";
            $summary .= '</div>';
        }
        else {
            $summary .= '<div class="alert alert-warning">No records were imported!</div>';
        }
        return $summary;
    }


    /**
     * Uses the data model object created from the input file to find corresponing records in the database.
     * This function is used to delete records base on an input file.
     * Returns an array found records; only if exactly one is found, this should be deleted.
     * @param $model
     * @return array|null Array of found records
     */
    protected function findRecords ($model) {
        if ($model->id > 0) {
            $record = call_user_func([$model, 'find'])->where(['id' => $model->id])->one();
            if (record) return [$record];
        }
        else {
            $columns = $this->getUniqueColumns($model);
            if (sizeof($columns) > 0) {
                $query = call_user_func([$model, 'find']);
                foreach ($columns as $column) {
                    if ($model->{$column} !== null) {
                        $query->andWhere([$column => $model->{$column}]);
                    }
                }
                return $query->all();
            }
            else {
                $this->error ("Cannot find records to delete: no unique columns in data table");
                $this->abort = true;
                $this->stopOnErrors = true;
            }
        }
        return null;
    }


    /**
     * @var array Array of unique columns
     */
    private $uniqueColumns = null;

    /**
     * Returns an array of the unique columns in the data model
     * @param $model
     * @return array Array of unique column names
     */
    protected function getUniqueColumns($model) {
        if ($this->uniqueColumns == null) {
            $this->uniqueColumns = [];
            $template = $model->getModelTemplate();
            foreach ($template->indices as $index) {
                if ($index->type == "UNIQUE" || $index->type == "PRIMARY") {
                    $this->uniqueColumns = array_merge($this->uniqueColumns, $index->columns);
                }
            }
            $this->uniqueColumns = array_unique($this->uniqueColumns);
        }
        return $this->uniqueColumns;

    }


    /**
     * Writes some characters to the output page
     * @param string $chars
     */
    protected function echoChars($chars) {
        echo $chars;
        flush();
    }

    /**
     * Writes a line to the output page
     * @param string $line
     */
    protected function echo ($line = "") {
        $this->echoChars ($line . '<br>');
    }

    /**
     * Writes a line formatted as "info" to the output page
     * @param $line
     */
    protected function info ($line) {
        $this->echo ('<span class="alert-info">' . $line . '</span>');
    }

    /**
     * @var int Row number of input file where the last error was reported
     */
    private $lastErrorRowNo = -1;

    /**
     * Writes a line formatted as "error" to the output page
     * @param string $line
     */
    protected function error ($line) {
        if ($this->rowNo-1 > $this->lastErrorRowNo) $this->echo ('');
        $this->echo ('<span class="alert-danger">' . $line . '</span>');
        $this->lastErrorRowNo = $this->rowNo;
    }

    /**
     * Writes a line formatted as "warning" to the output page
     * @param $line
     */
    protected function warning ($line) {
        $this->echo ('<span class="alert-warning">' . $line . '</span>');
    }



}
