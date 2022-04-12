<?php

namespace app\reports;

use app\controllers\ReportController;
use app\rbac\LimitedAccessRule;
use app\reports\interfaces\ICsvReport;
use app\reports\interfaces\IDirectPrintReport;
use app\reports\interfaces\IPdfReport;
use app\reports\interfaces\IReport;
use app\reports\interfaces\IXmlReport;
use app\reports\traits\DirectPrintTrait;
use kartik\mpdf\Pdf;
use Mpdf\HTMLParserMode;
use Mpdf\Mpdf;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\debug\panels\EventPanel;
use yii\web\Response;

/**
 * Class Base
 *
 * Base class for all reports. Every report has to implement the generate() method.
 * The class and file basename must end with "Report".
 * Reports can be used to display the data of several or one record in different formats or to export the data.
 *
 * @package app\reports
 */
abstract class Base implements IReport
{
    /**
     * Title to show in the menu
     */
    const TITLE = '';
    /**
     * Regular expression for the data model.
     * When a form is opened, its data model is matched to this regular expression.
     * This can be just the name of a data model (i.e. 'CoreCore'), the wildcard '.*' or some other regular expression
     */
    const MODEL = '';

    /**
     * Does this report show only one single record?
     * A report can either be used to show or export multiple records or for one record only. Depending on this
     * the report will be offered for the list of records or the current record in the form.
     * If a report should be available for both options set the value to NULL.
     */
    const SINGLE_RECORD = false;

    /**
     * The report can be one of the following ['report', 'export', actions]
     * export is in a report that will be downloaded rather than viewed on the browser
     */
    const REPORT_TYPE = 'report';

    /**
     * Confirmation message to show to user when clicking on a report.
     * false for no confirmation message
     */
    const CONFIRMATION_MESSAGE = false;

    /**
     * @var ReportController The report controller
     */
    protected $controller;

    /**
     * @var \app\models\ProjectExpedition This expedition is used to print a header on some reports.
     */
    protected $expedition;

    /**
     * @var \app\models\ProjectProgram This project is used to print a header on some reports.
     */
    protected $program;

    /**
     * @var mixed the report content
     */
    protected $content;

    static function class_uses_deep($class, $autoload = true)
    {
        $traits = [];
        $x = class_uses($class, $autoload);
        // Get traits of all parent classes
        do {
            $traits = array_merge(class_uses($class, $autoload), $traits);
        } while ($class = get_parent_class($class));
        // Get traits of all parent traits
        $traitsToSearch = $traits;
        while (!empty($traitsToSearch)) {
            $newTraits = class_uses(array_pop($traitsToSearch), $autoload);
            $traits = array_merge($newTraits, $traits);
            $traitsToSearch = array_merge($newTraits, $traitsToSearch);
        }
        foreach ($traits as $trait => $same) {
            $traits = array_merge(class_uses($trait, $autoload), $traits);
        }
        return array_unique($traits);
    }

    /**
     * Base constructor.
     * @param $controller
     */
    public function __construct($controller) {
        $this->controller = $controller;
        // check if class uses the required traits
        if ($this instanceof IDirectPrintReport && !($this instanceof IPdfReport)) {
            throw new Exception("Direct print report must implement IPdfReport.");
        }
        if ($this instanceof IDirectPrintReport && !in_array(DirectPrintTrait::class, array_keys(self::class_uses_deep(static::class)))) {
            throw new Exception("Direct print report must use DirectPrintTrait.");
        }
    }

    /**
     * Unknown methods are directed to the controller. This way, you can call Methods like render() on the report.
     * @param string $name Name of the method
     * @param array $params Parameters for the method
     * @return mixed Return value from the method called on the controller.
     */
    public function __call($name, $params)
    {
        if ($this->controller->hasMethod($name)) {
            return call_user_func_array([$this->controller, $name], $params);
        }
        throw new \yii\base\UnknownMethodException('Calling unknown method: ' . get_class($this) . "::$name()");
    }

