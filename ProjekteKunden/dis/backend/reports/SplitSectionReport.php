<?php

namespace app\reports;

use app\models\core\DisListItem;
use app\models\CurationSectionSplit;
use app\reports\Base as BaseReport;
use app\reports\interfaces\IHtmlReport;
use yii\base\Exception;
use yii\base\Model;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

/**
 * Class SplitSectionReport
 *
 * @package app\reports
 */
class SplitSectionReport extends BaseReport implements IHtmlReport
{
    /**
     * {@inheritdoc}
     */
    const TITLE = 'Split sections';
    /**
     * {@inheritdoc}
     * This report can be allied for any data model
     */
    const MODEL = '^(CoreCore|CoreSection|CurationSectionSplit)';
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
     * Colums to show in the report
     * @var array Columns
     */
    protected $columns = [];

    /**
     * {@inheritdoc}
     */
    const REPORT_TYPE = 'action';

    public function extendDataProvider (string $modelClass, \yii\data\ActiveDataProvider $dataProvider) {
        $dataProvider->pagination = false;
        if ($modelClass !== 'app\\models\\CurationSectionSplit') {
            $query = \app\models\CurationSectionSplit::find();
            switch (preg_replace("/^.+\\\\/", "", $modelClass)) {
                case "CoreSection":
                    $subQuery = $dataProvider->query->select('core_section.id');
                    $query->andWhere(["IN", "curation_section_split.section_id", $subQuery]);
                    break;
                case "CoreCore":
                    $subQuery = $dataProvider->query->select('core_core.id');
                    $query->innerJoinWith('section');
                    $query->andWhere(["IN", "core_section.core_id", $subQuery]);
                    break;
            }
            $query->orderBy(['section_id' => SORT_ASC, 'curation_section_split.id' => SORT_ASC]);
            return $this->getDataProvider($query);
        }
        return $dataProvider;
    }

    function getJs()
    {
        return '';
    }

    function getCss()
    {
        $cssFile = \Yii::getAlias("@webroot/css/report.css");
        $stylesheet = file_get_contents($cssFile);
        $stylesheet .= <<<'EOD'
            table tbody tr td .form-group {
                margin-bottom: 0;
            }
EOD;
        return $stylesheet;
    }

    /**
     * {@inheritdoc}
     */
    function validateReport($options) {
        $valid = parent::validateReport($options);
        $valid = $this->validateColumns("CoreCore", ['core']) && $valid;
        $valid = $this->validateColumns("CoreSection", ['core_id', 'section']) && $valid;
        $valid = $this->validateColumns("CurationSectionSplit", ['section_id', 'combined_id', 'type', 'percent', 'origin_split_type', 'sampleable', 'still_exists']) && $valid;
        return $valid;
    }

