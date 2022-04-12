<?php

namespace app\reports;
use app\reports\Base as BaseReport;
use app\reports\forms\SampleSeriesForm;
use app\reports\interfaces\IHtmlReport;
use app\models\CurationSectionSplit;
use yii\base\Model;
use yii\base\Exception;

/**
 * Class CratesReport
 *
 * @package app\reports
 */
class CratesReport extends BaseReport implements IHtmlReport
{
    /**
     * {@inheritdoc}
     */
    const TITLE = 'Crates overview';
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
    const REPORT_TYPE = 'report';

    public function extendDataProvider (string $modelClass, \yii\data\ActiveDataProvider $dataProvider) {
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
EOD;
        return $stylesheet;
    }

    /**
     * {@inheritdoc}
     */
    function validateReport($options) {
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

        $query = $dataProvider->query;
        $query->andWhere(['>', 'crate_name', '']);
        $query->orderBy(['crate_name' => SORT_ASC]);
        $query->select(['crate_name AS name', 'curation_section_split.combined_id AS split', 'weight']);
        $command = $query->createCommand();
        $crates = $command->queryAll();

        $crateName = "";
        $countSplits = 0;
        $sumWeight = 0;
        for ($i=sizeof($crates)-1; $i>=0; $i--) {
            $crate = & $crates[$i];
            if ($crate["name"] != $crateName) {
                if ($crateName != "") {
                    array_splice($crates, $i+1, 0, [["name" => $crateName, "split" => $countSplits . " splits:", "weight" => "Sum: " . $sumWeight]]);
                }
                $crateName = $crate["name"];
                $countSplits = 0;
                $sumWeight = 0;
            }
            $countSplits ++;
            $sumWeight += $crate["weight"];
            $crate["name"] = "";
        }
        array_splice($crates, 0, 0, [["name" => $crateName, "split" => $countSplits . " splits:", "weight" => "Sum: " . $sumWeight]]);

        $this->content = $this->render(null, [
            'header' => $this->renderDisHeader($headerAttributes, "Crates overview"),
            'crates' => $crates
        ]);
    }

}

