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
 * Class UndoSplitSectionReport
 *
 * @package app\reports
 */
class UndoSplitSectionReport extends BaseReport implements IHtmlReport
{
    /**
     * {@inheritdoc}
     */
    const TITLE = 'Undo section splits';
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
     * @var array IDs of Splits that have been sampled
     */
    protected $sampledSplitIds = [];

    /**
     * {@inheritdoc}
     */
    const REPORT_TYPE = 'action';

    public function extendDataProvider (string $modelClass, \yii\data\ActiveDataProvider $dataProvider) {
        $query = $dataProvider->query;

        if ($modelClass !== 'app\\models\\CurationSectionSplit') {
            $subQuery = $query;
            $query = \app\models\CurationSectionSplit::find();
            switch (preg_replace("/^.+\\\\/", "", $modelClass)) {
                case "CoreSection":
                    $subQuery->select('core_section.id');
                    $sql = $subQuery->createCommand()->getRawSql();
                    $query->andWhere(["IN", "curation_section_split.section_id", $subQuery]);
                    $sql = $query->createCommand()->getRawSql();
                    break;
                case "CoreCore":
                    $subQuery->select('core_core.id');
                    $query->innerJoinWith('section');
                    $query->andWhere(["IN", "core_section.core_id", $subQuery]);
                    break;
            }
        }
        $sql = $query->createCommand()->getRawSql();

        $onlyIdsQuery = clone $query;
        $onlyIdsQuery->select ('curation_section_split.id');

        // Save Ids of records in data provider that have samples in array sampledSplitIds
        $sampleQuery = \app\models\CurationSample::find();
        $sampleQuery->andWhere(["IN", "curation_sample.section_split_id", $onlyIdsQuery]);
        $sampleQuery->select ("curation_sample.section_split_id");
        $sampleQuery->distinct();
        $this->sampledSplitIds = $sampleQuery->column();

        $query->orderBy(['curation_section_split.combined_id' => SORT_ASC, 'curation_section_split.id' => SORT_ASC]);
        // $sql = $query->createCommand()->getRawSql();

        $dataProvider = $this->getDataProvider($query);
        $dataProvider->pagination = false;
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
        $valid = $this->validateColumns("CurationSectionSplit", ['section_id', 'combined_id', 'type', 'percent', 'origin_split_type', 'sampleable', 'still_exists', 'storage_id']) && $valid;
        return $valid;
    }

