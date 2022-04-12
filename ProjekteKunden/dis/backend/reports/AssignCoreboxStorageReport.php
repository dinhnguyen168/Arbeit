<?php

namespace app\reports;

use app\reports\Base as BaseReport;
use app\reports\interfaces\IHtmlReport;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\base\Model;


/**
 * Class AssignCoreboxStorageReport
 *
 * @package app\reports
 */
class AssignCoreboxStorageReport extends BaseReport implements IHtmlReport
{
    /**
     * {@inheritdoc}
     */
    const TITLE = 'Assign storage location';
    /**
     * {@inheritdoc}
     * This report can be allied for any data model
     */
    const MODEL = '^CurationCorebox$';
    /**
     * {@inheritdoc}
     * This report is for a single or multiple records
     */
    const SINGLE_RECORD = null;

    /**
     * {@inheritdoc}
     */
    const REPORT_TYPE = 'action';


    protected $columns = [];

    function getJs()
    {
        return <<<EOT
            function selectAllRecords(checkAll) {
                for (i in checkAll.form.elements) {
                    var element = checkAll.form.elements[i];
                    if (element != checkAll) {
                        if (element.type && element.type == 'checkbox') {
                            element.checked = checkAll.checked;
                        }
                    } 
                }
            }
EOT;
    }