    public static function getClassWithoutNamespace() {
        $path = explode('\\', get_called_class());
        return array_pop($path);
    }

    /**
     * Normally view templates have to be a file in the file system. To allow the definition of a view template on the
     * fly in a variable, this method generates a temporary view file with the contents of the template string and then
     * calls the normal render method for that file.
     * @param string $template content of the template
     * @param array $params Parameters to supply to the template
     * @return string Rendered output from the template
     */
    public function renderPartialString($template, $params = []) {
        $runtimeViewPath = \Yii::getAlias("@runtime/reports");
        if (!file_exists($runtimeViewPath)) {
            mkdir($runtimeViewPath);
        }
        $runtimeView = "@runtime/reports/" . md5($template) . ".php";
        $runtimeViewFile = \Yii::getAlias($runtimeView);
        if (!file_exists($runtimeViewFile)) {
            file_put_contents($runtimeViewFile, $template);
            usleep(500);
        }
        return $this->controller->renderPartial($runtimeView, $params);
    }

    /**
     * Normally view templates have to be a file in the file system. To allow the definition of a view template on the
     * fly in a variable, this method generates a temporary view file with the contents of the template string and then
     * calls the normal render method for that file.
     * @param string $template content of the template
     * @param array $params Parameters to supply to the template
     * @return string Rendered output from the template
     */
    public function renderString($template, $params = []) {
        $runtimeViewPath = \Yii::getAlias("@runtime/reports");
        if (!file_exists($runtimeViewPath)) {
            mkdir($runtimeViewPath);
        }
        $runtimeView = "@runtime/reports/" . md5($template) . ".php";
        $runtimeViewFile = \Yii::getAlias($runtimeView);
        if (!file_exists($runtimeViewFile)) {
            file_put_contents($runtimeViewFile, $template);
            usleep(500);
        }
        return $this->controller->render($runtimeView, $params);
    }


    /**
     * Renders a view file.
     * The view file for a report should be in the same directory /backend/reports/ and should have the same name as
     * the report but with the file extension ".view.php"
     * A report could have multiple view files. The a unique name starting with the report name should be given.
     * @param null $viewFile Name of viewFile. I ommited it is build based on the report name with extension ".view.php"
     * @param array $params Parameters to supply to the view file
     * @return string Rendered output from rendering the view file
     */
    public function render($viewFile = null, $params = []) {
        if ($viewFile == null) {
            $viewFile = "@app/reports/" . static::getClassWithoutNamespace() . ".view.php";
        }
        else if (strpos($viewFile, "/") === FALSE) {
            if (!preg_match("/\\.php$/", $viewFile)) {
                if (!preg_match("/\\.view$/", $viewFile)) $viewFile .= ".view";
                $viewFile .= ".php";
            }
            $viewFile = "@app/reports/" . $viewFile;
        }
        return $this->controller->render($viewFile, $params);
    }


    /**
     * Renders a view file.
     * The view file for a report should be in the same directory /backend/reports/ and should have the same name as
     * the report but with the file extension ".view.php"
     * A report could have multiple view files. The a unique name starting with the report name should be given.
     * @param null $viewFile Name of viewFile. I ommited it is build based on the report name with extension ".view.php"
     * @param array $params Parameters to supply to the view file
     * @return string Rendered output from rendering the view file
     */
    public function renderPartial($viewFile = null, $params = []) {
        if ($viewFile == null) {
            $viewFile = "@app/reports/" . static::getClassWithoutNamespace() . ".view.php";
        }
        else if (strpos($viewFile, "/") === FALSE) {
            if (!preg_match("/\\.php$/", $viewFile)) {
                if (!preg_match("/\\.view$/", $viewFile)) $viewFile .= ".view";
                $viewFile .= ".php";
            }
            $viewFile = "@app/reports/" . $viewFile;
        }
        return $this->controller->renderPartial($viewFile, $params);
    }


