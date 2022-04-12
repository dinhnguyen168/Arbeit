<?php

namespace app\reports;

use app\models\CurationSample;
use app\reports\Base as BaseReport;
use app\reports\interfaces\IHtmlReport;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\base\Model;
use yii\helpers\ArrayHelper;


/**
 * Class SplitSectionReport
 *
 * @package app\reports
 */
class UndoSampleSeriesReport extends BaseReport implements IHtmlReport
{
    /**
     * {@inheritdoc}
     */
    const TITLE = 'Undo samples series';
    /**
     * {@inheritdoc}
     * This report can be allied for any data model
     */
    const MODEL = '^(CurationSample)$';
    /**
     * {@inheritdoc}
     * This report is for a single record
     */
    const SINGLE_RECORD = true;

    /**
     * {@inheritdoc}
     */
    const REPORT_TYPE = 'action';

    function getJs()
    {
        return <<<EOT
            function selectAllSamples(checkAll) {
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
EOT;

        return $stylesheet;
    }

    /**
     * {@inheritdoc}
     */
    function validateReport($options) {
        $valid = parent::validateReport($options);
        $valid = $this->validateColumns("CoreSection", ['core_id', 'combined_id', 'core']) && $valid;
        $valid = $this->validateColumns("CurationSectionSplit", ['section_id']) && $valid;
        $valid = $this->validateColumns("CurationSample", ['section_split_id', 'top', 'bottom']) && $valid;
        return $valid;
    }



    /**
     * {@inheritdoc}
     */
    public function generate($options = [])
    {
        $modelClass = $this->getModelClass($options);
        $dataProvider = $this->getDataProvider($options);
        $models = $dataProvider->getModels();
        if (count($models) !== 1) {
            throw new Exception(count($models) . " record was found to copy. Only one is needed.");
        }
        $modelToCopy = $models[0];
        $ancestorValues = $this->getAncestorValues($modelToCopy->core);
        $this->setExpedition($modelToCopy);

        $headerAttributes = [];
        foreach ($ancestorValues as $ancestorValue) $headerAttributes[$ancestorValue[1]] = $ancestorValue[0];

        $showSampleColumns = [
            'combined_id',
            'sample_date',
            'top',
            'interval',
            'bottom',
            'analyst',
            'sample_type',
            'section_top_msbf',
            'request_no',
            'amount',
            'amount_unit',
            'volume',
            'sample_top_mbsf',
            'sample_bottom_mbsf',
//            'scientist'
        ];

        $labels = $modelToCopy->attributeLabels();
        $existingColumns = array_keys($labels);
        for ($i=sizeof($showSampleColumns); $i--; $i>=0) {
            if (!in_array($showSampleColumns[$i], $existingColumns)) unset($showSampleColumns[$i]);
        }
        $sampleColumnLabels=[];
        foreach ($showSampleColumns as $column) {
            $sampleColumnLabels[$column] = $labels[$column];
        }

        $sampleSeriesForm = new UndoSampleSeriesForm([
            'modelToCopy' => $modelToCopy
        ]);
        if ($sampleSeriesForm->load(\Yii::$app->getRequest()->getBodyParams())) {
            $sampleSeriesForm->validate();
        }
        $this->content = $this->render(null, [
            'header' => $this->renderDisHeader($headerAttributes, "Undo sample series"),
            'sampleSeriesForm' => $sampleSeriesForm,
            'sampleColumnLabels' => $sampleColumnLabels
        ]);
    }

}



/**
 * Class UndoSampleSeriesForm
 * @package app\reports
 *
 * @property boolean canCreateSamples returns true if all samples are valid and there are no invalid samples
 * @property boolean isSamplingArchive returns true is at least one selected split is an archive split
 */
class UndoSampleSeriesForm extends Model
{
    /**
     * Szenario step:
     * - 0: Select the samples to delete
     * - 1: Preview samples to delete
     * - 2: Show deleted samples
     * scenario = "STEP" . $step
     */
    public $step = 0;

    public $modelToCopy;
    public $matchingSamples = [];
    public $sampleIdsToDelete = [];
    protected $formStep = 0;


    public function init()
    {
        if (empty($this->modelToCopy)) {
            throw new InvalidConfigException('modelToCopy is missing for SampleSeriesForm');
        }
        parent::init();
    }

