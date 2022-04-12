<?php
namespace app\reports;

use app\models\ProjectHole;
use app\reports\interfaces\IHtmlReport;

/**
 * Class ExpeditionEventsBySiteReport
 *
 * @package app\reports
 */
class ExpeditionEventsBySiteReport extends Base implements IHtmlReport
{
    /**
     * {@inheritdoc}
     */
    const TITLE = 'Expedition Events by Site';

    /**
     * {@inheritdoc}
     * This reports can only be used for ProjectExpedition forms.
     */
    const MODEL = 'ProjectExpedition';

    /**
     * {@inheritdoc}
     * This reports can be used for single or multiple records.
     */
    const SINGLE_RECORD = null;


    const OTHER_GEAR = "Other";

    protected $allGear = [];


    /**
     * {@inheritdoc}
     */
    function validateReport($options) {
        $valid = parent::validateReport($options);
        $valid = $this->validateColumns("ProjectExpedition", [\app\models\ProjectExpedition::NAME_ATTRIBUTE]) && $valid;
        $valid = $this->validateColumns("ProjectSite", [\app\models\ProjectSite::NAME_ATTRIBUTE, 'expedition_id']) && $valid;
        $valid = $this->validateColumns("ProjectHole", ['site_id', 'gear']) && $valid;
        return $valid;
    }


    /**
     * {@inheritdoc}
     */
    public function generate($options = []) {
        $dataProvider = $this->getDataProvider($options);
        $subQuery = $dataProvider->query->select("id");

        $query = ProjectHole::find()
            ->innerJoinWith("site")
            ->andWhere(['IN', 'expedition_id', $subQuery]);
        $dataProvider = $this->getDataProvider($query);

        $this->content = $this->_generate($dataProvider);
    }

    /**
     * Generates the report for all records in the dataProvider
     * @param \yii\data\ActiveDataProvider $dataProvider
     * @return string HTML of the rendered report
     */
    protected function _generate($dataProvider) {
        $dataProvider->pagination = false;

        $ancestorValues = [];
        $content = "";
        $gear = [];
        $countExpeditions = 0;
        $countSites = 0;
        $countHoles = $dataProvider->count;

        // Collect the gear data by expedition and site
        foreach ($dataProvider->getModels() as $hole) {
            if (sizeof($ancestorValues) == 0) {
                $ancestorValues = $this->getAncestorValues($hole);
                array_pop($ancestorValues);
                array_pop($ancestorValues);
                $this->setProgram($hole);
            }

            $site = $hole->site;
            $siteNo = $site->{$site::NAME_ATTRIBUTE};

            $expedition = $site->expedition;
            $expeditionNo = $expedition->{$expedition::NAME_ATTRIBUTE};

            if (!isset($gear[$expeditionNo])) {
                $gear[$expeditionNo] = [];
                $countExpeditions++;
            }
            if (!isset($gear[$expeditionNo][$siteNo])) {
                $gear[$expeditionNo][$siteNo]= [];
                $countSites++;
            }

            $gear[$expeditionNo][$siteNo][] = $this->translateGearName($hole->gear);
        }

        $this->processGear($gear);

        $countAllExpeditions = \app\models\ProjectExpedition::find()->count();
        $countAllSites = \app\models\ProjectSite::find()->count();
        $countAllHoles = \app\models\ProjectHole::find()->count();
        $headerAttributes = ["Displayed expeditions" => $countExpeditions . " / " . $countAllExpeditions];
        $headerAttributes["Displayed sites"] = $countSites . " / " . $countAllSites;
        $headerAttributes["Displayed events"] = $countHoles . " / " . $countAllHoles;

        // Render the full report
        return $this->render(null, [
            'header' => $this->renderDisHeader($headerAttributes),
            'gearNames' => $this->allGear,
            'gear' => $gear
        ]);
    }

    /**
     * Translate the gear name
     * Empty values are replaced with a value "Other"
     * @param $gearName
     * @return string
     */
    protected function translateGearName($gearName) {
        if (empty($gearName))
            return static::OTHER_GEAR;
        else
            return $gearName;
    }

    /**
     * Process and unify the gear data and cumulate it.
     * @param $gear Hierarchical array of gear data
     */
    protected function processGear(&$gear) {
        // Show all possible gear
        // $this->allGear = array_merge($this->allGear, ["ADCP", "BC", "CAT", "CPT", "CTD", "CTD+RO", "GBC", "GC", "HF", "Lander", "MBC", "MBES", "MBES+PS", "MIC", "MUC", "PC", "RC", "ROV", "VC"]);

        // Cumulate gear of the holes
        foreach ($gear AS $expeditionNo => & $expeditionGear) {
            foreach ($expeditionGear AS $siteNo => $siteGear) {
                $summary = array_count_values($siteGear);
                $this->allGear = array_merge($this->allGear, array_keys($summary));
                $summary["holes"] = sizeof($siteGear);
                $expeditionGear[$siteNo] = $summary;
            }
        }
        // The data of each site now contains existing gears as keys and corresponding numbers as values
        // Additionally it containes the number of holes in the key "holes"

        // Sort values in allGear and move "OTHER" to the end, if it exists
        $this->allGear = array_unique($this->allGear);
        sort($this->allGear);
        if (($key = array_search(static::OTHER_GEAR, $this->allGear)) !== false) {
            unset($this->allGear[$key]);
            $this->allGear[] = static::OTHER_GEAR;
        }

        // Fill up gear of the sites, so each site contains all gears as keys
        $emptyGears = array_fill_keys ($this->allGear, "");
        foreach ($gear AS $expeditionNo => &$expeditionGear) {
            foreach ($expeditionGear AS $siteNo => $siteGear) {
                $expeditionGear[$siteNo] = array_merge($emptyGears, $siteGear);
            }
        }
    }

    function getCss()
    {
        $cssFile = \Yii::getAlias("@webroot/css/report.css");
        $stylesheet = file_get_contents($cssFile);
        return $stylesheet . <<<'EOB'
            table.report th, table.report td {
                padding-left: 0.35em;
                padding-right: 0.35em;
            }
EOB;
    }
}