    /**
     * Render a unified header for all reports
     * @param array $attributes Associative array ["label" => value] to be shown in the header
     * @param string|null $reportName Name of the report. By default static::TITLE is used
     * @param \app\models\ProjectExpedition|null $expedition By default the expedition set by setExpedition() is used
     * @param \app\models\ProjectProgram|null $program By default the program set by setProgram() or setExpedition() is used
     * @return string HMTL of rendered header
     */
    public function renderDisHeader($attributes, $reportName = null, $expedition = null, $program = null, $extraHeaderRow = null) {
        if ($reportName == null) $reportName = static::TITLE;
        if ($expedition == null) $expedition = $this->expedition;
        if ($program == null) $program = $this->program;

        foreach (["Expedition", "Program Shortcut", "Expedition Code"] as $unsetLabel) {
            unset($attributes[$unsetLabel]);
            unset($attributes[strtolower($unsetLabel)]);
        }

        return $this->controller->renderPartial("@app/views/report/DisHeader.php", [
            'program' => $program,
            'expedition' => $expedition,
            'reportName' => $reportName,
            'attributes' => $attributes,
            'repository' => $this->getRepository(),
            'extraHeaderRow' => $extraHeaderRow
        ]);
    }

    protected function getRepository() {
        $aLogos = glob(\Yii::getAlias("@webroot/img/logos") . "/repository_*.png");
        if (sizeof($aLogos)) {
            $cName = preg_replace("/^repository_/", "", basename($aLogos[0], ".png"));
            $cUrl = str_replace(\Yii::getAlias("@webroot"), "", $aLogos[0]);
            return ["name" => $cName, "url" => $cUrl];
        }
        return null;
    }

    public function renderDisExtraHeader ($headerAttributes, $class = "") {
        return $this->renderPartial("@app/views/report/DisExtraHeader.php", [
            'attributes' => $headerAttributes,
            'class' => $class
        ]);
    }


    /**
     * Creates a pdf object.
     * @return Mpdf PDF object
     */
    protected function createPdf() {
        // Creating pdfs can take a long time.
        set_time_limit(600);

        $pdf = new Pdf([
            // set to use core fonts only
            'mode' => Pdf::MODE_CORE,
            // A4 paper format
            'format' => Pdf::FORMAT_A4,
            // portrait orientation
            'orientation' => Pdf::ORIENT_LANDSCAPE,
            'defaultFont' => 'dejavusans',
            'marginTop' => 35
        ]);
        $mPdf = $pdf->api; // fetches mpdf api
        $mPdf->SetFooter('Page {PAGENO}');

        return $mPdf;
    }

    /**
     * Returns the full data model class name (including namespace)
     * In the Query parameters for the reportController there must be data model name in the parameter "model"
     * @param string[] $options Query parameters provided to the reportController
     * @return string Full data model class name
     */
    protected function getModelClass($options) {
        $modelName = $options["model"];
        return "app\\models\\" . $modelName;
    }

    /**
     * Creates a database provider by calling the search() method on the search model.
     * In the Query parameters for the reportController there must be data model name in the parameter "model"
     * @param array|\yii\db\ActiveQuery $options Query parameters provided to the reportController | ActiveQuery
     * @return \yii\data\ActiveDataProvider Dataprovider
     */
    protected function getDataProvider($options) {
        $dataProvider = null;
        if ($options instanceof \yii\db\ActiveQuery) {
            $dataProvider = new \yii\data\ActiveDataProvider([
                'query' => $options,
            ]);
            $modelClass = $options->modelClass;
        } elseif (isset($options['specific-ids'])) {
            $modelClass = $this->getModelClass($options);
            $query = call_user_func([$modelClass, "find"]);
            $ids = preg_split('/,/', $options['specific-ids']);
            $query->where([
                'IN',
                $modelClass::tableName() . '.id',
                $ids
            ]);
            $dataProvider = new \yii\data\ActiveDataProvider([
                'query' => $query,
            ]);
        } else {
            if (isset($options["id"]) && !isset($options["filter"])) {
                $options["filter"] = ["id" => $options["id"]];
            }
            // $modelClass = $this->getModelClass($options);
            $searchModelClass = $this->getModelClass($options) . "Search";
            $searchModel = new $searchModelClass();
            $dataProvider = call_user_func([$searchModel, "search"], $options);
        }

        // Apply access filters
        LimitedAccessRule::addLimitedAccessCondition($dataProvider->query);

        return $dataProvider;
    }



