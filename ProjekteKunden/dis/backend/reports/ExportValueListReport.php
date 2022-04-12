<?php

namespace app\reports;

use app\models\core\DisList;
use app\models\core\DisListItem;
use app\rbac\LimitedAccessRule;

/**
 * Class ExportValueListReport
 *
 * Exports all items of a value list as as CSV file.
 * The exported file can be imported to another DIS system.
 *
 * @package app\reports
 */
class ExportValueListReport extends ListCsvReport
{
    /**
     * {@inheritdoc}
     */
    const TITLE = 'Export value list as CSV file';
    /**
     * {@inheritdoc}
     * This report can be applied to no data models
     */
    const MODEL = 'NONE!!';

    /**
     * {@inheritdoc}
     */
    const REPORT_TYPE = 'export';

    protected $listname = '';
    protected $list = null;

    /**
     * Returns the filename for the generated CSV file
     * @param string[] $options Query parameters provided to the reportController
     * @return string
     */
    protected function getFilename($options) {
        return 'ValueList_' . $this->listname . '_' . date('Y-m-d_H:i') .'.csv';
    }


    protected function getModelClass($options) {
        return "app\\models\\core\\DisListItem";
    }

    protected function getDataProvider($options) {
        $dataProvider = null;
        $modelClass = $this->getModelClass($options);
        $query = call_user_func([$modelClass, "find"]);
        $query->andWhere(['list_id' => $this->list->id]);

        $dataProvider = new \yii\data\ActiveDataProvider([
            'query' => $query,
        ]);
        return $dataProvider;
    }


    protected function getColumns($options) {
        $columns = ["listname" => "listname"];
        foreach (parent::getColumns($options) as $column => $label) {
            if (!in_array($column, ['list_id', 'id'])) {
                $columns[$column] = $column;
            }
        }
        return $columns;
    }

    /**
     * {@inheritdoc}
     */
    function validateReport($options) {
        if (isset($_GET["listname"]))
            $this->listname = $_GET["listname"];
        else {
            $this->validateErrors[] = "You must provide the name of the value list as URL parameter 'listname'";
            $this->validateErrorCode = 500;
            return false;
        }

        $this->list = DisList::findOne(['list_name' => $this->listname]);
        if (!$this->list) {
            $this->validateErrors[] = "Value list '" . $this->listname . "' could not be found";
            $this->validateErrorCode = 404;
            return false;
        }
        return true;
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

    protected function getCsvRow($model, $columns) {
        $row = [];
        foreach ($columns as $column => $label) {
            if ($column == "listname")
                $row[] = $this->listname;
            else
                $row[] = isset($model->{$column}) ? $model->{$column} : "";
        }
        return $this->getcsv($row);
    }

}
