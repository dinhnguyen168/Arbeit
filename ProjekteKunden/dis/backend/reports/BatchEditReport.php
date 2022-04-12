<?php

namespace app\reports;

use app\components\helpers\DbHelper;
use app\components\templates\ModelTemplate;
use app\reports\Base as BaseReport;
use app\reports\interfaces\IHtmlReport;
use yii\base\InvalidConfigException;
use yii\base\Model;
use yii\data\ArrayDataProvider;
use yii\helpers\Inflector;


/**
 * Class BatchEditReport
 *
 * @package app\reports
 */
class BatchEditReport extends BaseReport implements IHtmlReport
{
    /**
     * {@inheritdoc}
     */
    const TITLE = 'Batch edit';
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

    function getCss()
    {
        $cssFile = \Yii::getAlias("@app/../web/css/report.css");
        $stylesheet = file_get_contents($cssFile);
        $stylesheet .= <<<EOT
        div.panel-body .table-container {
            overflow-x: auto;
            margin-bottom: 1em;
        }
        
        .panel-title{
            font-size: 22px;
            font-weight: bold;
        }

        .table {
            margin-bottom: 0;
        }
        
        .table > thead > tr > th, 
        .table > tbody > tr > td {
            vertical-align: top;
            padding-bottom: 0;
            padding: 10px 10px !important;
        }
        
        .table > thead > tr > th {
            white-space: nowrap;
        }
        
        .table tr.error {
            color: red;
        }
        
        td {
            min-width: 50%;
        }
        .inputs-options td {
            border-top: none !important;
        }
        .group-input-delete label {
            font-weight: bold;
            font-size: smaller;
        }
        .group-input label {
            width: 100%;
            font-weight: bold;
            font-size: smaller;
        }
        td.changed {
            background-color: yellow;
        }
        .selected-loader, .selected-loader-2 {
          border: 10px solid #f3f3f3;
          border-radius: 50%;
          border-top: 10px solid #3498db;
          width: 50px;
          height: 50px;
          -webkit-animation: spin 2s linear infinite; /* Safari */
          animation: spin 2s linear infinite;
        }
        
        /* Safari */
        @-webkit-keyframes spin {
          0% { -webkit-transform: rotate(0deg); }
          100% { -webkit-transform: rotate(360deg); }
        }
        
        @keyframes spin {
          0% { transform: rotate(0deg); }
          100% { transform: rotate(360deg); }
        }
        
        #batch-edit-form1 .alert.alert-warning {
            margin-top: 20px;
        }
        
        .summary {
            padding: 15px;
        }
        .switch {
          position: relative;
          display: inline-block;
          width: 60px;
          height: 34px;
        }
        
        .switch input { 
          opacity: 0;
          width: 0;
          height: 0;
        }
        
        .slider {
          position: absolute;
          cursor: pointer;
          top: 0;
          left: 0;
          right: 0;
          bottom: 0;
          background-color: #ccc;
          -webkit-transition: .4s;
          transition: .4s;
        }
        
        .slider:before {
          position: absolute;
          content: "";
          height: 26px;
          width: 26px;
          left: 4px;
          bottom: 4px;
          background-color: white;
          -webkit-transition: .4s;
          transition: .4s;
        }
        
        input:checked + .slider {
          background-color: #2196F3;
        }
        
        input:focus + .slider {
          box-shadow: 0 0 1px #2196F3;
        }
        
        input:disabled  + .slider {
          background-color: #ccc;
        }
        
        input:checked + .slider:before {
          -webkit-transform: translateX(26px);
          -ms-transform: translateX(26px);
          transform: translateX(26px);
        }
        
        /* Rounded sliders */
        .slider.round {
          border-radius: 34px;
        }
        