    /**
     * Returns an array of the ancestor name values up to ProjectProgram for a data model.
     * For CoreCore, the number and core type is used.
     * @param $model Data model
     * @return array Associative array in the form [<attribute name> => [<value> , <attribute label>], ...]
     */
    public function getAncestorValues($model) {
        $ancestorValues = [];
        if (isset($model->parent)) {
            $parent = $model->parent;
            while ($parent) {

                $nameAttribute = constant(get_class($parent) . "::NAME_ATTRIBUTE");
                if ($nameAttribute > "") {
                    $value = $parent->{$nameAttribute};
                    if ($parent instanceof \app\models\CoreCore && isset($parent->core_type)) {
                        $value .= " " . $parent->core_type;
                    }
                    $ancestorValues[$nameAttribute] = [$value, $parent->getAttributeLabel($nameAttribute)];
                }

                if (method_exists($parent, 'getParent'))
                    $parent = $parent->parent;
                else
                    $parent = null;
            }
            $ancestorValues = array_reverse($ancestorValues, true);
        }
        return $ancestorValues;
    }

    public function getManyToManyValues($model) {
        $manyToManyValues = [];
        $className = get_class($model);
        if (defined($className . '::MANY_TO_MANY_COLUMNS')) {
            foreach ($model::MANY_TO_MANY_COLUMNS as $key => $value) {
                $ralationName = $value[1];
                $displayColumn = $value[0];
                $values = [];
                foreach ($model->{$ralationName} as $relatedModel) {
                    $values[] = $relatedModel->{$displayColumn};
                }
                $manyToManyValues[$key] = [implode(', ',$values), $model->getAttributeLabel($key)];
            }
        };
        return $manyToManyValues;
    }

    public function getOneToManyValue($model) {
        $oneToManyValue = [];
        $className = get_class($model);
        if (defined($className . '::ONE_TO_MANY_COLUMNS')) {
            foreach ($model::ONE_TO_MANY_COLUMNS as $key => $value) {
                $relationName = $value[1];
                $displayColumn = $value[0];
                $oneToManyValue[$key] = [$model->{$relationName} ? $model->{$relationName}->{$displayColumn} : null, $model->getAttributeLabel($key)];
            }
        };
        return $oneToManyValue;
    }

    /**
     * Returns an associative array of the names and labels for the requested (or all) columns of the data model
     * @param $options Query parameters provided to the reportController
     * @return array Associative array in the form [<column name> => <column label>, ...]
     */
    protected function getColumns($options) {
        $columns = [];
        $modelClass = $this->getModelClass($options);
        $model = new $modelClass();
        if (isset($options["columns"])) {
            $aLabels = $model->attributeLabels();
            foreach (explode(",", $options["columns"]) as $columnName) {
                $columns[$columnName] = $model->getAttributeLabel($columnName);
            }
        }
        else {
            $columns = array_merge($columns, $model->attributeLabels());
        }
        return $columns;
    }

    /**
     * Defines the expedition by giving a data model. For this data model a relation "expedition" is used to determine
     * the expedition. If the model is null or that relation does not exist, the method getCurrentExpedition() from the
     * AppController is used to find the current expedition.
     *
     * If $model is of class ProjectProgram, only setProgram is called.
     * @param object $model
     */
    public function setExpedition($model = null) {
        if ($model && $model instanceof \app\models\ProjectExpedition)
            $this->expedition = $model;
        else if ($model && $model instanceof \app\models\ProjectProgram)
            $this->setProgram($model);
        else if ($model && method_exists($model, 'getExpedition'))
            $this->expedition = $model->expedition;
        else
            $this->expedition = \app\modules\api\common\controllers\AppController::getCurrentExpedition();

        if ($this->expedition) $this->setProgram($this->expedition);
    }