    public function load($data, $formName = null) {
        $loaded = false;
        if (sizeof($data)) {
            $this->step = isset($data["UndoSampleSeriesForm"]["step"]) ? intval($data["UndoSampleSeriesForm"]["step"]) : 0;
            $this->setScenario("STEP" . $this->step);
            $loaded = parent::load($data, $formName);
            $this->step = intval($this->step);
        }
        $this->findMatchingSamples();
        return $loaded;
    }


    public function rules()
    {
        return [
            ['sampleIdsToDelete', 'safe', 'on' => ['STEP0']],
            ['sampleIdsToDelete', 'required', 'on' => ['STEP1', 'STEP2']],
            ['sampleIdsToDelete', 'validateSampleIdsToDelete', 'on' => ['STEP1', 'STEP2']],
        ];
    }

    public function validateSampleIdsToDelete($attribute) {
        $ids = [];
        foreach ($this->sampleIdsToDelete as $id) {
            $id = intval($id);
            if ($id > 0) $ids[] = $id;
        }
        $this->sampleIdsToDelete = array_unique($ids);
        if (sizeof($this->sampleIdsToDelete) == 0) {
            $this->addError("sampleIdsToDelete", "Please select at least one sample to delete.");
        }
    }

    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'sampleIdsToDelete' => 'Samples to delete'
        ]);
    }



    public function afterValidate() {
        if (!$this->hasErrors()) {
            switch ($this->step) {
                case 1:
                    break;
                case 2:
                    $this->deleteSamples();
                    break;
            }
            if (!$this->hasErrors()) $this->step++;
        }
        return parent::afterValidate();
    }

    public function startForm($stepNo) {
        $this->formStep = $stepNo;
        $form = \yii\widgets\ActiveForm::begin([
            'id' => 'sample-series-form' . $stepNo,
            'action' => '#formTarget' . $stepNo
        ]);
        $hiddenFields = [['step', ($stepNo + 1)]];
        switch ($stepNo) {
            case 3:
            case 2:
            case 1:
                foreach ($this->sampleIdsToDelete as $id) {
                    $hiddenFields[] = ['sampleIdsToDelete[]', $id];
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

    public function getSplitsToSample () {
        return ArrayHelper::index($this->splitsToSample, null, ['section']);
    }

    protected function findMatchingSamples() {
        $attributes = ['sample_date', 'analyst', 'sample_type', 'request_no', 'amount', 'amount_unit', 'scientist'];
        $attributes = array_intersect($attributes, array_keys($this->modelToCopy->attributes));

        $query = CurationSample::find()
            ->innerJoin('curation_section_split', 'curation_sample.section_split_id = curation_section_split.id')
            ->innerJoin('core_section', 'curation_section_split.section_id = core_section.id')
            ->innerJoin('core_core', 'core_section.core_id = core_core.id')
            ->andWhere(['core_core.hole_id' => $this->modelToCopy->core->hole_id])
            ->andWhere(['<>', $this->modelToCopy->tableName() . ".id", $this->modelToCopy->id]);
        foreach ($attributes as $attribute) {
            $query->andWhere([$this->modelToCopy->tableName() . "." . $attribute => $this->modelToCopy->{$attribute}]);
        }

        $intervalTopDepth = $this->modelToCopy->section->top_depth + ($this->modelToCopy->top / 100.0);
        foreach($query->batch() as $samples) {
            foreach ($samples as $sample) {
                if ($sample->section->top_depth + ($sample->top / 100.0) > $intervalTopDepth) {
                    $this->matchingSamples[] = $sample;
                }
            }
        }
    }

    protected function deleteSamples() {
        $transaction = \Yii::$app->db->beginTransaction();
        try {
            foreach ($this->samplesToDelete as $sample) {
                if (!$sample->delete()) {
                    throw new \Exception ("Could not delete sample " . $sample->isgn);
                }
            }
            $transaction->commit();
        } catch (\Exception $e) {
            $this->addError("", "Could not delete samples: " . $e->getMessage());
            $transaction->rollBack();
        }
    }

    public function getSamplesToDelete () {
        $samplesToDelete = [];
        foreach ($this->matchingSamples as $sample) {
            if (in_array(intval($sample->id), $this->sampleIdsToDelete)) {
                $samplesToDelete[] = $sample;
            }
        }
        return $samplesToDelete;
    }

    public function getIsFinished() {
        $isFinished = $this->step >= 3 && !$this->hasErrors();
        return $isFinished;
    }
}