        .slider.round:before {
          border-radius: 50%;
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
        $this->columns = $this->getColumns($options);
        $dataProvider = $this->getDataProvider($options);
        $dataProvider->pagination = [
            'pageSize' => 50
        ];

        $modelTemplate = ModelTemplate::find($options['model']);
        $formTemplate = \Yii::$app->templates->getFormTemplate(Inflector::camel2id($modelTemplate->name, '-'));

        $batchEditForm = new BatchEditForm([
            'modelClass' => $this->getModelClass($options),
            'modelTemplate' => $modelTemplate,
            'formTemplate' => $formTemplate,
            'dataProvider' => $dataProvider,
            'specificIds' => isset($options['specific-ids']) ? $ids = preg_split('/,/', $options['specific-ids']) : (isset($options['id']) ? [$options['id']] : [])
        ]);

        $requestParams = \Yii::$app->getRequest()->getBodyParams();
        if (empty($requestParams)) {
            $requestParams = \Yii::$app->getRequest()->getQueryParams();
        }
        if ($batchEditForm->load($requestParams)) {
            $batchEditForm->validate();
        }

        $this->controller->layout = "report.vue.php";

        if($batchEditForm->step > 1) {
            $this->editModels($batchEditForm, true);
            $batchEditForm->setModelsToReviewDataProvider();
        }

        $content = $this->render('BatchEditReport.view.php', [
            'header' => $this->renderDisHeader([], "Batch Edit " . $options['model'] . 's'),
            'columns' => $this->columns,
            'batchEditForm' => $batchEditForm,
            'modelTemplate' => $modelTemplate,
            'formTemplate' => $formTemplate
        ]);

        $this->echoChars ($this->controller->renderContent($content));

        if($batchEditForm->step > 2) {
            $this->editModels($batchEditForm);
            $this->finish();
        }
        try {
            ob_end_flush();
        }
        catch (\Exception $e){}
        die();
    }

    protected function generateTableHead () {
        // $content = "<script>var scroller = setInterval(function() { window.scrollTo(0,document.body.scrollHeight);}, 10);</script>";
        $content = "<div class='report'><div class='panel panel-default'><div class='panel-heading'><h3 class='panel-title'>3. Show results</h3></div><div class='panel-body'><div class='table-container'><table class='table result-sample-table'>";
        $this->echoWithoutLineBreak(trim($content));
    }
    protected function generateLables($model) {
        $content = '<thead>';
        $content .= '<tr>';
        foreach ($model->attributeLabels() as $label) {
            $content .= '<th>' .$label. '</th>';
        }
        $content .= '</tr>';
        $content .= '</thead>';
        $this->echoWithoutLineBreak($content);
    }

    protected function generateRow ($model, $batchEditForm, $dirtyAttributes, $saved) {
        $content = '<tbody>';
        if(sizeof($model->errors)) {
            $content .= '<tr class="error">';
        } else {
            $content .= '<tr>';
        }
        foreach ($model->attributes as $key => $value) {
            $content .= '<td';
            if($batchEditForm->isChangedAttribute($key, $dirtyAttributes, $saved)) {
                $content .= ' class="changed"';
            }
            $content .= '>';
            if(is_array($value)) {
                $content .= implode(',', $value);
            }else {
                $content .= $value;
            }
            $content .= '</td>';
        }
        if(sizeof($model->errors)) {
            $content .= '<tr class="error"><td colspan="'.sizeof($model->attributes).'">';
            $content .= implode ( "<br />" , \yii\helpers\ArrayHelper::getColumn ( $model->errors , 0 , false ));
            $content .= '</td></tr>';
        }
        $this->echoWithoutLineBreak($content);
        // $content .= '</tbody></tr>';
    }

    protected function generateEndtable() {
        $content = '</tbody></table></div>';
        if($this->savedModelCount > 0) {
            $content .= '<div class="alert alert-success" role="alert">'.$this->savedModelCount.' models have been modified</div>';
        }
        if($this->notSavedModelCount > 0) {
            $content .= '<div class="alert alert-warning" role="alert">'.$this->notSavedModelCount.' models could not be modified</div>';
        }
        // $content .= '<script>clearInterval(scroller);</script>';
        $this->echoWithoutLineBreak($content);
    }

    private $areLabelsgenerated = false;
    public $notSavedModelCount = 0;
    public $savedModelCount = 0;