    /**
     * Defines the program by giving a data model. For this data model a relation "program" is used to determine
     * the expedition. If the model is null or that relation does not exist, the method getCurrentProgram() from the
     * AppController is used to find the current program.
     * @param object $model
     */
    public function setProgram($model = null) {
        if ($model instanceof \app\models\ProjectProgram)
            $this->program = $model;
        else if ($model && method_exists($model, 'getProgram'))
            $this->program = $model->program;
        else
            $this->program = \app\modules\api\common\controllers\AppController::getCurrentProgram();
    }

    /**
     * Find an image for a section
     * @param $section The section object
     * @param string[]|null $fileTypes Array of the filetypes in the prefered priority, i.e. ["CS", "SS"]
     * @return ArchiveFile|null Found image
     */
    protected function getSectionImage($section, $fileTypes = null) {
        if ($fileTypes === null) {
            //TODO: get default Files types to show for sections from global settings
            $fileTypes = ["CS", "SS", "BA", "BW", "BWR", "CB"];
        }

        foreach ($fileTypes as $fileType) {
            foreach ($section->archiveFiles as $archiveFile) {
                if ($archiveFile->type == $fileType && preg_match("/^image/", $archiveFile->mime_type)) {
                    $file = $archiveFile->getConvertedFile();
                    if (file_exists($file)) {
                        return $archiveFile;
                    }
                }
            }
        }
        return null;
    }


    /**
     * Get the URL of an image for a section
     * @param $section The section object
     * @param string[]|null $fileTypes Array of the filetypes in the prefered priority, i.e. ["CS", "SS"]
     * @return string URL of the image or a placeholder if none is found
     */
    public function getSectionImageUrl($section, $fileTypes = null) {
        $archiveFile = $this->getSectionImage($section, $fileTypes);
        if ($archiveFile)
            return "/files/view-converted?id=" . $archiveFile->id;
        else
            return "/img/missing-image.svg";
    }

    protected function disableYiiDebugToolbar() {
        if (class_exists('yii\debug\Module')) {
            \Yii::$app->getResponse()->off(Response::EVENT_AFTER_PREPARE, [\yii\debug\Module::getInstance(), 'setDebugHeaders']);
            $this->controller->view->off(\yii\web\View::EVENT_END_BODY, [\yii\debug\Module::getInstance(), 'renderToolbar']);
        }
    }