    /**
     * {@inheritdoc}
     */
    public function generate($options = [])
    {
        $modelClass = $this->getModelClass($options);
        $dataProvider = $this->getDataProvider($options);
        $dataProvider = $this->extendDataProvider($modelClass, $dataProvider);

        $splitForm = new UndoSplitForm();
        $splitResults = [];
        $headerAttributes = [];
        if ($splitForm->load(\Yii::$app->getRequest()->getBodyParams()) && $splitForm->validate()) {
            $idsToSplit = [];
            foreach ($splitForm->idsToSplit as $id => $doSplit) {
                if ($doSplit) $idsToSplit[] = $id;
            }

            // Filter selected models
            $actionQuery = clone $dataProvider->query;
            $actionQuery->andWhere (["IN", "curation_section_split.id", $idsToSplit]);

            foreach ($actionQuery->all() as $model) {
                $transaction = \Yii::$app->db->beginTransaction();
                try {
                    // Copy storage id from sub split
                    $storage_id = null;
                    foreach ($model->curationSectionSplits as $subSplit) {
                        if ($subSplit->storage_id && ($storage_id == null || $subSplit->sampleable)) {
                            $storage_id = $subSplit->storage_id;
                        }
                        // delete sub splits
                        if (!$subSplit->delete()) {
                            throw new Exception("Unable to delete sub split " . $subSplit->id . " (" . $subSplit->combined_id . ")");
                        }
                    }

                    // Change origin model
                    $model->still_exists = 1;
                    $model->storage_id = $storage_id;
                    // Set sampleable depending on type
                    $model->sampleable = preg_match('/^WR|A[1-9]*$/', $model->type) ? 0 : 1;
                    if (!$model->save()) {
                        throw new Exception("Unable to save split " . $model->id . " (" . $model->combined_id . "): ");
                    }

                    $transaction->commit();
                    $splitResults[] = "Split of " . $model->id . " (" . $model->combined_id . ") was undone successfully";

                } catch (\Exception $e) {
                    $transaction->rollBack();
                    $splitResults[] = "An error happened while undoing split of " . $model->id . " (" . $model->combined_id . "). " . $e->getMessage();
                }
            }
            $dataProvider->refresh();
        }

        $models = $dataProvider->getModels();
        // Build values for report header
        if (sizeof($models)) {
            $ancestorValues = $this->getAncestorValues($models[0]);
            unset($ancestorValues['core']);
            unset($ancestorValues['section']);
            $this->setExpedition($models[0]);
            foreach ($ancestorValues as $ancestorValue) $headerAttributes[$ancestorValue[1]] = $ancestorValue[0];
        }

        // IDs of all models
        $allIds = [];
        foreach ($models as $model) $allIds[] = $model->id;

        // IDs of all origin_splits, but only if in $allIds and has not been sampled
        $allOriginIds = [];
        foreach ($models as $key => $model) {
            if ($model->still_exists && $model->origin_split_id
                && in_array($model->origin_split_id, $allIds)
                && !in_array($model->origin_split_id, $this->sampledSplitIds)) {
                $allOriginIds[] = $model->origin_split_id;
            }
        }
        $allOriginIds = array_unique($allOriginIds);

        // Remove Ids from $allOriginIDs where a child split does not exist anymore or has been sampled
        foreach ($models as $model) {
            if ($model->origin_split_id) {
                $key = array_search($model->origin_split_id, $allOriginIds);
                if ($key !== false && (!$model->still_exists || in_array($model->id, $this->sampledSplitIds))) {
                    unset($allOriginIds[$key]);
                }
            }
        }
        $allOriginIds = array_values($allOriginIds);

        // Convert to array
        $sampledSplitIDs = $this->sampledSplitIds;
        $models = ArrayHelper::toArray($models, [
            'app\models\CurationSectionSplit' => [
                'id',
                'type',
                'percent',
                'still_exists',
                'origin_split_id',
                'section' => function ($model) {
                    return $model->section->section;
                },
                'core' => function ($model) {
                    return $model->section->core->core;
                },
                'hasSamples' => function ($model) use (& $sampledSplitIDs) {
                    return in_array($model->id, $sampledSplitIDs);
                },
                'canSelect' => function ($model) use (& $allOriginIds) {
                    return in_array($model->id, $allOriginIds);
                }
            ]
        ]);

        // Reindex by core, section
        $models = ArrayHelper::index($models, null, ['core', 'section']);

        // Order the splits under sections so that the origin_split is on top
        foreach ($models as $core => $sectionModels) {
            foreach ($sectionModels as $section => $splitModels) {
                usort($splitModels, function($a, $b) use ($allOriginIds) {
                   if ($a["origin_split_id"] == $b["id"])
                       return 10;
                   else if ($a["id"] == $b["origin_split_id"])
                       return -10;
                   else {
                       return ($a["id"] < $b["id"] ? -1 : 1);
                   }
                });
                $models[$core][$section] = $splitModels;
            }
        }

        $this->content = $this->render(null, [
            'models' => $models,
            'splitForm' => $splitForm,
            'splitResults' => $splitResults,
            'header' => $this->renderDisHeader($headerAttributes, "Undo Split Action")
        ]);
    }

}

class UndoSplitForm extends Model
{
    public $idsToSplit;

    public function rules()
    {
        return [
            ['idsToSplit', function ($attribute, $params, $validator) {
                if (!in_array(1, array_values($this->$attribute))) {
                    $this->addError($attribute, 'No split was selected');
                }
            }],
            ['idsToSplit', 'each', 'rule' => ['integer']]
        ];
    }

}
