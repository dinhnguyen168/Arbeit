<?php

namespace app\reports;
use app\reports\Base as BaseReport;
use app\reports\forms\SampleSeriesForm;
use app\reports\interfaces\IHtmlReport;
use app\models\CurationSectionSplit;
use yii\base\Model;
use yii\base\Exception;

/**
 * Class CreateCratesReport
 *
 * @package app\reports
 */
class CreateCratesReport extends BaseReport implements IHtmlReport
{
    /**
     * {@inheritdoc}
     */
    const TITLE = 'Fill crates';
    /**
     * {@inheritdoc}
     * This report can be allied for any data model
     */
    const MODEL = '^(ProjectExpedition|ProjectSite|ProjectHole)$';
    /**
     * {@inheritdoc}
     * This report is for a single record
     */
    const SINGLE_RECORD = null;

    /**
     * Name of the model to display in the header of the report
     * @var string
     */
    protected $modelShortName = "";

    /**
     * {@inheritdoc}
     */
    const REPORT_TYPE = 'action';

    public function extendDataProvider(string $modelClass, \yii\data\ActiveDataProvider $dataProvider)
    {
        $query = $dataProvider->query;

        if ($modelClass !== 'app\\models\\CurationSectionSplit') {
            $subQuery = $query;
            $subQuery->select($modelClass::tableName() . '.id');

            $query = \app\models\CurationSectionSplit::find();
            $query->innerJoinWith("section");
            $query->innerJoin("core_core", "core_section.core_id = core_core.id");
            switch (preg_replace("/^.+\\\\/", "", $modelClass)) {
                case "ProjectHole":
                    $query->andWhere(["IN", "core_core.hole_id", $subQuery]);
                    break;
                case "ProjectSite":
                    $query->innerJoin("project_hole", "core_core.hole_id = project_hole.id");
                    $query->andWhere(["IN", "project_hole.site_id", $subQuery]);
                    break;
                case "ProjectExpedition":
                    $query->innerJoin("project_hole", "core_core.hole_id = project_hole.id");
                    $query->innerJoin("project_site", "project_hole.site_id = project_site.id");
                    $query->andWhere(["IN", "project_site.expedition_id", $subQuery]);
                    break;
            }
        }
        $dataProvider = $this->getDataProvider($query);
        $dataProvider->pagination = false;
        return $dataProvider;
    }

    function getJs()
    {
        return <<<'EOD'
            function updateCrateValues(checkbox) {
                var deltaSplits = (checkbox.checked ? 1 : -1);
                var deltaWeight = parseInt(checkbox.dataset.weight) * deltaSplits;
                var maxCrateWeight = parseInt(document.querySelector('input[type=hidden]#createcratesform-cratemaxweight').value);
                console.log(document.querySelector('input[type=hidden]#createcratesform-cratemaxweight'));
                
                var td = document.querySelector('tr.selected td[data-splits]');
                if (td) {
                    td.dataset.splits = parseInt(td.dataset.splits) + deltaSplits;
                    td.innerHTML = td.dataset.splits.toString();
                }
                
                td = document.querySelector('tr.selected td[data-weight]');
                if (td) {
                    td.dataset.weight = parseInt(td.dataset.weight) + deltaWeight;
                    td.innerHTML = td.dataset.weight.toString();
                    if (td.dataset.weight > maxCrateWeight)
                        td.classList.add('overweight');
                    else
                        td.classList.remove('overweight');
                }
                console.log('updateCrateValues()', td, checkbox);
                
            }
EOD;

    }

    function getCss()
    {
        $cssFile = \Yii::getAlias("@webroot/css/report.css");
        $stylesheet = file_get_contents($cssFile);
        $stylesheet .= <<<'EOD'
            .table {
                margin-bottom: 0;
            }
            
            .table > thead > tr > th, 
            .table > tbody > tr > td {
                padding-bottom: 0;
            }

            tr.selected {
                background-color: yellow;
            }
            td.overweight {
                background-color: red;
            }
            
EOD;
        return $stylesheet;
    }

    /**
     * {@inheritdoc}
     */
    function validateReport($options)
    {
        $valid = parent::validateReport($options);
        $valid = $this->validateColumns("CurationSectionSplit", ['section_id', 'weight', 'crate_name']) && $valid;
        return $valid;
    }

