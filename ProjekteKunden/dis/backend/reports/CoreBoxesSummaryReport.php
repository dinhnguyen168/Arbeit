<?php
/**
 * Created by PhpStorm.
 * User: reckert
 * Date: 21.01.2019
 * Time: 14:59
 * => Problems with headerAttributes on update to new data model, error is probably older as site and hole hasn't changed
 */

namespace app\reports;
use app\reports\Base as BaseReport;
use app\reports\interfaces\IHtmlReport;
use app\models\CurationSectionSplit;
use yii\base\Model;
use yii\base\Exception;

/**
 * Class CoreBoxesSummaryReport
 *
 * @package app\reports
 */
class CoreBoxesSummaryReport extends BaseReport implements IHtmlReport
{
    /**
     * {@inheritdoc}
     */
    const TITLE = 'Core Boxes Summary';
    /**
     * {@inheritdoc}
     * This report can be allied for any data model
     */
    const MODEL = '^ProjectHole|CurationCorebox$';
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
        if ($modelClass == "ProjectHole") {
            $dataProvider->query->orderBy([
                "project_hole.site_id" => SORT_ASC,
                "project_hole.hole" => SORT_ASC
            ]);
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
    html, body { 
        box-shadow: 0;
        height: 100% 
        margin: 0;
        padding: 0;
    }
    
    @page {
        margin: .5cm;
        padding: 0;
    }

    table.report {
        width: 100%;
    }

    table.report tr.even:nth-child(2n+1) td, table.report tr.even:nth-child(2n) td {
        background: rgba(220, 230, 255, 0.8);
    }

    table.report tr.odd:nth-child(2n+1) td, table.report tr.odd:nth-child(2n) td {
        background: rgba(255, 255, 255, 0.8);
    }


    div.report.core-box-report, div.report.core-box-image {
        page-break-after: always;
    }
    
    div.report.core-box-image + header.reports.report { // Trick für Chrome
        page-break-before: always;
    }

    @media screen {
        div.report.core-box-report, div.report.core-box-image {
            padding-bottom: 4em;
        }
    }
    
    div.report.core-box-image {
        height: calc(100vh - 12.5em);
        max-width: 100%;
    }

    div.report.core-box-image img {
        object-fit: contain;
        max-width: 100%;
    }
    
    span.warning {
        color: red;
    }
EOD;
        return $stylesheet;
    }

    /**
     * {@inheritdoc}
     */
    function validateReport($options) {
        $valid = parent::validateReport($options);
        $valid = $this->validateColumns("CoreCore", ['drillers_top_depth', 'hole_id', 'core']) && $valid;
        $valid = $this->validateColumns("CoreSection", ['section']) && $valid;
        $valid = $this->validateColumns("CurationSectionSplit", ['corebox']) && $valid;
        $valid = $this->validateColumns("ArchiveFile", ['upload_date', 'type', 'number', 'mime_type', 'id']) && $valid;
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

        $dataProvider = $this->extendDataProvider($modelClass, $dataProvider);
        $models = $dataProvider->getModels();

        $this->content = $this->render(null, [
            'report' => $this,
            'models' => $models
        ]);
    }

    public function getCoreboxesDataProvider ($holeId) {
        $query = \app\models\CurationCorebox::find();
        $query->andWhere(["curation_corebox.hole_id" => $holeId]);
        $query->orderBy([
            'CONVERT(corebox, UNSIGNED INTEGER)' => SORT_ASC,
            'corebox' => SORT_ASC
        ]);
        $dataProvider = $this->getDataProvider($query);
        $dataProvider->pagination->pageSize = 50;
        return $dataProvider;
    }



}

