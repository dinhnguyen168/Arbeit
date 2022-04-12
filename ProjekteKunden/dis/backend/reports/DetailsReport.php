<?php

namespace app\reports;

use app\reports\interfaces\IHtmlReport;

/**
 * Class DetailsReport
 *
 * Generates an html page with all columns (an ancestor names) of any model.
 * This report can be called for every data model; it is applied to the current single record.
 *
 * @package app\reports
 */
class DetailsReport extends Base implements IHtmlReport
{
    /**
     * {@inheritdoc}
     */
    const TITLE = 'Details of record';
    /**
     * {@inheritdoc}
     * This report can be allied for any data model
     */
    const MODEL = '.*';
    /**
     * {@inheritdoc}
     * This report is for a single record
     */
    const SINGLE_RECORD = true;

    /**
     * Name of the model to display in the header of the report
     * @var string
     */
    protected $modelShortName = "";

    /**
     * Colums to show in the report
     * @var array Columns
     */
    protected $columns = [];


    function getCss()
    {
        $cssFile = \Yii::getAlias("@app/../web/css/report.css");
        $stylesheet = file_get_contents($cssFile);
        return $stylesheet . <<<'EOB'
            table.report tr td {
                padding-right: 1em;
            }
            table.report tr td:first-child {
                padding-right: 4em;
            }
EOB;
    }


    /**
     * {@inheritdoc}
     */
    function validateReport($options) {
        $modelClass = $this->getModelClass($options);
        $valid = parent::validateReport($options);
        $valid = $this->validateColumns($modelClass, []) && $valid;
        if (isset($options["columns"])) {
            $valid = $this->validateColumns($modelClass, explode(",", $options["columns"])) && $valid;
        }
        return $valid;
    }


    /**
     * {@inheritdoc}
     */
    public function generate($options = []) {
        $modelClass = $this->getModelClass($options);
        $this->modelShortName = $modelClass::SHORT_NAME;
        $id = $options["id"];

        $this->columns = array_merge($this->getColumns($options));
        $query = call_user_func([$modelClass, 'find'])->andWhere(['id' => $id]);
        $dataProvider = $this->getDataProvider($query);
        $this->content = $this->_generate($dataProvider);
    }

    /**
     * Generates the report for all records in the dataProvider
     * @param \yii\data\ActiveDataProvider $dataProvider
     * @return string HTML of rendered report
     */
    protected function _generate($dataProvider) {
        $dataProvider->pagination = false;

        $ancestorValues = [];
        $manyToManyValues = [];
        $oneToManyValues = [];
        $models = $dataProvider->getModels();
        if (sizeof($models) > 1) die ("Should be single record report!");

        $model = $models[0];
        $ancestorValues = $this->getAncestorValues($model);
        $manyToManyValues = $this->getManyToManyValues($model);
        $oneToManyValues = $this->getOneToManyValue($model);
        $this->setExpedition($model);

        $headerAttributes = [];
        foreach ($ancestorValues as $ancestorValue) $headerAttributes[$ancestorValue[1]] = $ancestorValue[0];
        foreach ($manyToManyValues as $key => $value) $this->columns[$key] = $value[1];
        foreach ($oneToManyValues as $key => $value) $this->columns[$key] = $value[1];
        $value = $model->{$model::NAME_ATTRIBUTE};
        if ($model instanceof \app\models\CoreCore && isset($model->core_type)) {
            $value .= " " . $model->core_type;
        }
        $headerAttributes[$model::SHORT_NAME] = $value;

        return $this->render(null, [
            'model' => $model,
            'columns' => $this->columns,
            'oneToManyValues' => $oneToManyValues,
            'manyToManyValues' => $manyToManyValues,
            'header' => $this->renderDisHeader($headerAttributes, "Details of " . $this->modelShortName . " record")
        ]);
    }

}