    /**
     * @param array $options
     * @return mixed|string
     * @throws \Mpdf\MpdfException
     * @throws \yii\base\UnknownPropertyException
     *
     * Generate and render report output. On UnknownPropertyExceptions of a model class, render an error page
     */
    public function output($options = []) {
        $response = \Yii::$app->response;
        $headers = $response->headers;
        $htmlHeaders = $headers->toArray();
        $debug = (\Yii::$app->request->getQueryParam("DEBUG", "") > "");
        try {
            if ($this instanceof IPdfReport && !$debug) {
                // disable yii debug toolbar
                set_time_limit(600);
                $this->disableYiiDebugToolbar();
                $this->initPdf();
                $this->generate($options);
                $this->content = preg_replace('/="\\//', '="' . \Yii::getAlias('@webroot') . '/', $this->content);

                $this->getPdf()->WriteHTML($this->getCss(), HTMLParserMode::HEADER_CSS);
                $backtrackLimit = intval(ini_get("pcre.backtrack_limit"));
                $chunkSize = floor($backtrackLimit / 2);
                while (strlen($this->content) > $chunkSize / 2) {
                    $pos = strpos($this->content, "<", floor($chunkSize / 2));
                    if ($pos > 0) {
                        $chunk = substr($this->content, 0, $pos);
                        $this->getPdf()->WriteHTML($chunk, HTMLParserMode::HTML_BODY);
                        $this->content = substr($this->content, $pos);
                    }
                }
                $this->getPdf()->WriteHTML($this->content, HTMLParserMode::HTML_BODY);


                // $inline = $this->getPdf()->Output($pdfTmpPath, 'S');
                $isPrinterAvailable = ($this->getPrinter() > "");
                if ($isPrinterAvailable && $this instanceof IDirectPrintReport) {
                    $pdfTmpPath = \Yii::getAlias('@runtime/reports'). '/' . static::TITLE.'.pdf';
                    $this->getPdf()->Output($pdfTmpPath, 'F');
                    exec($this->getPrintCommand($pdfTmpPath), $output, $return_var);
                    $this->content = $this->formatDirectPrintResult($return_var, $this->getPrintCommand($pdfTmpPath), $this->getPdf());
                } else {
                    $this->getPdf()->Output($this->getPdfFilename() . '.pdf', 'D');
                    \Yii::$app->response->isSent = true;
                }
            } elseif ($this instanceof ICsvReport) {
                $this->generate($options);
                $response->format = \yii\web\Response::FORMAT_RAW;
                $headers->set('Content-type', 'text/csv');
                $headers->set('Content-Disposition', 'attachment; filename="' . $this->getFilename($options) . '"');
                return $this->content;
            } elseif ($this instanceof IXmlReport) {
                $this->generate($options);
                $response->format = \yii\web\Response::FORMAT_RAW;
                $headers->set('Content-Type', 'text/xml');
                return $this->content;
            } else {
                \Yii::$app->controller->layout = "report.php";
                $css = $this->getCss();
                $this->getView()->registerCss($css);
                $this->getView()->registerJs($this->getJs(), \yii\web\View::POS_HEAD);
                $this->generate($options);
            }
        }
        catch (\yii\base\UnknownPropertyException $e) {
            $controller = \Yii::$app->controller;
            $controller->layout = 'main';
            \Yii::$app->response->format = \yii\web\Response::FORMAT_HTML;
            $headers->fromArray($htmlHeaders);

            $aMatches = [];
            if (preg_match ("/(app\\\\models\\\\\\S+)::(\\S+)$/", $e->getMessage(), $aMatches)) {
                $className = $aMatches[1];
                $model = new $className();
                $tableName = $model->tableName();
                $property = $aMatches[2];
                $this->validateErrors[] = "Model '$className' (data table '$tableName') does not contain the property: <ul><li>". $property . "</li></ul>";
                $this->validateErrorCode = 500;
                return $controller->render("error", ["title" => static::TITLE, "class" => get_class($this), "errors" => $this->validateErrors, "code" => $this->validateErrorCode]);
            }
            else
                throw $e;
        }

        return $this->content;
    }


    /**
     * @var array Array of validation errors for the report
     */
    public $validateErrors = [];

    /**
     * @var integer HTTP Error code of validation
     */
    public $validateErrorCode = 0;


    /**
     * Validate the report; check if all needed columns exist; ...
     * If report is not valid, fill $validateErrors
     * Additionally, an exception handler in method output reports missing properties in models.
     * @return bool Report is valid
     */
    public function validateReport($options) {
        if (isset($options["model"])) {
            if (!$this->validateModel($options["model"])) {
                return false;
            }

            if (!preg_match("/" . static::MODEL . "/", $options["model"])) {
                $this->validateErrorCode = 500;
                $this->validateErrors[] = "This report can not be used for model '" . $options["model"] . "'";
                return false;
            }
        }
        return true;
    }

