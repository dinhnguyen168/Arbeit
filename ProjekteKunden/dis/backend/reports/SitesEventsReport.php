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
class SitesEventsReport extends Base implements IHtmlReport
{
    /**
     * {@inheritdoc}
     */
    const TITLE = 'Sites and Events (=Holes)';

    /**
     * {@inheritdoc}
     * This reports can only be used for ProjectSite and ProjectHole forms.
     */
    const MODEL = '^(ProjectExpedition|ProjectSite)';

    /**
     * {@inheritdoc}
     * This reports can be used for single or multiple records.
     */
    const SINGLE_RECORD = null;



    function getCss()
    {
        $cssFile = \Yii::getAlias("@webroot/css/report.css");
        $stylesheet = file_get_contents($cssFile);
        return $stylesheet . <<<'EOB'
    table.site, table.events {
        width: 100%;
    }
    
    table.site {
        margin-bottom: 1em;
    }
    
    table.events {
        margin-bottom: 3em;
    }
    
    table.site.td, table.site.th, table.events.td, table.events.th {
        border-collapse: collapse;
    }
    
    table.report tr td.blank {
        background: none;
        border: none;
    }
    div.page-break {
        page-break-after: always;
    }
EOB;
    }


    /**
     * {@inheritdoc}
     */
    function validateReport($options) {
        $valid = parent::validateReport($options);
        $valid = $this->validateColumns("ProjectExpedition", ['exp_name']) && $valid;
        $valid = $this->validateColumns("ProjectSite", ['expedition_id', 'site', 'site_name', 'comment']) && $valid;
        $valid = $this->validateColumns("ProjectHole", ['site_id', 'hole', 'comment', 'start_date', 'latitude', 'longitude', 'end_date', 'drillers_reference_height']) && $valid;
        $valid = $this->validateColumns("CoreCore", ['hole_id']) && $valid;
        $valid = $this->validateColumns("CoreSection", ['core_id', 'combined_id', 'top_depth', 'bottom_depth']) && $valid;
		$valid = $this->validateColumns("CurationSectionSplit", ['section_id', 'igsn']) && $valid;
        return $valid;
    }

    /**
     * {@inheritdoc}
     */
    public function generate($options = []) {
        $dataProvider = $this->getDataProvider($options);
        $modelClass = $this->getModelClass($options);
        if ($modelClass != "app\\models\\ProjectHole") {
            $subQuery = $dataProvider->query->select("id");
            $query = \app\models\ProjectHole::find();
            switch (preg_replace("/^.+\\\\/", "", $modelClass)) {
                case "ProjectSite":
                    $query->andWhere(['IN', "project_hole.site_id", $subQuery]);
                    break;
                case "ProjectExpedition":
                    $query->innerJoinWith("site");
                    $query->andWhere(['IN', "project_site.expedition_id", $subQuery]);
                    break;
            }
            $dataProvider = $this->getDataProvider($query);
        }
        $this->content = $this->_generate($dataProvider);
    }

    /**
     * Generates the report for all records in the dataProvider
     * @param \yii\data\ActiveDataProvider $dataProvider
     * @return string Rendered report
     */
    protected function _generate(\yii\data\ActiveDataProvider $dataProvider) {
        $dataProvider->pagination = false;
        $expeditions = [];
        foreach ($dataProvider->getModels() as $hole) {
            $site = $hole->site;
            $expedition = $site->expedition;
            if (!isset($expeditions[$expedition->id])) {
                $expeditions[$expedition->id] = [
                    "expedition" => $expedition,
                    "displayed-sites" => [],
                    "count-sites" => \app\models\ProjectSite::find()->where(['expedition_id' => $expedition->id])->count()];
            }

            if (!isset($expeditions[$expedition->id]["displayed-sites"][$site->id])) {
                $expeditions[$expedition->id]["displayed-sites"][$site->id] = [
                    "site" => $site,
                    "displayed-holes" => []
                ];
            }

            $expeditions[$expedition->id]["displayed-sites"][$site->id]["displayed-holes"][] = $hole;
        }

        uasort ($expeditions, function($a, $b){
           return ($a["expedition"]->exp_name < $b["expedition"]->exp_name ? -1 : 1);
        });
        foreach ($expeditions as $x_exp) {
            uasort($x_exp["displayed-sites"], function($a, $b){
                return ($a["site"]->site < $b["site"]->site ? -1 : 1);
            });

            foreach ($x_exp["displayed-sites"] as $x_site) {
                uasort($x_site["displayed-holes"], function($a, $b){
                    return ($a->hole < $b->hole ? -1 : 1);
                });
            }
        }

        return $this->render(null, [
            'report' => $this,
            'x_expeditions' => $expeditions
        ]);
    }

}