    protected function editModels($batchEditForm, $isPreview = false) {
        if(!$isPreview) {
            $this->generateTableHead();
        }

        $fieldsOptions = json_decode($batchEditForm->fieldsOptions);
        if($fieldsOptions == null) $fieldsOptions = [];
        if($batchEditForm->specificIds) {
            $query = $batchEditForm->modelClass::find()->andWhere(['id' => $batchEditForm->modelIdsToEdit]);
        } else {
            if ($isPreview) {
                $query = $batchEditForm->modelClass::find()->andWhere(['id' => $batchEditForm->modelIdsToEdit]);
            } else {
                $query = $batchEditForm->dataProvider->query;
            }
        }

        $unbuffered_db = DbHelper::getUnbufferedMysqlDb($batchEditForm->modelClass::getDb());
        set_time_limit(100);
        foreach ($query->batch(50, $unbuffered_db) as $models) {
            foreach ($models as $model) {
                foreach ($fieldsOptions as $fieldOptions) {
                    if($fieldOptions->operation == 'B') {
                        $batchEditForm->booleanOperation($model, $fieldOptions->columnName, $fieldOptions->newVal);
                    }
                    if($fieldOptions->operation == 'D' && $fieldOptions->delete) {
                        $batchEditForm->delateOperation($model, $fieldOptions->columnName);
                    }
                    if($fieldOptions->operation == 'DA' && $fieldOptions->deleteAll) {
                        $batchEditForm->delateAllOperation($model, $fieldOptions->columnName);
                    }
                    if($fieldOptions->operation == 'D_S') {
                        $batchEditForm->delateSingelSelectOperation($model, $fieldOptions->columnName, $fieldOptions->newVal, $fieldOptions->selectSource);
                    }
                    if($fieldOptions->operation == 'R' || $fieldOptions->operation == 'R_S') {
                        $batchEditForm->replaceOperation($model, $fieldOptions->columnName, $fieldOptions->newVal, $fieldOptions->type);
                    }
                    if($fieldOptions->operation == 'A') {
                        if(!$fieldOptions->newVal) {
                            $batchEditForm->addOperationsError($fieldOptions->columnName, 'For operation "Add" you should select one item at least');
                        } else {
                            $batchEditForm->addOperation($model, $fieldOptions->columnName, $fieldOptions->newVal, $fieldOptions->selectSource);
                        }
                    }
                    if($fieldOptions->operation == 'S+R') {
                        $batchEditForm->searchReplaceOperation($model, $fieldOptions->columnName, $fieldOptions->oldVal, $fieldOptions->newVal, $fieldOptions->type);
                    }
                    if($fieldOptions->operation == 'S+R_S') {
                        $batchEditForm->searchReplaceSelectOperation($model, $fieldOptions->columnName, $fieldOptions->oldVal, $fieldOptions->newVal, $fieldOptions->multiple, $fieldOptions->selectSource);
                    }

                    if($fieldOptions->operation == 'S+R_REGEX') {
                        if($fieldOptions->oldVal) {
                            if(preg_match('/'.$fieldOptions->oldVal.'/', '') !== false) {
                                $batchEditForm->searchReplaceWithRegexOperation($model, $fieldOptions->columnName, $fieldOptions->oldVal, $fieldOptions->newVal);
                            } else {
                                $batchEditForm->addOperationsError($fieldOptions->columnName, 'Invalid regex - no changes');
                            }
                        } else {
                            $batchEditForm->addOperationsError($fieldOptions->columnName, 'Invalid regex - no changes');
                        }
                    }
                }
                if($isPreview) {
                    if(in_array($model->id, $batchEditForm->modelIdsToEdit)) {
                        $model->validate();
                    }

                    $batchEditForm->addModelToReview($model);
                    if($model->errors) {
                        $batchEditForm->addModelToReviewError();
                    }
                } else {
                    if(!$this->areLabelsgenerated) $this->generateLables($model);
                    $dirtyAttributes = $model->getDirtyAttributes();

                    foreach (array_keys($dirtyAttributes) as $attribute) {
                        if (in_array($attribute, $model::COMBINED_ID_FIELDS)) {
                            foreach ($model->behaviors as $behaviorKey => $behavior) {
                                if (isset($behavior->combinedIdField) && $behavior->combinedIdField == $attribute) {
                                    $behavior->detach();
                                    break;
                                }
                            }
                        }
                    }

                    if($saved = $model->save()) {
                        $this->savedModelCount++;
                    } else {
                        $this->notSavedModelCount++;
                    }
                    $this->areLabelsgenerated = true;
                    $this->generateRow($model, $batchEditForm, $dirtyAttributes, $saved);
                }

            }
            usleep(300);
        }
        if (!$isPreview) {
            $this->generateEndtable();
        }
    }

    /**
     * Renders the foot of the output page.
     * This should be called at the end of the run() method of any inheriting class.
     */
    public function finish() {
        $this->controller->layout = "report_foot.vue.php";
        // Write output collected by calls to "echo()", "info()", "warning()", "error()"
        $this->echoChars ($this->controller->renderContent(""));
        die();
    }
}



/**
 * Class BatchEditForm
 * @package app\reports
 *
 */
class BatchEditForm extends Model
{
    /**
     * scenario step:
     * - 0: edit records
     * - 1: Preview edited records
     * - 2: Show result
     * scenario = "STEP" . $step
     */
    public $step = 0;

    public $modelClass;
    public $modelTemplate;
    public $formTemplate;
    public $dataProvider;
    public $specificIds;

    public $modelIdsToEdit = [];
    protected $formStep = 0;