    function getCss()
    {
        $cssFile = \Yii::getAlias("@app/../web/css/report.css");
        $stylesheet = file_get_contents($cssFile);
        $stylesheet .= <<<EOT
        div.panel-body .table-container {
            overflow-x: auto;
            margin-bottom: 1em;
        }

        .table {
            margin-bottom: 0;
        }
        
        div.panel-body.form0 table {
            margin-bottom: 1em;
        }
        
        .table > thead > tr > th, 
        .table > tbody > tr > td {
            padding-bottom: 0;
        }
        
        .table tr.error {
            color: red;
        }
EOT;

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

    protected function getDataProvider($options) {
        $dataProvider = parent::getDataProvider($options);
        $dataProvider->query->orderBy([
            "curation_corebox.corebox_combined_id" => SORT_ASC
        ]);
        return $dataProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function generate($options = [])
    {
        $ancestorValues = [];
        $this->columns = $this->getColumns($options);
        $dataProvider = $this->getDataProvider($options);
        $dataProvider->pagination = false;
        $models = $dataProvider->getModels();

        if (sizeof($models)) {
            $model = $models[0];
            $ancestorValues = $this->getAncestorValues($model);
            $this->setExpedition($model);
        }
        $headerAttributes = [];
        foreach ($ancestorValues as $ancestorValue) $headerAttributes[$ancestorValue[1]] = $ancestorValue[0];

        $assignLocationForm = new AssignLocationForm([
            'modelClass' => $this->getModelClass($options),
            'dataProvider' => $dataProvider,
            'specificIds' => isset($options['specific-ids']) ? $ids = preg_split('/,/', $options['specific-ids']) : (isset($options['id']) ? [$options['id']] : [])
        ]);


        if ($assignLocationForm->load(\Yii::$app->getRequest()->getBodyParams())) {
            $assignLocationForm->validate();
        }
        $this->content = $this->render(null, [
            'header' => $this->renderDisHeader($headerAttributes, "Assign storage location"),
            'assignLocationForm' => $assignLocationForm,
            'columns'
        ]);
    }

}



/**
 * Class AssignLocationForm
 * @package app\reports
 *
 */
class AssignLocationForm extends Model
{
    /**
     * Szenario step:
     * - 0: Select the corebox records and the location to assign
     * - 1: Preview corebox records with assigned location
     * - 2: Show assigned corebox records
     * scenario = "STEP" . $step
     */
    public $step = 0;

    public $modelClass;
    public $dataProvider;
    public $specificIds;

    public $storageLocationId;

    public $modelIds = [];
    protected $formStep = 0;


    public function init()
    {
        if (empty($this->dataProvider)) {
            throw new InvalidConfigException('dataProvider is missing for BatchDeleteForm');
        }
        $modelClass = $this->modelClass;

        parent::init();
    }

    public function load($data, $formName = null) {
        $loaded = false;
        if (sizeof($data)) {
            $this->step = isset($data["AssignLocationForm"]["step"]) ? intval($data["AssignLocationForm"]["step"]) : 0;
            $this->setScenario("STEP" . $this->step);
            $loaded = parent::load($data, $formName);
            $this->step = intval($this->step);
        }
        if ($this->step == 0 && sizeof($this->specificIds)) {
            $this->modelIds = $this->specificIds;
        }

        return $loaded;
    }


    public function rules()
    {
        return [
            [['modelIds', 'storageLocationId'], 'safe', 'on' => ['STEP0', 'STEP1', 'STEP2']],
            [['modelIds', 'storageLocationId'], 'required', 'on' => ['STEP1', 'STEP2']],
            ['modelIds', 'validateModelIds', 'on' => ['STEP1', 'STEP2']]
        ];
    }

    public function validateModelIds($attribute) {
        $ids = [];
        foreach ($this->modelIds as $id) {
            $id = intval($id);
            if ($id > 0) $ids[] = $id;
        }
        $this->modelIds = array_unique($ids);
        if (sizeof($this->modelIds) == 0) {
            $this->addError("modelIds", "Please select at least one corebox.");
        }
    }

    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'modelIds' => 'Coreboxes to assign to new location',
            'storageLocationId' => 'New storage location'
        ]);
    }



    public function afterValidate() {
        if (!$this->hasErrors()) {
            switch ($this->step) {
                case 1:
                    break;
                case 2:
                    $this->assignLocation();
                    break;
            }
            if (!$this->hasErrors()) $this->step++;
        }
        return parent::afterValidate();
    }

    public function startForm($stepNo) {
        $this->formStep = $stepNo;
        $form = \yii\widgets\ActiveForm::begin([
            'id' => 'assign-location-form' . $stepNo,
            'action' => '#formTarget' . $stepNo
        ]);
        $hiddenFields = [['step', ($stepNo + 1)]];
        switch ($stepNo) {
            case 3:
            case 2:
            case 1:
                foreach ($this->modelIds as $id) {
                    $hiddenFields[] = ['modelIds[]', $id];
                }
                $hiddenFields[] = ['storageLocationId', $this->storageLocationId];
                break;
        }
        $html = '';
        foreach ($hiddenFields as $hiddenField) {
            $name = $hiddenField[0];
            $value = $hiddenField[1];
            $html .= $form->field($this, $name, ['template' => '{input}', 'options' => ['tag' => false]])->hiddenInput($value ? ['value' => $value] : [])->label(false);
        }
        echo $html;
        return $form;
    }

    public function showErrors() {
        echo '<div id="formTarget' . $this->formStep . '"></div>';
        if ($this->formStep == $this->step-1 && $this->hasErrors()) {
            echo '<div class="alert alert-danger" role="alert"><ul>';
            foreach ($this->getErrorSummary(false) as $error) {
                echo '<li>' . $error . '</li>';
            }
            echo '</ul></div>';
        }
    }

    public function endForm() {
        \yii\widgets\ActiveForm::end();
    }


    public function getModels() {
        return $this->dataProvider->getModels();
    }

    public function getSelectedModels() {
        $selectedIds = $this->modelIds;
        return array_filter($this->getModels(), function($model) use($selectedIds) {
            return in_array($model->id, $selectedIds);
        });
    }

    public function getAssignedModels() {
        return $this->_assignedModels;
    }

    public function getErrorAssignedModels() {
        return $this->_errorAssignedModels;
    }

    private $_assignedModels = [];
    private $_errorAssignedModels = [];


    protected function assignLocation() {
        $this->_assignedModels = [];
        $transaction = \Yii::$app->db->beginTransaction();
        foreach ($this->getSelectedModels() as $model) {
            set_time_limit(90);
            try {
                $success = true;
                $model->storage_id = $this->storageLocationId;
                $model->save();
            } catch (\Exception $e) {
                while ($e->getPrevious()) $e = $e->getPrevious();
                $model->addError('', $e->getMessage());
                $success = false;
            }

            if ($success)
                $this->_assignedModels[] = $model;
            else
                $this->_errorAssignedModels[] = $model;
        }
        $transaction->commit();
    }

    public function getIsFinished() {
        $isFinished = $this->step >= 3 && !$this->hasErrors();
        return $isFinished;
    }

    public function getStorageLocations() {
        $data = [];
        foreach (\app\models\CurationStorage::find()->orderBy(['combined_id' => SORT_ASC])->batch() as $models) {
            foreach ($models as $model) {
                $data[$model->id] = $model->combined_id;
            }
        }
        return $data;
    }

    public function getStorageLocation() {
        $storageLocation = null;
        if ($this->storageLocationId > 0) {
            $storageLocation = \app\models\CurationStorage::find()->where(['id' => $this->storageLocationId])->one();
        }
        return $storageLocation;
    }
}
