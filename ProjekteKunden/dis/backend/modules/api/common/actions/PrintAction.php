<?php

namespace app\modules\api\common\actions;

use app\reports\interfaces\IPdfReport;
use Yii;
use yii\base\InvalidConfigException;

/**
 * Class AutoPrintAction
 * @package app\modules\api\common\actions
 *
 * Action to print the current record
 */
class PrintAction extends \yii\rest\Action
{

    /**
     * @param $id ID of the model to print
     * @param $reportName Name of the report to use
     * @return bool Successfully printed
     * @throws InvalidConfigException
     * @throws \yii\web\NotFoundHttpException
     */
    public function run($id, $reportName)
    {
        $modelToPrint = $this->findModel($id);
        if ($this->checkAccess) {
            call_user_func($this->checkAccess, $this->id, $modelToPrint);
        }
        return $this->autoPrint($modelToPrint, $reportName);
    }

    /**
     * @param $modelToPrint
     * @param $reportName
     * @return boolean Successfully printed
     * @throws InvalidConfigException
     */
    protected function autoPrint ($modelToPrint, $reportName) {
        $reportName = preg_replace("/Report$/", "", $reportName) . "Report";
        $reportClass = "\\app\\reports\\" . $reportName;
        if (!class_exists($reportClass))
            throw new InvalidConfigException("Report ' . $reportName . ' does not exist.");
        else {
            $report = new $reportClass($this->controller);
            if (!($report instanceof IPdfReport)) {
                throw new InvalidConfigException("Report ' . $reportName . ' must implement IPdfReport!");
            }
            else if (!method_exists($report,"getPrintCommand")) {
                throw new InvalidConfigException("Report ' . $reportName . ' must implement method 'getPrintCommand'!");
            }
        }

        $options = ["model" => $modelToPrint->getModelFullName(), "id" => $modelToPrint->id];
        \Yii::warning ('AutoPrint report ' . $reportName);
        return $report->autoPrint($options);
    }
}