    /**
     * {@inheritdoc}
     */
    /**
     * {@inheritdoc}
     */
    public function generate($options = [])
    {
        $modelClass = $this->getModelClass($options);
        $dataProvider = $this->getDataProvider($options);
        $models = $dataProvider->getModels();
        $headerAttributes = [];
        if (sizeof($models) > 0) {
            $ancestorValues = $this->getAncestorValues($models[0]);
            $this->setExpedition($models[0]);
            if (sizeof($models) == 1) {
                $nameAttribute = constant(get_class($models[0]) . "::NAME_ATTRIBUTE");
                $ancestorValues[$nameAttribute] = [$models[0]->{$nameAttribute}, $models[0]->getAttributeLabel($nameAttribute)];
            }
            foreach ($ancestorValues as $ancestorValue) $headerAttributes[$ancestorValue[1]] = $ancestorValue[0];
        }

        $dataProvider = $this->extendDataProvider($modelClass, $dataProvider);
        $cratesForm = new CreateCratesForm([
            'baseQuery' => $dataProvider->query
        ]);

        $overviewReportUrl = str_replace("/report/CreateCrates", "/report/Crates", $_SERVER['REQUEST_URI']);

        if ($cratesForm->load(\Yii::$app->getRequest()->getBodyParams())) {
            $cratesForm->validate();
        }
        $this->content = $this->render(null, [
            'header' => $this->renderDisHeader($headerAttributes, "Fill crates"),
            'cratesForm' => $cratesForm,
            'overviewReportUrl' => $overviewReportUrl
        ]);
    }

}


/**
 * Class CreateCratesForm
 * @package app\reports
 *
 * @property boolean canCreateSamples returns true if all samples are valid and there are no invalid samples
 * @property boolean isSamplingArchive returns true is at least one selected split is an archive split
 */
class CreateCratesForm extends Model
{
    const CONFIG_CRATE_MAX_WEIGHT = "crateMaxWeight";

    /**
     * Szenario step:
     * - 0: Select / enter crate
     * - 1: Select the section splits to assign
     * - 2: Preview the section splits to assign and resulting crate
     * - 3: Show assigned section splits
     * scenario = "STEP" . $step
     */
    public $step = 0;

    public $baseQuery = null;

    public $crateMaxWeight = 0;
    public $crateName = "";
    public $newCrateName;
    protected $createNewCrate = false;
    public $selectedSplitIds = [];

    public $existingCrates = [];
    public $selectedCrate;
    public $unassignedSplits = [];
    public $selectedSplits = [];
    public $assignedSplits = [];
    protected $formStep = 0;



    public function init()
    {
        if (empty($this->baseQuery)) {
            throw new InvalidConfigException('modelToCopy is missing for SampleSeriesForm');
        }
        parent::init();
        $this->fetchCrateMaxWeight();
        $this->fetchExistingCrates();
        $this->fetchUnassignedSplits();
    }

    protected function fetchExistingCrates() {
        $this->existingCrates = [];
        $query = clone $this->baseQuery;
        $query->andWhere(['>', 'crate_name', '']);
        $query->groupBy(['crate_name']);
        $query->select(['crate_name AS name', 'COUNT(crate_name) AS splits', 'SUM(curation_section_split.weight) AS weight']);
        $command = $query->createCommand();
        $this->existingCrates = $command->queryAll();
    }

    protected function fetchUnassignedSplits() {
        $this->unassignedSplits = [];
        $query = clone $this->baseQuery;
        $query->andWhere(['still_exists' => 1]);
        $query->andWhere(['OR', ['IS', 'crate_name', null], ['crate_name' => '']]);
        $query->orderBy(['combined_id' => SORT_ASC]);
        $command = $query->createCommand();
        $sql = $command->getRawSql();
        $this->unassignedSplits = $query->all();
    }

    protected function fetchCrateMaxWeight() {
        if (isset(\Yii::$app->config[static::CONFIG_CRATE_MAX_WEIGHT]))
            $this->crateMaxWeight = \Yii::$app->config[static::CONFIG_CRATE_MAX_WEIGHT];
        else {
            $this->crateMaxWeight = 500;
            $this->updateCrateMaxWeight();
        }
    }

    protected function updateCrateMaxWeight() {
        if ($this->crateMaxWeight > 0 &&
            (!isset(\Yii::$app->config[static::CONFIG_CRATE_MAX_WEIGHT]) ||
             $this->crateMaxWeight != \Yii::$app->config[static::CONFIG_CRATE_MAX_WEIGHT])) {
            \Yii::$app->config[static::CONFIG_CRATE_MAX_WEIGHT] = $this->crateMaxWeight;
            \Yii::$app->config->save();
        }
    }

    protected function updateSelectedCrate() {
        $this->selectedCrate = ["name" => $this->getCrateName(), "splits" => 0, "weight" => 0];
        if (!$this->createNewCrate) {
            foreach ($this->existingCrates as $crate) {
                if ($crate["name"] == $this->crateName) {
                    $this->selectedCrate = $crate;
                    break;
                }
            }
        }
    }

