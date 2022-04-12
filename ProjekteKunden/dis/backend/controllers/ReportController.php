<?php
namespace app\controllers;

use app\models\DisCore;

/**
 * Class ReportController
 *
 * Runs a report.
 * Reports can be used to display the data of several or one record in different formats or to export the data.
 * Reports a run directly in the yii framework and not via vuejs and the api to make it easier to create new reports.
 *
 * @package app\controllers
 */
class ReportController extends \yii\web\Controller
{

    /**
     * @inheritdoc
     */
    public function beforeAction($action)
    {
        $requestParams = \Yii::$app->getRequest()->getQueryParams();
        $reportName = isset($requestParams['reportName']) ? $requestParams['reportName'] : null;
        if ($reportName == 'BatchEdit') {
            $this->enableCsrfValidation = false;
        }

        return parent::beforeAction($action);
    }

    /**
     * The calls to run different reports are all mapped to this action (see config/web.php).
     * The report PHP file for the request is searched in the file system, instantiated and run.
     * @param string $reportName
     * @return mixed The content generated from the report
     * @throws \yii\web\NotFoundHttpException
     */
    public function actionGenerate($reportName)
    {
        $this->layout = "report.php";

        $options = \Yii::$app->request->queryParams;
        unset($options["controller"]);
        unset($options["reportName"]);

        $reportsPath = \Yii::getAlias("@app/reports") . "/";
        $reportFile = $reportsPath . $reportName . "Report.php";
        $className = "\\app\\reports\\" . $reportName . "Report";

        if (file_exists($reportFile)) {
            try {
                $report = new $className($this);
                if (!$report->validateReport($options) || sizeof($report->validateErrors) > 0) {
                    $this->layout = 'main';
                    return $this->render("error", ["title" => $report::TITLE, "class" => get_class($report), "errors" => $report->validateErrors, "code" => $report->validateErrorCode]);
                }
                return $report->output($options);
            }
            catch (\ParseError $e) {
                $errors = [$e->getMessage() . " at line " . $e->getLine()];
                return $this->render("error", ["title" => $reportName, "class" => $className, "errors" => $errors, "code" => 500]);
            }

        }
        else
            throw new \yii\web\NotFoundHttpException("Report not found: " . $reportName, 404);
    }


}
