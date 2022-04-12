<?php
namespace app\reports;


use app\reports\interfaces\IHtmlReport;/**
 * Class ListReport
 *
 *  Generates an html page with a list of all filtered records in the form.
 * This report can be called for every data model; it can be used for the list of records.
 * @package app\reports
 */
class ListReport extends Base implements IHtmlReport
{
    /**
     * {@inheritdoc}
     */
    const TITLE = 'List of records';
    /**
     * {@inheritdoc}
     * This report can be applied to every data model.
     */
    const MODEL = '.*';

    protected $modelShortName = "";
    protected $columns = [];

    function getJs()
    {
        return '';
    }

    function getCss()
    {
        $cssFile = \Yii::getAlias("@app/../web/css/report.css");
        $stylesheet = file_get_contents($cssFile);
        return $stylesheet;
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
        $this->columns = $this->getColumns($options);
        $dataProvider = $this->getDataProvider($options);

        $this->content = $this->_generate($dataProvider);
    }

    /**
     * Generate the report with all records in the dataProvider
     * @param \yii\data\ActiveDataProvider $dataProvider
     * @return string HTML of the rendered report
     */
    protected function _generate($dataProvider) {
        $dataProvider->pagination = false;

        $ancestorValues = [];
        $manyToManyValues = [];
        $oneToManyValues = [];
        $models = $dataProvider->getModels();
        if (sizeof($models)) {
            $ancestorValues = $this->getAncestorValues($models[0]);
            $this->setExpedition($models[0]);
        }
        $headerAttributes = [];
        foreach ($ancestorValues as $ancestorValue) $headerAttributes[$ancestorValue[1]] = $ancestorValue[0];

        foreach ($models as $model) {
            $manyToManyValues = $this->getManyToManyValues($model);
            $oneToManyValues = $this->getOneToManyValue($model);
            foreach ($manyToManyValues as $key => $value) $this->columns[$key] = $value[1];
            foreach ($oneToManyValues as $key => $value) $this->columns[$key] = $value[1];
        }

        return $this->render(null, [
            'header' => $this->renderDisHeader($headerAttributes, "List of " . $this->modelShortName . " records"),
            'columns' => $this->columns,
            'oneToManyValues' => $oneToManyValues,
            'manyToManyValues' => $manyToManyValues,
            'models' => $models
        ]);
    }


}
