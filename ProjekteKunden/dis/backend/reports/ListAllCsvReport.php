<?php

namespace app\reports;

/**
 * Class ListAllCsvReport
 *
 * Exports (the filtered) records from the form to a CSV file.
 * All columns of the data model are exported.
 * The actual data is preceeded with a header that describes the structure of the data model (= table).
 *
 * This report can be applied to all data models and can be used only for the list of records.
 *
 * @package app\reports
 */
class ListAllCsvReport extends ListCsvReport
{
    /**
     * {@inheritdoc}
     */
    const TITLE = 'Export full records as CSV file';
    /**
     * {@inheritdoc}
     * This report can be applied to all data models
     */
    const MODEL = '.*';

    /**
     * {@inheritdoc}
     */
    const REPORT_TYPE = 'export';

    /**
     * Returns the filename for the generated CSV file
     * @param string[] $options Query parameters provided to the reportController
     * @return string
     */
    protected function getFilename($options) {
        return $options["model"] . 's_full.csv';
    }


    /**
     * {@inheritdoc}
     */
    function validateReport($options) {
        $modelClass = $this->getModelClass($options);
        $valid = parent::validateReport($options);
        return $this->validateColumns($modelClass, []) && $valid;
    }


    /**
     * {@inheritdoc}
     */
    public function generate($options = []) {
        $modelClass = $this->getModelClass($options);
        $model = new $modelClass();
        $options['columns'] = implode(",", array_keys($model->attributeLabels()));

        parent::generate($options);
        $this->content = $this->getDatastructureCsv($modelClass) . $this->content;
    }


    /**
     * {@inheritdoc}
     * While in ListCsvReport the labels are used in the CSV header row, here we use the column names.
     */
    protected function getHeader($class, $model, $columns)
    {
        $row = [];
        foreach ($columns as $column => $label) {
            $row[] = $column;
        }
        return $this->getcsv($row);
    }


    /**
     * Return a string of multiline CSV rows with the description of the data structure of the model
     * @param string $modelClass Full name of the data model class (including Namespace)
     * @return string Multiple CSV rows with description of data structure
     */
    protected function getDatastructureCsv($modelClass) {
        $model = new $modelClass();
        $modelTemplate = $model->getModelTemplate();

        $rows = [];
        $rows[] = ['Module', $modelTemplate->module];
        $rows[] = ['Name', $modelTemplate->name];
        $rows[] = ['Table', $modelTemplate->table];
        $rows[] = ['ParentModel', $modelTemplate->parentModel];
        $rows[] = [];
        $rows[] = ['Name', 'Type', 'Size', 'Required', 'Primary', 'AutoIncrement', 'Label', 'Description', 'Validator', 'Unit', 'SelectList', 'Calculate', 'DefaultValue'];
        $maxCols = sizeof ($rows[sizeof($rows)-1]);
        foreach ($modelTemplate->columns as $column) {
            $rows[] = [$column->name, $column->type, $column->size, $column->required, $column->primaryKey, $column->autoInc,
                        $column->label, $column->description, $column->validator, $column->unit, $column->selectListName, $column->calculate, $column->defaultValue];
        }
        $rows[] = explode(",", str_repeat(str_repeat("-", 10) . ",", $maxCols));
        $rows[] = [];
        $rows[] = [];
        $rows[] = [];

        $text = "";
        foreach ($rows as $row) {
            $text .= $this->getcsv($row);
        }
        return $text;
    }

}
