<?php

namespace app\reports;

use app\models\CurationSample;
use app\models\CurationSectionSplit;
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
class CreateSampleSeriesReport extends BaseReport implements IHtmlReport
{
    /**
     * {@inheritdoc}
     */
    const TITLE = 'Create samples series';
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
        $this->getView()->registerAssetBundle('yii\bootstrap\BootstrapPluginAsset');
        return '';
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
        
        .table tr.warning {
            background-color: yellow;
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
//        $dataProvider = $this->extendDataProvider($modelClass, $dataProvider);
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
            'id',
            'sample_date',
            'top',
            'sample_length',
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

        $removeSampleColumnsInPreview = [
            'id'
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

        $sampleSeriesForm = new CreateSampleSeriesForm([
            'modelToCopy' => $modelToCopy
        ]);
        if ($sampleSeriesForm->load(\Yii::$app->getRequest()->getBodyParams())) {
            $sampleSeriesForm->validate();
        }
        $this->content = $this->render(null, [
            'header' => $this->renderDisHeader($headerAttributes, "Create sample series"),
            'modelToCopy' => $modelToCopy,
            'sampleSeriesForm' => $sampleSeriesForm,
            'sampleColumnLabels' => $sampleColumnLabels,
            'removeSampleColumnsInPreview' => array_flip($removeSampleColumnsInPreview)
        ]);
    }



}



/**
 * Class CreateSampleSeriesForm
 * @package app\reports
 *
 * @property boolean canCreateSamples returns true if all samples are valid and there are no invalid samples
 * @property boolean isSamplingArchive returns true is at least one selected split is an archive split
 */
class CreateSampleSeriesForm extends Model
{
    /**
     * Szenario step:
     * - 0: Enter sample parameters (start, interval, end)
     * - 1: Select the section splits to use for sampling
     * - 2: Preview the samples to generate
     * - 3: Show generated samples and provide Link to generate Labels
     * scenario = "STEP" . $step
     */
    public $step = 0;

    public $interval;
    public $numberSamples;
    public $idsToSample = [];

    public $modelToCopy;
    public $coresQuery;
    private $minDepth;
    private $maxDepth;
    private $splitsToSample = [];
    public $validSamples = [];
    public $invalidSamples = [];
    public $forceSamplingArchive = false;
    public $createdSampleIds = [];
    protected $formStep = 0;


    public function init()
    {
        if (empty($this->modelToCopy)) {
            throw new InvalidConfigException('modelToCopy is missing for SampleSeriesForm');
        }
        $this->coresQuery = $this->modelToCopy->hole->getCoreCores();
        $cores = $this->coresQuery->orderBy(['core' => SORT_ASC])->all();
        $this->minDepth = $cores[0]->core_top_depth;
        $this->maxDepth = $cores[count($cores) - 1]->core_bottom_depth;
        parent::init();
    }

    public function load($data, $formName = null) {
        $loaded = false;
        if (sizeof($data)) {
            $this->step = isset($data["CreateSampleSeriesForm"]["step"]) ? intval($data["CreateSampleSeriesForm"]["step"]) : 0;
            $this->setScenario("STEP" . $this->step);
            $loaded = parent::load($data, $formName);
            $this->step = intval($this->step);
        }
        return $loaded;
    }

    function getMinDepth () {
        return $this->minDepth;
    }

    function getMaxDepth () {
        return $this->maxDepth;
    }

    function getCreatedSamples() {
        $createdSamples = [];
        foreach ($this->validSamples as $sample) {
            if ($sample->id > 0 && in_array($sample->id, $this->createdSampleIds)) {
                $createdSamples[] = $sample;
            }
        }
        return $createdSamples;
    }

    public function rules()
    {
        return [
            [['interval', 'numberSamples'], 'required', 'on' => ['STEP0', 'STEP1', 'STEP2', 'STEP3']],
            [['interval', 'numberSamples', 'step'], 'number'],
            [['interval'], 'double', 'min' => $this->modelToCopy->sample_length + 1],
            ['forceSamplingArchive', 'boolean', 'on' => ['STEP2', 'STEP3']],
            ['forceSamplingArchive', 'validateForceSamplingArchive'],
            ['idsToSample', 'required', 'on' => ['STEP2', 'STEP3']],
            ['idsToSample', function ($attribute, $params, $validator) {
                $selectedSplits = $this->$attribute;
                foreach ($selectedSplits as $section => $splitId) {
                    if (empty(intval($splitId))) {
                        $this->addError('idsToSample[' . $section . ']', "Please select a split for section $section.");
                    }
                }
            }, 'on' => ['STEP2', 'STEP3']]
        ];
    }