    /**
     * {@inheritdoc}
     */
    public function generate($options = [])
    {
        $modelClass = $this->getModelClass($options);
//        $id = $options["id"];
//        $query = call_user_func([$modelClass, 'find'])->andWhere(['id' => $id]);
        $dataProvider = $this->getDataProvider($options);
        $dataProvider = $this->extendDataProvider($modelClass, $dataProvider);
        $splitForm = new SplitForm();
        $splitResults = [];
        $headerAttributes = [];
        if ($splitForm->load(\Yii::$app->getRequest()->getBodyParams()) && $splitForm->validate()) {
            $models = $dataProvider->getModels();
            foreach ($models as $model) {
                /** @var ActiveRecord $model */
                if ($model->still_exists) {
                    if ($splitForm->idsToSplit[$model->id] == 1) {
                        if ($model->type == $splitForm->firstSplitType || $model->type == $splitForm->secondSplitType) {
                            $splitResults[] = "An error happened while splitting " . $model->id . " (" . $model->combined_id . '). It cannot be splitted into the same split type "' . $model->type . '".';
                        }
                        else {
                            $transaction = \Yii::$app->db->beginTransaction();
                            try {
                                $attributesToCopy = $model->attributes;
                                unset($attributesToCopy['combined_id']);
                                unset($attributesToCopy['type']);
                                unset($attributesToCopy['igsn']);
                                $isSaved = true;
                                /** @var ActiveRecord $firstSplit */
                                $firstSplit = new CurationSectionSplit();
                                $firstSplit->type = $splitForm->firstSplitType; // ------
                                $firstSplit->setAttributes($attributesToCopy);
                                $firstSplit->sampleable = preg_match('/^A[1-9]*$/', $splitForm->firstSplitType) ? 0 : 1;
                                $firstSplit->trigger(\app\models\core\Base::EVENT_DEFAULTS);
                                $firstSplit->percent = $splitForm->firstSplitPercent / 100 * $model->percent;
                                // $firstSplit->origin_split_type = $model->type;
                                $firstSplit->origin_split_id = $model->id;
                                $isSaved = $isSaved && $firstSplit->save();
                                if (!$isSaved) {
                                    \Yii::error('SplitSectionReport: Unable to save first split: ' . print_r($firstSplit->getErrors(), true));
                                    throw new Exception("Unable to save first split: " . $firstSplit->getErrorsHtml());
                                }

                                /** @var ActiveRecord $secondSplit */
                                $secondSplit = new CurationSectionSplit();
                                $secondSplit->type = $splitForm->secondSplitType; // ------
                                $secondSplit->setAttributes($attributesToCopy);
                                $secondSplit->sampleable = preg_match('/^A[1-9]*$/', $splitForm->secondSplitType) ? 0 : 1;
                                $secondSplit->trigger(\app\models\core\Base::EVENT_DEFAULTS);
                                $secondSplit->percent = $splitForm->secondSplitPercent / 100 * $model->percent;
                                // $secondSplit->origin_split_type = $model->type;
                                $secondSplit->origin_split_id = $model->id;
                                $isSaved = $isSaved && $secondSplit->save();
                                if (!$isSaved) {
                                    \Yii::error('SplitSectionReport: Unable to save second split: ' . print_r($secondSplit->getErrors(), true));
                                    throw new Exception("Unable to save second split: " . $secondSplit->getErrorsHtml());
                                }

                                $model->still_exists = 0;
                                $model->sampleable = 0;
                                $isSaved = $isSaved && $model->save();
                                if (!$isSaved) {
                                    \Yii::error('SplitSectionReport: Unable to save original split: ' . print_r($model->getErrors(), true));
                                    throw new Exception("Unable to save original split: " . $model->getErrorsHtml());
                                }
                                $transaction->commit();
                                $splitResults[] = "Split " . $model->id . " (" . $model->combined_id . ") was split successfully";
                            } catch (\Exception $e) {
                                $transaction->rollBack();
                                $splitResults[] = "An error happened while splitting " . $model->id . " (" . $model->combined_id . "). " . $e->getMessage();
                            }
                        }
                    }
                }
            }
            $dataProvider->refresh();
        }
        $models = $dataProvider->getModels();
        if (sizeof($models)) {
            $ancestorValues = $this->getAncestorValues($models[0]);
            unset($ancestorValues['core']);
            unset($ancestorValues['section']);
            $this->setExpedition($models[0]);
            foreach ($ancestorValues as $ancestorValue) $headerAttributes[$ancestorValue[1]] = $ancestorValue[0];
        }
        $models = ArrayHelper::toArray($models, [
            'app\models\CurationSectionSplit' => [
                'id',
                'type',
                'percent',
                'still_exists',
                'section' => function ($model) {
                    return $model->section->section;
                },
                'core' => function ($model) {
                    return $model->section->core->core;
                }
            ]
        ]);
        $models = ArrayHelper::index($models, null, ['core', 'section']);
        $this->content = $this->render(null, [
            'models' => $models,
            'splitForm' => $splitForm,
            'splitResults' => $splitResults,
            'header' => $this->renderDisHeader($headerAttributes, "Split Action")
        ]);
    }

}

class SplitForm extends Model
{
    public $firstSplitType;
    public $firstSplitPercent = 50;
    public $secondSplitType;
    public $secondSplitPercent = 50;
    public $idsToSplit;

    private $splitTypesList;

    public function init()
    {
        parent::init();
        $types = ArrayHelper::map(
            DisListItem::find()->joinWith('list')->where(['list_name' => 'SPLIT_TYPE'])->all(),
            'display',
            'remark'
        );
        foreach ($types as $key => $value) {
            if ($key === 'WR') {
                unset($types[$key]);
            }
        }
        $this->splitTypesList = $types;
    }

    public function rules()
    {
        return [
            [['firstSplitType', 'secondSplitType', 'firstSplitPercent', 'secondSplitPercent'], 'required'],
            [['firstSplitType', 'secondSplitType'], 'in', 'range' => array_keys($this->splitTypesList)],
            [['firstSplitPercent', 'secondSplitPercent'], 'number', 'min' => 1, 'max' => 99],
            [['firstSplitPercent'], function ($attribute, $params, $validator) {
                if ($this->firstSplitPercent + $this->secondSplitPercent != 100) {
                    $this->addError($attribute, 'Percents must add up to 100');
                }
            }],
            ['idsToSplit', function ($attribute, $params, $validator) {
                if (!in_array(1, array_values($this->$attribute))) {
                    $this->addError($attribute, 'No split was selected');
                }
            }],
            ['idsToSplit', 'each', 'rule' => ['integer']]
        ];
    }

    public function getTypesList()
    {
        return $this->splitTypesList;
    }
}
