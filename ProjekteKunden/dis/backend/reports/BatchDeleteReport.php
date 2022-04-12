<?php

namespace app\reports;

use app\reports\Base as BaseReport;
use app\reports\interfaces\IHtmlReport;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\base\Model;


/**
 * Class BatchDeleteReport
 *
 * @package app\reports
 */
class BatchDeleteReport extends BaseReport implements IHtmlReport
{
    /**
     * {@inheritdoc}
     */
    const TITLE = 'Batch delete / Recursive delete';
    /**
     * {@inheritdoc}
     * This report can be allied for any data model
     */
    const MODEL = '.*';
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
        /*
         * Only available for admins??
        if (!\Yii::$app->user->can('sa')) {
            $this->validateErrorCode = 500;
            $this->validateErrors[] = "This report can only be used by administrators";
            $valid = false;
        }
        */
        return $valid;
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

        $batchDeleteForm = new BatchDeleteForm([
            'modelClass' => $this->getModelClass($options),
            'dataProvider' => $dataProvider,
            'specificIds' => isset($options['specific-ids']) ? $ids = preg_split('/,/', $options['specific-ids']) : (isset($options['id']) ? [$options['id']] : [])
        ]);


        if ($batchDeleteForm->load(\Yii::$app->getRequest()->getBodyParams())) {
            $batchDeleteForm->validate();
        }
        $this->content = $this->render(null, [
            'header' => $this->renderDisHeader($headerAttributes, "Batch delete " . $options['model'] . 's'),
            'columns' => $this->columns,
            'batchDeleteForm' => $batchDeleteForm
        ]);
    }

}



/**
 * Class UndoSampleSeriesForm
 * @package app\reports
 *
 */
class BatchDeleteForm extends Model
{
    /**
     * Szenario step:
     * - 0: Select the records to delete
     * - 1: Preview records to delete
     * - 2: Show deleted recrods
     * scenario = "STEP" . $step
     */
    public $step = 0;

    public $modelClass;
    public $dataProvider;
    public $specificIds;
    public $canDeleteRecursive = false;

    public $modelIdsToDelete = [];
    public $deleteRecursive = false;
    protected $formStep = 0;


    public function init()
    {
        if (empty($this->dataProvider)) {
            throw new InvalidConfigException('dataProvider is missing for BatchDeleteForm');
        }
        $modelClass = $this->modelClass;
        $this->canDeleteRecursive = $modelClass::canDeleteRecursive();

        parent::init();
    }

    public function load($data, $formName = null) {
        $loaded = false;
        if (sizeof($data)) {
            $this->step = isset($data["BatchDeleteForm"]["step"]) ? intval($data["BatchDeleteForm"]["step"]) : 0;
            $this->setScenario("STEP" . $this->step);
            $loaded = parent::load($data, $formName);
            $this->step = intval($this->step);
        }
        if ($this->step == 0 && sizeof($this->specificIds)) {
            $this->modelIdsToDelete = $this->specificIds;
        }

        return $loaded;
    }


    public function rules()
    {
        return [
            [['modelIdsToDelete', 'deleteRecursive'], 'safe', 'on' => ['STEP0', 'STEP1', 'STEP2']],
            ['modelIdsToDelete', 'required', 'on' => ['STEP1', 'STEP2']],
            ['modelIdsToDelete', 'validateModelIdsToDelete', 'on' => ['STEP1', 'STEP2']],
            ['deleteRecursive', 'validateDeleteRecursive']
        ];
    }

    public function validateModelIdsToDelete($attribute) {
        $ids = [];
        foreach ($this->modelIdsToDelete as $id) {
            $id = intval($id);
            if ($id > 0) $ids[] = $id;
        }
        $this->modelIdsToDelete = array_unique($ids);
        if (sizeof($this->modelIdsToDelete) == 0) {
            $this->addError("modelIdsToDelete", "Please select at least one record to delete.");
        }
    }

    public function validateDeleteRecursive($attribute)
    {
        if (!$this->canDeleteRecursive) $this->deleteRecursive = false;
    }

    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'modelIdsToDelete' => 'Records to delete',
            'deleteRecursive' => 'Recursively delete all related records'
        ]);
    }



    public function afterValidate() {
        if (!$this->hasErrors()) {
            switch ($this->step) {
                case 1:
                    break;
                case 2:
                    $this->deleteModels();
                    break;
            }
            if (!$this->hasErrors()) $this->step++;
        }
        return parent::afterValidate();
    }

    public function startForm($stepNo) {
        $this->formStep = $stepNo;
        $form = \yii\widgets\ActiveForm::begin([
            'id' => 'batch-delete-form' . $stepNo,
            'action' => '#formTarget' . $stepNo
        ]);
        $hiddenFields = [['step', ($stepNo + 1)]];
        switch ($stepNo) {
            case 3:
            case 2:
            case 1:
                $hiddenFields[] = ['deleteRecursive', $this->deleteRecursive];
                foreach ($this->modelIdsToDelete as $id) {
                    $hiddenFields[] = ['modelIdsToDelete[]', $id];
                }
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


    public function getReferencingModelTemplates($modelTemplate = null) {
        if ($modelTemplate == null) {
            $modelTemplate = \Yii::$app->templates->getModelTemplate(substr($this->modelClass, strrpos($this->modelClass, '\\') + 1));
        }

        $templates = $modelTemplate->getReferencingModelTemplates();
        foreach ($templates as $name => $template) {
            if ($name !== $modelTemplate->fullName) {
                foreach ($this->getReferencingModelTemplates($template) as $name => $subTemplate) {
                    if (!isset($templates[$name])) $templates[$name] = $subTemplate;
                }
            }
        }
        return $templates;
    }

    public function getModels() {
        return $this->dataProvider->getModels();
    }

    public function getSelectedModels() {
        $selectedIds = $this->modelIdsToDelete;
        return array_filter($this->getModels(), function($model) use($selectedIds) {
            return in_array($model->id, $selectedIds);
        });
    }

    private $_deletedModels = [];
    private $_errorDeleteModels = [];
    private $_deleteError = "";

    public function getDeletedModels() {
        return $this->_deletedModels;
    }

    public function getDeleteErrorModels() {
        return $this->_errorDeleteModels;
    }

    public function getDeleteError() {
        return $this->_deleteError;
    }

    protected function deleteModels() {
        $this->_deletedModels = [];
        $this->_errorDeleteModels = [];
        if ($this->deleteRecursive) {
            $where = ['IN', 'id', $this->modelIdsToDelete];
            $modelClass = $this->modelClass;
            $transaction = \Yii::$app->db->beginTransaction();
            try {
                $modelClass::deleteAllRecursive($where);
                $transaction->commit();
                $this->_deletedModels = $this->getSelectedModels();
            } catch (\Exception $e) {
                $this->_deleteError = $e->getMessage();
                $transaction->rollBack();
            }
        }
        else {
            $transaction = \Yii::$app->db->beginTransaction();
            foreach ($this->getSelectedModels() as $model) {
                set_time_limit(90);
                try {
                    $success = true;
                    if ($this->deleteRecursive)
                        $model->deleteRecursive();
                    else
                        $model->delete();
                } catch (\Exception $e) {
                    while ($e->getPrevious()) $e = $e->getPrevious();
                    $model->addError('', $e->getMessage());
                    $success = false;
                }

                if ($success)
                    $this->_deletedModels[] = $model;
                else
                    $this->_errorDeleteModels[] = $model;
            }
            $transaction->commit();
        }
    }

    public function getIsFinished() {
        $isFinished = $this->step >= 3 && !$this->hasErrors();
        return $isFinished;
    }
}