    protected $pseudoOperationsVars= [];
    protected $pseudoOperationsValues = [];

    public $fieldsOptions;

    public function init()
    {
        if (empty($this->dataProvider)) {
            throw new InvalidConfigException('dataProvider is missing for BatchDeleteForm');
        }
        foreach ($this->modelTemplate->columns as $column) {
            if($column->name !== 'id' && $column->calculate == '') {
                $this->pseudoOperationsVars[] = $column->name . '_operation';
            }
        }
        parent::init();
    }

    public function __get($name)
    {
        if (in_array($name, $this->pseudoOperationsVars)) {
            if (isset($this->pseudoOperationsValues[$name])) {
                return $this->pseudoOperationsValues[$name];
            } else {
                return null;
            }
        }
        return parent::__get($name);
    }


    public function __set($name, $value)
    {
        if(in_array($name, $this->pseudoOperationsVars)){
            $this->pseudoOperationsValues[$name] = $value;
        }else {
            parent::__set($name, $value);
        }
    }

    public function load($data, $formName = null) {
        $loaded = false;
        if(sizeof($this->specificIds)) {
            $this->modelIdsToEdit = $this->specificIds;
        } else {
            $this->modelIdsToEdit = array_map(function($o) { return $o['id'];}, $this->dataProvider->getModels());
        }
        if (sizeof($data)) {
            $this->step = isset($data["BatchEditForm"]["step"]) ? intval($data["BatchEditForm"]["step"]) : 0;
            $this->setScenario("STEP" . $this->step);
            $loaded = parent::load($data, $formName);
            $this->step = intval($this->step);
        }
        return $loaded;
    }