    /**
     * Validate a model:
     * - template exists
     * - required class files (incl. base and search)
     * - class exists
     * - database table exists
     * - user may access table data
     * @param $modelClass Name of model or class including namespace
     * @return bool
     */
    protected function validateModel ($modelClass) {
        if (!preg_match("/^app\\\models/", $modelClass)) {
            $modelClass = "app\\models\\" . $modelClass;
        }

        $modelName = str_replace("app\\models\\", "", $modelClass);
        $modelTemplate = \Yii::$app->templates->getModelTemplate($modelName);
        if (!$modelTemplate) {
            $this->validateErrorCode = 500;
            $this->validateErrors[] = "The template for model '" . $modelName . "' does not exist";
        }
        else if (!$modelTemplate->validateClassFilesExist()) {
            $this->validateErrorCode = 500;
            $this->validateErrors[] = "Some class files for model '" . $modelName . "' are missing";
        } else if (!class_exists($modelClass)) {
            $this->validateErrorCode = 500;
            $this->validateErrors[] = "The model class '" . $modelClass . "' does not exist";
        } else if (!$modelTemplate->getIsTableCreated()) {
            $this->validateErrorCode = 500;
            $this->validateErrors[] = "The database table '" . $modelTemplate->table . "' of model '" . $modelName . "' has not been created";
        } else if (!$modelTemplate->checkAccess()) {
            $this->validateErrorCode = 403;
            if (\Yii::$app->user->isGuest)
                $error = "You are not logged in.";
            else
                $error = "You may not view records of model '$modelName'";
            $this->validateErrors[] = $error;
        }

        $this->validateErrors = array_unique($this->validateErrors);
        return (sizeof($this->validateErrors) == 0);
    }

    /**
     * Check if columns for the given object or model class exists
     * Fill $validateErrors if some do not exist.
     * @param object|string $model Object or Class name to check columns for
     * @param array $columns List of columns to check
     * @return bool All columns exist in the object / model class
     */
    protected function validateColumns($model, $columns = []) {
        $modelClass = (is_string($model) ? $model : get_class($model));

        if (!$this->validateModel($modelClass)) {
            return false;
        }

        if (!is_object($model)) {
            if (!preg_match("/^app\\\models/", $model)) {
                $model = "app\\models\\" . $model;
            }
            $model = new $model();
        }

        $missingColumns = [];
        foreach ($columns as $column) {
            if (!$model->hasProperty($column)) {
                $missingColumns[] = $column;
            }
        }
        if (sizeof($missingColumns)) {
            $className = get_class($model);
            $tableName = $model->tableName();
            $this->validateErrorCode = 500;
            $this->validateErrors[] = "Model '$className' (data table '$tableName') does not contain the columns: <ul><li>" . implode("</li><li>", $missingColumns) . "</li></ul>";
            return false;
        }
        return true;
    }


    /**
     * Returns the name/host of the printer to use for the given Report
     * @param $report Report name (Class without namespace and ending "Report")
     * @return string Name/host of printer or "" if no printer is available for this report
     */
    public static function getPrinterForReport($report) {
        $printerConfig = \Yii::$app->params['printers'];
        if (isset($printerConfig[$report]))
            return $printerConfig[$report];
        else if (isset($printerConfig["default"]))
            return $printerConfig["default"];
        return "";
    }

    /**
     * Returns the name/host of the printer to use in the current report
     * The printer for a report is determined by the settings params "printers".
     * If a report has a dedicated printer, the name of the report is a key in this array.
     * The default printer is listed in the key "default".
     * Format for a printer: <printername> or <printername>@<host> or <printername>@<host>:<port> or @<host>...
     * @return string Name/Host of printer for this report
     */
    public function getPrinter() {
        $report = preg_replace("/^.+\\\\(.+)Report$/", "$1", get_class($this));
        $printer = static::getPrinterForReport($report);
        return $printer;
    }

    /**
     * Returns the options to print a pdf file
     * The
     * @param $pdfFile string full path of the file to print
     * @return array of print options containing printer and host
     */
    function getPrintOptions($pdfFile) {
        $options = [];
        if ($printer = $this->getPrinter()) {
            $matches = [];
            if (preg_match("/^(.*)@(.*)$/", $printer, $matches)) {
                if ($matches[1]) $options["printer"] = $matches[1];
                if ($matches[2]) $options["host"] = $matches[2];
            }
            else
                $options["printer"] = $printer;
        }
        return $options;
    }

