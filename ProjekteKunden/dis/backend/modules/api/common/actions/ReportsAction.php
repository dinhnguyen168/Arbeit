<?php

namespace app\modules\api\common\actions;

use Yii;
use yii\base\Model;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use yii\web\BadRequestHttpException;

/**
 * Class ReportsAction
 * @package app\modules\api\common\actions
 *
 * Action to get the available reports for a form.
 */
class ReportsAction extends \yii\rest\Action
{
    /**
     * Return an array of the available reports for this list of records ("multiple") and for the current record ("single").
     * Reports are assigned to both lists, if "singleRecord" attribute is null.
     * @return array Associative array in the form ["single" => [<ReportInfo>, ...], "multiple" => [<ReportInfo>, ...]].
     * @throws \yii\base\InvalidConfigException
     * @throws BadRequestHttpException
     */
    public function run()
    {
        if ($this->checkAccess) {
            call_user_func($this->checkAccess, $this->id);
        }
        $model = $this->controller->getName();
        $reportInfos = [
            "single" => [],
            "multiple" => []
        ];
        $reportsDirectory = realpath(\Yii::getAlias("@app/reports/")) . "/";
        $singleRecordReports = [];
        $multipleRecordReports = [];
        foreach (glob($reportsDirectory . "*Report.php") as $reportFile) {
            if (basename($reportFile) !== "Base.php") {
                $reportInfo = $this->getReportInfo($reportFile);
                if ($reportInfo && preg_match("/^" . $reportInfo["model"] . "/", $model)) {
                    if ($reportInfo["singleRecord"] === null) {
                        $singleRecordReports[] = $reportInfo;
                        $multipleRecordReports[] = $reportInfo;
                    }
                    else if ($reportInfo["singleRecord"])
                        $singleRecordReports[] = $reportInfo;
                    else
                        $multipleRecordReports[] = $reportInfo;
                }
            }
        }
        usort($singleRecordReports, function ($a, $b) {
            return $a['title'] < $b['title'] ? -1 : 1;
        });
        usort($singleRecordReports, function ($a, $b) {
            return $a['type'] <= $b['type'] ? -1 : 1;
        });
        usort($multipleRecordReports, function ($a, $b) {
            return $a['title'] < $b['title'] ? -1 : 1;
        });
        usort($multipleRecordReports, function ($a, $b) {
            return $a['type'] <= $b['type'] ? -1 : 1;
        });
        return [
            "single" => $singleRecordReports,
            "multiple" => $multipleRecordReports
        ];
    }

    /**
     * Get the informations on a report
     * @param $reportFile file path of the PHP report file (in directory "backend/reports")
     * @return array Associative array with keys "name", "title", "model", "modified", "singleRecord"
     */
    public function getReportInfo ($reportFile) {
        $cacheId = "Reportinfo:" . $reportFile;
        $name = basename($reportFile, ".php");
        $reportInfo = false; // \Yii::$app->cache->get($cacheId);
        if (!is_array($reportInfo) || $reportInfo["modified"] < filemtime($reportFile)) {
            $reportInfo = [
                "name" => preg_replace('/Report$/', '', $name),
                "title" => "",
                "model" => "",
                "modified" => time(),
                "type" => "report",
                "singleRecord" => false,
                "directPrint" => false
            ];

            $className = "app\\reports\\" . $name;
            try {
                $isPrinterAvailable = (\app\reports\Base::getPrinterForReport(preg_replace("/^(.+)Report$/", "$1", $name)) > "");
                $reportInfo["title"] = constant($className . "::TITLE");
                $reportInfo["model"] = constant($className . "::MODEL");
                $reportInfo["singleRecord"] = constant($className . "::SINGLE_RECORD");
                $reportInfo["type"] = constant($className . "::REPORT_TYPE");
                $reportInfo["confirmationMessage"] = constant($className . "::CONFIRMATION_MESSAGE");
                if ($isPrinterAvailable) {
                    $reportInfo["directPrint"] = in_array("app\\reports\\interfaces\\IDirectPrintReport", class_implements ( "\\" . $className));
                    if ($reportInfo["singleRecord"] !== false) {
                        $reportInfo["canAutoPrint"] = in_array("app\\reports\\interfaces\\IPdfReport", class_implements ( "\\" . $className));
                    }
                }
                \Yii::$app->cache->set($cacheId, $reportInfo);

            }
            catch (\ParseError $e) {
                $error = sprintf("%s: (Parse error!)", basename($reportFile, '.php'));
                $reportInfo["title"] = $error;
                $reportInfo["model"] = '.*';
                $reportInfo["singleRecord"] = null;
            }
        }
        unset ($reportInfo["modified"]);


        return $reportInfo;
    }
}