    public function rules()
    {
        return [
            [['modelIdsToEdit', 'fieldsOptions'], 'safe', 'on' => ['STEP0', 'STEP1', 'STEP2']],
            ['modelIdsToEdit', 'required', 'on' => ['STEP0', 'STEP1', 'STEP2']],
            [$this->pseudoOperationsVars, 'safe']
        ];
    }

    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'modelIdsToEdit' => 'Records to Edit',
        ]);
    }

    public function afterValidate() {
        if (!$this->hasErrors()) {
            switch ($this->step) {
                case 1:
                case 2:
                    break;
            }
            if (!$this->hasErrors()) $this->step++;
        }
        return parent::afterValidate();
    }

    public function startForm($stepNo) {
        $this->formStep = $stepNo;
        $form = \yii\widgets\ActiveForm::begin([
            'id' => 'batch-edit-form' . $stepNo,
            'action' => '#formTarget' . $stepNo
        ]);
        $hiddenFields = [['step', ($stepNo + 1)]];
        /*switch ($stepNo) {
            case 2:
            case 1:
            case 0:
                foreach ($this->modelIdsToEdit as $id) {
                    $hiddenFields[] = ['modelIdsToEdit[]', $id];
                }
                break;
        }*/
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


    public function getSelectedModels() {
        return $this->dataProvider->getModels();
    }

    public function getAllSelectedModels() {
        $selectedIds = $this->modelIdsToEdit;
        return $this->modelClass::find()->andWhere(['id' => $selectedIds])->all();
    }

    private $_modelsToReview = [];
    private $_operationsErrors = [];
    private $_modelsToReviewError = 0;

    public $modelsToReviewDataProvider = null;
    public function setModelsToReviewDataProvider() {
        $requestParams = \Yii::$app->getRequest()->getBodyParams();
        if (empty($requestParams)) {
            $requestParams = \Yii::$app->getRequest()->getQueryParams();
        }

        return $this->modelsToReviewDataProvider = new ArrayDataProvider([
            'allModels' => $this->_modelsToReview,
            'pagination' => [
                'pageSize' => 50,
            ],
        ]);
    }

    public function getModelsToReview() {
        return $this->modelsToReviewDataProvider->getModels();
    }

    public function getAllModelsToReview() {
        return $this->_modelsToReview;
    }

    public function addModelToReview($model) {
        $this->_modelsToReview[] = $model;
    }

    public function getOperationsErrors() {
        return $this->_operationsErrors;
    }

    public function addOperationsError($columnName, $error) {
        $this->_operationsErrors[$columnName] = $error;
    }

    public function getModelsToReviewError () {
        return $this->_modelsToReviewError;
    }

    public function addModelToReviewError () {
        $this->_modelsToReviewError++;
    }

    public function booleanOperation($model, $columnName, $newVal) {
        $newVal = $newVal ? 1 : 0;
        $model->{$columnName} = $newVal;
    }
    public function delateOperation($model, $columnName) {
        $model->{$columnName} = null;
    }

    public function delateAllOperation($model, $columnName) {
        $model->{$columnName} = null;
    }

    public function delateSingelSelectOperation($model, $columnName, $newVal, $selectSource) {
        if($selectSource->type === 'list') {
            if($model->{$columnName}) {
                $values = explode(';', $model->{$columnName});
                foreach($values as $key => $value) {
                    if($value == $newVal) {
                        unset($values[$key]);
                        $model->{$columnName} = implode($values, ';');
                    }
                }
            }
        } else {
            if($model->{$columnName} === null) $model->{$columnName} = [];
            if(in_array($newVal, $model->{$columnName})) {
                unset($newVal, $model->{$columnName});
            }
        }
    }

    public function addOperation($model, $columnName, $newVal, $selectSource) {
        if($selectSource->type === 'list') {
            if($model->{$columnName}) {
                $model->{$columnName} = $model->{$columnName}.';'.implode($newVal, ';');
            } else {
                $model->{$columnName} = implode($newVal, ';');
            }
        } else {
            // if($model->{$columnName} === null) $model->{$columnName} = [];
            $model->{$columnName} = $newVal;
        }
    }

    public function replaceOperation($model, $columnName, $newVal, $type) {
        if($type == 'datetime') {
            $phpTime = strtotime($newVal);
            $model->{$columnName} = date ('Y-m-d H:i:s', $phpTime);
        } else if($type == 'date') {
            $phpTime = strtotime($newVal);
            $model->{$columnName} = date ('Y-m-d', $phpTime);
        } else if($type == 'time') {
            $phpTime = strtotime($newVal);
            $model->{$columnName} = date ('H:i:s', $phpTime);
        } else {
            $model->{$columnName} = $newVal;
        }
    }

    public function searchReplaceOperation($model, $columnName, $oldVal, $newVal, $type) {
        if($model->{$columnName} == $oldVal) {
            if($type == 'datetime') {
                $phpTime = strtotime($newVal);
                $model->{$columnName} = date ('Y-m-d H:i:s', $phpTime);
            } else if($type == 'date') {
                $phpTime = strtotime($newVal);
                $model->{$columnName} = date ('Y-m-d', $phpTime);
            } else if($type == 'date') {
                $phpTime = strtotime($newVal);
                $model->{$columnName} = date ('H:i:s', $phpTime);
            } else {
                $model->{$columnName} = $newVal;
            }
        }
    }

    public function searchReplaceSelectOperation($model, $columnName, $oldVal, $newVal, $multiple, $selectSource) {
        if($selectSource->type === 'list') {
            if(!$multiple) {
                if($model->{$columnName}) {
                    if($model->{$columnName} === $oldVal) {
                        $model->{$columnName} = $newVal;
                    }
                }
            } else {
                if($model->{$columnName}) {
                    $values = explode(';', $model->{$columnName});
                    foreach($values as $key => $value) {
                        if($value == $oldVal) {
                            foreach ($newVal as $item) {
                                if($values[$key] === $oldVal) {
                                    $values[$key] = $item;
                                } else {
                                    $values[] = $item;
                                }
                            }
                        }
                    }
                    $model->{$columnName} = implode($values, ';');
                }
            }
        } else {
            if(!$multiple) {
                if($model->{$columnName} == $oldVal) {
                    $model->{$columnName} = $newVal;
                }
            } else {
                if($model->{$columnName} === null) $model->{$columnName} = [];
                foreach ($model->{$columnName} as $key => $value) {
                    if($value == $oldVal) {
                        if($model->{$columnName}[$key] == $oldVal) {
                            $model->{$columnName}[$key] = $newVal;
                        } else {
                            $model->{$columnName}[] = $newVal;
                        }
                    }
                }
            }
        }
    }

    public function searchReplaceWithRegexOperation($model, $columnName, $oldVal, $newVal) {
        $model->{$columnName} = preg_replace('/'.$oldVal.'/', $newVal, $model->{$columnName});
    }

    public function getIsFinished() {
        $isFinished = $this->step >= 3 && !$this->hasErrors();
        return $isFinished;
    }

    public function isDirtyAttribute($model, $column) {
        $dirtyAttributes = $model->getDirtyAttributes();
        $isDirty = isset($dirtyAttributes[$column]);
        return $isDirty;
    }

    public function isChangedAttribute($column, $dirtyAttributes, $saved) {
        if($saved) {
            foreach ($dirtyAttributes as $key => $value) {
                if($key == $column) {
                    return true;
                }
            }
        }
        return false;
    }
}