    public function validateForceSamplingArchive($attribute) {
        if ($this->getIsSamplingArchive() && !$this->forceSamplingArchive) {
            $this->addError($attribute, "Please confirm to use archive splits for sampling.");
        }
    }

    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'idsToSample' => 'Split to sample',
            'numberSamples' => 'Number of samples to create',
            'interval' => 'Interval (cm) (distance between top depths of samples)',
            'forceSamplingArchive' => 'Are you sure you want to sample archive splits?',
        ]);
    }



    public function beforeValidate() {
        for ($step = 1; $step < $this->step; $step++) {
            switch ($step) {
                case 1:
                    $this->initSplitsToSample();
                    break;
                case 2:
                    $this->createSamples();
                    break;
            }
        }
        return parent::beforeValidate();
    }

    public function afterValidate() {
        if (!$this->hasErrors()) {
            switch ($this->step) {
                case 1:
                    $this->initSplitsToSample();
                    break;
                case 2:
                    $this->createSamples();
                    break;
                case 3:
                    $this->saveSamples();
                    break;
            }
            if (!$this->hasErrors()) $this->step++;
        }

        if ($this->step == 3 && sizeof($this->validSamples) < $this->numberSamples) {
            $this->addError("", "The requested number of samples cannot be created.");
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
                foreach ($this->idsToSample as $section => $splitId) {
                    $hiddenFields[] = ['idsToSample['. $section . ']',  $splitId];
                }
                $hiddenFields[] = ['forceSamplingArchive', $this->forceSamplingArchive];
            case 1:
                $hiddenFields[] = ['interval', null];
                $hiddenFields[] = ['numberSamples', null];
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

    protected function initSplitsToSample () {
        $intervalTopDepth = $this->modelToCopy->section->top_depth + ($this->modelToCopy->top / 100.0);
        $intervalBottomDepth = $intervalTopDepth + $this->numberSamples * ($this->interval / 100.0);

        $coresQuery = \app\models\CoreCore::find();
        $coresQuery->andWhere(['hole_id' => $this->modelToCopy->core->hole_id]);
        $coresQuery->orderBy(['combined_id' => SORT_ASC]);
        $coreIDs = [];

        foreach ($coresQuery->batch() as $cores) {
            foreach ($cores as $core) {
                if ($core->core_bottom_depth <= $intervalTopDepth) continue;
                if ($core->core_top_depth >= $intervalBottomDepth) break 2;
                $coreIDs[] = $core->id;
            }
        }

        $sectionsQuery = \app\models\CoreSection::find();
        $sectionsQuery->andWhere(['IN', 'core_id', $coreIDs]);
        $sectionsQuery->orderBy(['core_section.combined_id' => SORT_ASC]);
        $sectionIDs = [];

        foreach ($sectionsQuery->batch() as $sections) {
            foreach ($sections as $section) {
                if ($section->bottom_depth <= $intervalTopDepth) continue;
                if ($section->top_depth >= $intervalBottomDepth) break 2;
                $sectionIDs[] = $section->id;
            }
        }

        $query = CurationSectionSplit::find();
        $query->innerJoinWith('section');
        $query->andWhere(['IN', 'section_id', $sectionIDs]);
        $query->andWhere(["=", "still_exists", 1]);
        $query->orderBy(['core_section.combined_id' => SORT_ASC, CurationSectionSplit::tableName() . '.type' => SORT_ASC]);
        $models = $query->all();
        $models = ArrayHelper::toArray($models, [
            'app\models\CurationSectionSplit' => [
                'id',
                'type',
                'sampleable' => function ($model) {
                    return $model->sampleable;
                },
                'topDepth' => function ($model) {
                    return $model->section->top_depth;
                },
                'bottomDepth' => function ($model) {
                    return $model->section->bottom_depth;
                },
                'section' => function ($model) {
                    return $model->section->combined_id;
                }
            ]
        ]);

        usort($models, function($a, $b) {
            return $a["topDepth"] > $b["topDepth"] ? 1 : -1;
        });

        // $models = ArrayHelper::index($models, null, ['section']);
        // set default idsToSample
        if (count($this->idsToSample) == 0) {
            foreach (ArrayHelper::index($models, null, ['section']) as $section => $splits) {
                $this->idsToSample[$section] = null;
                foreach ($splits as $split) {
                    if (preg_match('/^W[1-9]*$/', $split['type'])) {
                        $this->idsToSample[$section] = $split['id'];
                    }
                }
            }
        }
        $this->splitsToSample = $models;
    }

    protected function createSamples () {
        $attributesToCopy = $this->modelToCopy->attributes;
        unset($attributesToCopy['id']);
        unset($attributesToCopy['top']);
        unset($attributesToCopy['bottom']);
        unset($attributesToCopy['section_split_id']);
        unset($attributesToCopy['igsn']);

        $splitsToSample = array_filter($this->splitsToSample, function ($v, $k) {
            return in_array($v['id'], array_values($this->idsToSample));
        }, ARRAY_FILTER_USE_BOTH);

        $intervalTopDepth = $this->modelToCopy->section->top_depth + ($this->modelToCopy->top / 100.0);
        $intervalBottomDepth = $intervalTopDepth + $this->numberSamples * ($this->interval / 100.0);
        $sampleDepth = $intervalTopDepth + ($this->interval / 100.0);

        foreach ($splitsToSample as $split) {
            $splitTopDepth = $split['topDepth'];
            $splitBottomDepth = $split['bottomDepth'];

            while ($sampleDepth >= $splitTopDepth && $sampleDepth < $splitBottomDepth) {
                $attributesToCopy['section_split_id'] = $split['id'];
                $newSample = new CurationSample();
                $newSample->section_split_id = $split['id'];
                $newSample->trigger(\app\models\core\Base::EVENT_DEFAULTS);
                $newSample->attributes = $attributesToCopy;
                $newSample->top = round(($sampleDepth - $splitTopDepth) * 100);
                $bottom = round($newSample->top + $this->modelToCopy->sample_length);

                if ($newSample->validate()) {
                    $existingSample = CurationSample::find()
                        ->andWhere(['section_split_id' => $split['id']])
                        ->andWhere('top + sample_length > ' . $newSample->top)
                        ->andWhere([
                                '<=',
                                'top',
                                $bottom
                            ])
                        ->one();
                    if ($existingSample) {
                        $newSample->addError("", "This samples collides with an existing one: (" . $existingSample->sample_combined_id . ", top: " . $existingSample->top . ", bottom: " . $existingSample->bottom . ")");
                        $this->invalidSamples[] = $newSample;
                    }
                    else {
                        if (round($splitTopDepth + ($bottom / 100.0), 2) > round($splitBottomDepth, 2)) {
                            $bottom = round(($splitBottomDepth - $splitTopDepth) * 100.0);
                            $newSample->sample_length = round($bottom - $newSample->top);
                        }
                        $this->validSamples[] = $newSample;
                    }
                } else {
                    $this->invalidSamples[] = $newSample;
                }
                $sampleDepth += ($this->interval / 100.0);

                if (sizeof($this->validSamples) >= $this->numberSamples) return;
            }
        }
    }

    protected function saveSamples() {
        if (!$this->isSamplingArchive || ($this->isSamplingArchive && $this->forceSamplingArchive)) {
            $this->createdSampleIds = [];
            $transaction = \Yii::$app->db->beginTransaction();
            try {
                foreach ($this->validSamples as $sample) {
                    if ($sample->save()) {
                        $this->createdSampleIds[] = $sample->id;
                    }
                }
                $transaction->commit();
            } catch (\Exception $e) {
                $this->addError("", "Could not create samples: " . $e->getMessage());
                $transaction->rollBack();
            }
        }
    }

    public function getCanCreateSamples () {
        return count($this->validSamples) && count($this->invalidSamples) === 0;
    }

    public function getIsSamplingArchive () {
        $selectedSplitsIds = array_values($this->idsToSample);
        $selectedSplits = array_filter($this->splitsToSample, function ($v) use ($selectedSplitsIds) { return in_array($v['id'], $selectedSplitsIds); });
        $selectedArchiveSplits = array_filter($selectedSplits, function ($v) { return preg_match('/^A[1-9]*$/', $v['type']); });
        return count($selectedArchiveSplits) > 0;
    }

    public function getIsFinished() {
        $isFinished = $this->step >= 4 && !$this->hasErrors();
        return $isFinished;
    }
}