    protected function updateSelectedSplits() {
        $this->selectedSplits = [];
        foreach ($this->unassignedSplits as $split) {
            if (isset ($this->selectedSplitIds[$split->id]) && $this->selectedSplitIds[$split->id] > 0) {
                $this->selectedSplits[] = $split;
            }
        }

        foreach ($this->selectedSplits as $split) {
            $this->selectedCrate["splits"]++;
            $this->selectedCrate["weight"]+= $split->weight;
        }

        if ($this->selectedCrate["weight"] > $this->crateMaxWeight) {
            $this->addError("", "Overweight! Please select less section splits");
        }
    }


    public function load($data, $formName = null) {
        $loaded = false;
        if (sizeof($data)) {
            $this->step = isset($data["CreateCratesForm"]["step"]) ? intval($data["CreateCratesForm"]["step"]) : 0;
            $this->setScenario("STEP" . $this->step);
            $loaded = parent::load($data, $formName);
            $this->step = intval($this->step);
        }
        return $loaded;
    }


    function getCrateName () {
        return ($this->createNewCrate ? $this->newCrateName : $this->crateName);
    }

    public function rules()
    {
        return [
            ['crateMaxWeight', 'required', 'on' => ['STEP0', 'STEP1', 'STEP2', 'STEP3']],
            [['crateName', 'newCrateName'], 'string'],
            ['crateName', 'validateCrateName', 'skipOnEmpty' => false],
            ['selectedSplitIds', 'required', 'on' => ['STEP2', 'STEP3']]
        ];
    }

    public function validateCrateName($attribute) {
        $this->createNewCrate = false;
        if ($this->crateName == '')
            $this->addError('crateName', 'Please select a crate');
        else if ($this->crateName == '!!NEW!!') {
            if (empty($this->newCrateName))
                $this->addError('newCrateName', 'Please enter a name for the new crate');
            else {
                $this->createNewCrate = true;
                // TODO: Check for existing crate with newCrateName
                // Search for crate in same expedition or where?
                /*
                $query = clone $this->baseQuery;
                $query->andWhere(['=', 'crate_name', $this->newCrateName]);
                $count = $query->count();
                if ($count > 0) {
                    $split = $query->one();
                    $this->addError('newCrateName', 'This crate has already been used for ')
                }
                */
            }
        }
    }

    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'idsToSample' => 'Split to sample',
            'crateMaxWeight' => 'Maximum weight for a crate (kg)',
            'interval' => 'Interval (cm) (distance between top depths of samples)',
            'forceSamplingArchive' => 'Are you sure you want to sample archive splits?',
        ]);
    }



    public function beforeValidate() {
        for ($step = 1; $step < $this->step; $step++) {
            switch ($step) {
                case 1:
                    break;
                case 2:
                    break;
            }
        }
        return parent::beforeValidate();
    }

    public function afterValidate() {
        if (!$this->hasErrors()) {
            $this->updateCrateMaxWeight();
            for ($step = 1; $step <= $this->step; $step++) {
                switch ($step) {
                    case 1:
                        $this->updateSelectedCrate();
                        break;
                    case 2:
                        $this->updateSelectedSplits();
                        break;
                    case 3:
                        $this->assignSplits();
                        break;
                }
            }
            if (!$this->hasErrors()) $this->step++;
        }
        return parent::afterValidate();
    }

    public function startForm($stepNo) {
        $this->formStep = $stepNo;
        $cratesForm = \yii\widgets\ActiveForm::begin([
            'id' => 'create-crates-form' . $stepNo,
            'action' => '#formTarget' . $stepNo
        ]);
        $hiddenFields = [['step', ($stepNo + 1)]];
        switch ($stepNo) {
            case 3:
            case 2:
                foreach ($this->selectedSplits as $split) {
                    $hiddenFields[] = ['selectedSplitIds['. $split->id . ']',  1];
                }
            case 1:
                $hiddenFields[] = ['crateName', null];
                $hiddenFields[] = ['newCrateName', null];
                $hiddenFields[] = ['crateMaxWeight', null];
                break;
        }
        $html = '';
        foreach ($hiddenFields as $hiddenField) {
            $name = $hiddenField[0];
            $value = $hiddenField[1];
            $html .= $cratesForm->field($this, $name, ['template' => '{input}', 'options' => ['tag' => false]])->hiddenInput($value ? ['value' => $value] : [])->label(false);
        }
        echo $html;
        return $cratesForm;
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


    protected function assignSplits() {
        $this->assignedSplits = [];
        foreach ($this->selectedSplits as $split) {
            $split->crate_name = $this->selectedCrate["name"];
            $split->save();
            $this->assignedSplits[] = $split;
        }
    }

    public function getIsFinished() {
        $isFinished = $this->step >= 4 && !$this->hasErrors();
        return $isFinished;
    }
}
