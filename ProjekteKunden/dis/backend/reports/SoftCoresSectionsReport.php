<?php
/**
 * Created by PhpStorm.
 * User: reckert
 * Date: 21.01.2019
 * Time: 14:59
 */

namespace app\reports;

use app\reports\interfaces\IHtmlReport;

/**
 * Class CoreSectionsReport
 *
 * Example for a more complex report of Cores with their sections.
 * It creates HTML pages with a list of the filtered cores together with their sections.
 *
 * @package app\reports
 */
class SoftCoresSectionsReport extends Base implements IHtmlReport
{
    /**
     * {@inheritdoc}
     */
    const TITLE = 'Soft Cores with Sections';

    /**
     * {@inheritdoc}
     * This reports can only be used for CoreCore forms.
     */
    const MODEL = 'ProjectExpedition';

    /**
     * {@inheritdoc}
     * This reports can be used for single or multiple records.
     */
    const SINGLE_RECORD = null;


    function getJs()
    {
        return '';
    }

    function getCss()
    {
        $cssFile = \Yii::getAlias("@webroot/css/report.css");
        $stylesheet = file_get_contents($cssFile);
        return $stylesheet . <<<'EOB'
    header.reports.site:not(:first-child) {
        page-break-before: always;
    }
    div.report table.report + header.reports.hole {
        padding-top: 1em;
    }
    header.extra div.report-col:nth-last-child(1),
    header.extra div.report-col:nth-last-child(2),
    header.extra div.report-col:nth-last-child(3) {
        width: 15%;
    }
    header.extra div.report-col:nth-last-child(4),
    header.extra div.report-col:nth-last-child(5) {
        width: 10%;
    }
    header.extra.site div.report-col:nth-child(1) {
        flex-grow: 100;
    }
    header.extra.hole div.report-col:nth-child(1),
    header.extra.hole div.report-col:nth-child(2) {
        flex-grow: 42;
    }
   table.report tr.sub.section td {
        font-weight: bold;
   }       
   table.report tr.core td, table.report tr.sub.split td {
        background:rgba(220,230,255,0.8);
   }     

   table.report tr.sub.section td {
        background:rgba(255,255,255,0.8);
   }     
EOB;
    }


    /**
     * {@inheritdoc}
     */
    function validateReport($options) {
        $valid = parent::validateReport($options);
        $valid = $this->validateColumns("ProjectExpedition", ['expedition']) && $valid;
        $valid = $this->validateColumns("ProjectSite", ['expedition_id']) && $valid;
        $valid = $this->validateColumns("ProjectHole", ['site_id']) && $valid;
        $valid = $this->validateColumns("CoreCore", ['hole_id', 'core_bottom_depth', 'core_recovery', 'drilled_length', 'core', 'core_type', 'core_top_depth', 'core_recovery', 'curator', 'comment']) && $valid;
        $valid = $this->validateColumns("CoreSection", ['core_id', 'combined_id', 'section_length', 'curated_length', 'top_depth', 'bottom_depth', 'curator']) && $valid;
        $valid = $this->validateColumns("CurationSectionSplit", ['section_id', 'still_exists', 'combined_id', 'igsn', 'curator', 'comment']) && $valid;
        return $valid;
    }


    /**
     * {@inheritdoc}
     */
    public function generate($options = []) {
        $dataProvider = $this->getDataProvider($options);
        $subQuery = $dataProvider->query->select("id");
        $query = \app\models\CoreCore::find();
        $query->innerJoin("project_hole", "core_core.hole_id = project_hole.id");
        $query->innerJoin("project_site", "project_hole.site_id = project_site.id");
        $query->andWhere(['IN', "project_site.expedition_id", $subQuery]);
        $dataProvider = $this->getDataProvider($query);
        $cSQL = $query->createCommand()->getRawSql();

        $this->content = $this->_generate($dataProvider);
    }

    /**
     * Generates the report for all records in the dataProvider
     * @param \yii\data\ActiveDataProvider $dataProvider
     * @return string Rendered report
     */
    protected function _generate(\yii\data\ActiveDataProvider $dataProvider) {
        $dataProvider->pagination = false;

        $data = [];
        $holes = [];
        $sites = [];
        $expeditions = [];
        foreach ($dataProvider->getModels() as $core) {
            if (isset($holes[$core->hole_id]))
                $hole = $holes[$core->hole_id];
            else
                $hole = $holes[$core->hole_id] = $core->hole;

            if (isset($sites[$hole->site_id]))
                $site = $sites[$hole->site_id];
            else
                $site = $sites[$hole->site_id] = $hole->site;

            if (isset($expeditions[$site->expedition_id]))
                $expedition = $expeditions[$site->expedition_id];
            else
                $expedition = $expeditions[$site->expedition_id] = $site->expedition;


            if (!isset($data[$expedition->id])) {
                $data[$expedition->id] = [
                    "expedition" => $expedition,
                    "sites" => []
                ];
            }

            if (!isset($data[$expedition->id]["sites"][$site->id])) {
                $data[$expedition->id]["sites"][$site->id] = [
                    "site" => $site,
                    "holes" => []
                ];
            }

            if (!isset($data[$expedition->id]["sites"][$site->id]["holes"][$hole->id])) {
                $data[$expedition->id]["sites"][$site->id]["holes"][$hole->id] = [
                    "hole" => $hole,
                    "cores" => []
                ];
            }

            $data[$expedition->id]["sites"][$site->id]["holes"][$hole->id]["cores"][] = $core;
        }

        return $this->render(null, [
            'report' => $this,
            'x_expeditions' => $data
        ]);
    }

}
