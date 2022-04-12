<?php
namespace app\reports;

use app\models\ProjectHole;
use app\reports\interfaces\IHtmlReport;

/**
 * Class ExpeditionEventsOverviewReport
 * Bases on ExpeditionEventsBySiteReport, since most of it is identical.
 * Only the gear data of the sites is cumulated into on record for an expedition.
 *
 * @package app\reports
 */
class ExpeditionEventsOverviewReport extends ExpeditionEventsBySiteReport implements IHtmlReport
{

    /**
     * {@inheritdoc}
     */
    const TITLE = 'Expedition Events Overview';


    /**
     * Process and unify the gear data and cumulate it.
     * Cumulate the sites data into the expeditions
     * @param $gear
     */
    protected function processGear(&$gear) {
        parent::processGear($gear);

        // Cumulate site gears into the expeditions
        foreach ($gear AS $expeditionNo => &$expeditionGear) {
            $bFirst = true;
            $summary = [];
            foreach ($expeditionGear AS $siteNo => $siteGear) {
                if ($bFirst) {
                    $summary = $siteGear;
                    $summary["sites"] = 1;
                    $bFirst = false;
                }
                else {
                    $summary["sites"]++;
                    $summary["holes"]+= $siteGear["holes"];
                    foreach ($this->allGear as $gearName) {
                        $summary[$gearName] = intval($summary[$gearName]) + intval($siteGear[$gearName]);
                    }
                }
            }
            $gear[$expeditionNo] = $summary;
        }
    }

}