    /**
     * Returns the command to print the pdf file
     * @param $pdfFile Filename (with path) of the pdf file to print
     * @throws InvalidConfigException
     * @return string Command string to print the pdf file
     */
    public function getPrintCommand($pdfFile) {
        $isWindows = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');
        $paramChar = $isWindows ? "/" : "-";
        $options = $this->getPrintOptions($pdfFile);
        $scriptFile = \Yii::getAlias("@app/bin/printReport") . ($isWindows ? ".cmd" : ".sh");
        if (!file_exists($scriptFile)) throw new InvalidConfigException ("Script to print reports not found!");
        $cmd = '"' . $scriptFile . '"';
        foreach ($options as $key => $value) {
            switch ($key) {
                case "host":
                    $cmd .= " " . $paramChar . "H " . $value;
                    break;
                case "printer":
                    $cmd .= " " . $paramChar . "P " . $value;
                    break;
                default:
                    $cmd .= " " . $paramChar . $key . " " . $value;
            }
        }
        $cmd .= ' "' . $pdfFile . '"';
        // die ("getPrintCommand(): " . $cmd);
        return $cmd;
    }


    /**
     * Auto Prints the report
     * @param $options Parameters to render the report
     * @return bool Print command has been successfully committed
     * @throws InvalidConfigException
     * @throws \Mpdf\MpdfException
     */
    public function autoPrint($options) {
        $printer = $this->getPrinter();
        if (!($this instanceof IPdfReport)) {
            throw new InvalidConfigException("To auto print the report, it must instance IPdfReport!");
        }
        else if (!$printer) {
            throw new InvalidConfigException("No printer found in configuration.");
        }
        else {
            set_time_limit(600);
            \Yii::$app->controller->layout = false;
            $this->initPdf();
            $this->generate($options);
            $this->getPdf()->WriteHTML($this->getCss(), HTMLParserMode::HEADER_CSS);
            $this->getPdf()->WriteHTML($this->content, HTMLParserMode::HTML_BODY);

            $pdfTmpPath = \Yii::getAlias('@runtime/reports'). '/' . static::TITLE.'.pdf';
            $this->getPdf()->Output($pdfTmpPath, 'F');
            $output = "";
            $return_var = null;
            $command = $this->getPrintCommand($pdfTmpPath);
            exec ($command, $output, $return_var);
            if ($return_var !== 0) {
                if (is_array($output)) $output = implode("\n", $output);
                \Yii::error("Error auto printing report " . get_class($this) . ": Code=" . $return_var . "; Output: " . $output);
                throw new InvalidConfigException("Error auto printing report " . get_class($this) . ": Code=" . $return_var . "; Output: " . $output);
            }
            return $return_var == 0;
        }
        return false;
    }

    function getCss() {
        return "";
    }

    function getJs() {
        return "";
    }

    /**
     * Writes some characters to the output page
     * @param string $chars
     */
    protected function echoChars($chars) {
        echo $chars;
        ob_flush();
        flush();
        usleep(1000);
    }

    /**
     * Writes a line to the output page
     * @param string $line
     */
    protected function echo ($line = "") {
        $this->echoChars ($line . '<br>');
    }
    /**
     * Writes a line to the output page
     * @param string $line
     */
    protected function echoWithoutLineBreak ($line = "") {
        $this->echoChars ($line );
    }

    protected function error ($line) {
        $this->echo ('<span class="alert-danger">' . $line . '</span>');
    }

    /**
     * Writes a line formatted as "info" to the output page
     * @param $line
     */
    protected function info ($line) {
        $this->echo ('<span class="alert-info">' . $line . '</span>');
    }

    /**
     * Writes a line formatted as "warning" to the output page
     * @param $line
     */
    protected function warning ($line) {
        $this->echo ('<span class="alert-warning">' . $line . '</span>');
    }

    /**
     * Renders the foot of the output page.
     * This should be called at the end of the run() method of any inheriting class.
     */
    public function finish() {
        $this->controller->layout = "report_foot.php";
        // Write output collected by calls to "echo()", "info()", "warning()", "error()"
        $this->echoChars ($this->controller->renderContent(""));
        die();
    }
}
