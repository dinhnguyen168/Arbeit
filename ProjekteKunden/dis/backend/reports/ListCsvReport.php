<?php
/**
 * Created by PhpStorm.
 * User: reckert
 * Date: 21.01.2019
 * Time: 14:59
 */

namespace app\reports;

use app\reports\interfaces\ICsvReport;

/**
 * Class ListCsvReport
 *
 * Creates a CSV file from the filtered records in the form and only the selected columns.
 *
 * This report can be applied to all data models and can be used only for the list of records.
 *
 * @package app\reports
 */
class ListCsvReport extends Base implements ICsvReport
{
    /**
     * {@inheritdoc}
     */
    const TITLE = 'Export records as CSV file';
    /**
     * {@inheritdoc}
     * This report can be applied to every data model.
     */
    const MODEL = '.*';

    /**
     * {@inheritdoc}
     */
    const REPORT_TYPE = 'export';


    /**
     * Returns the file name of the generated CSV file
     * @param string[] $options Query parameters provided to the reportController
     * @return string File name of CSV file
     */
    protected function getFilename($options) {
        return $options["model"] . 's.csv';
    }


    /**
     * {@inheritdoc}
     */
    function validateReport($options) {
        $modelClass = $this->getModelClass($options);
        $valid = parent::validateReport($options);
        $valid = $this->validateColumns($modelClass, []) && $valid;
        if (isset($options["columns"])) {
            $valid = $this->validateColumns($modelClass, explode(",", $options["columns"]));
        }
        return $valid;
    }


    /**
     * {@inheritdoc}
     */
    public function generate($options = []) {
        $modelClass = $this->getModelClass($options);
        $dataProvider = $this->getDataProvider($options);
        $dataProvider->pagination->pageSize = 100;
        $columns = $this->getColumns($options);

        $models = $dataProvider->getModels();
        $output = "";
        if (sizeof($models) > 0) {
            $model = $models[0];

            // remove invalid columns
            $columnNames = array_keys($columns);
            for ($i=sizeof($columnNames)-1; $i>=0; $i--) {
                $columnName = $columnNames[$i];
                if (!$model->canGetProperty($columnName)) {
                    unset($columns[$columnName]);
                }
            }

            $output .= $this->getHeader($modelClass, $model, $columns);

            for ($page=0; $page < $dataProvider->pagination->pageCount; $page++) {
                if ($page > 0) {
                    $dataProvider->pagination->page = $page;
                    $dataProvider->refresh();
                    $models = $dataProvider->getModels();
                }
                foreach ($models as $model) {
                    $output .= $this->getCsvRow($model, $columns);
                }
            }
        }

        $this->content = $output;
    }

    /**
     * Returns the header CSV row with the labels of the columns
     * @param string $class Name of the class
     * @param object $model Data model object
     * @param array $columns Associative Array of column names and labels [<column name> => <value>, ...]
     * @return string CSV row
     */
    protected function getHeader($class, $model, $columns)
    {
        $row = [];
        foreach ($columns as $column => $label) {
            $row[] = $label;
        }
        return $this->getcsv($row);
    }

    /**
     * Returns a data CSV row for the $model
     * @param object $model Data model object
     * @param array $columns Associative Array of column names and labels [<column name> => <value>, ...]
     * @return string CSV row
     */
    protected function getCsvRow($model, $columns) {
        $row = [];
        // $value = $model->canNow();
        $manyToManyValues = $this->getManyToManyValues($model);
        $oneToManyValues = $this->getOneToManyValue($model);
        foreach ($columns as $column => $label) {
            if (in_array($column, array_keys($oneToManyValues))) {
                $value = $oneToManyValues[$column][0];
            } else if (in_array($column, array_keys($manyToManyValues))) {
                $value = $manyToManyValues[$column][0];
            } else {
                $value = $model->{$column};
            }

            $row[] = $value;
        }
        return $this->getcsv($row);
    }

    /**
     * Return a CSV formatted row for the Array of values submitted
     * @param mixed[] $row Array of values to write into the CSV row
     * @return string CSV formatted row
     */
    protected function getcsv($row) {
        $f = fopen('php://memory', 'r+');
        fputcsv ($f, $row, static::CSV_DELIMITER, static::CSV_ENCLOSURE, static::CSV_ESCAPE);
        rewind($f);
        $csv_line = stream_get_contents($f);
        return rtrim($csv_line) . static:: CSV_NEWLINE;
    }

    protected function getTemplate()
    {
        // TODO: Implement getTemplate() method.
    }
}
