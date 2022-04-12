<?php
namespace app\controllers;

use app\models\ArchiveFile;
use yii\filters\AccessControl;

/**
 * Class ImportController
 *
 * Runs an importer.
 * Importers are run directly in the yii framework and not via vuejs and the api to make it easier to implement new importers.
 *
 * @package app\controllers
 */
class ImporterController extends \yii\web\Controller
{
    /**
     * @var string Name of the import file
     */
    protected $filename;
    /**
     * @var bool Is this a dry run? No data will be imported, only tested.
     */
    protected $dryRun = true;
    /**
     * @var bool Stop on the first error?
     */
    protected $stopOnErrors = true;
    /**
     * @var bool Delete Records from the import file instead of importing the records.
     */
    protected $deleteRecords = false;
    /**
     * @var string Name of the data model (= table) to import the records into.
     */
    protected $modelName;

    public function behaviors()
    {
        return array_merge(
            parent::behaviors(),
            [
                'access' => [
                    'class' => AccessControl::class,
                    'rules' => [
                        [
                            'allow' => true,
                            'roles' => ['operator']
                        ]
                    ]
                ]
            ]
        );
    }

    /**
     * Returns an array of the available importes from the file system. Importers are searched in directory "backend/importers"
     * and must end with "Importer.php". Every importer inherits (via Base) from yii\web\ViewAction and is run automatically
     * from the yii framework.
     * @return array
     */
    public function actions()
    {
        $this->parseQueryParams();

        $actions = parent::actions();

        $matches = [];
        $importersPath = \Yii::getAlias("@app/importers") . "/";
        foreach (glob($importersPath . "*Importer.php") AS $importFile) {
            if (preg_match ("/\\/([^\\/]+)Importer.php$/", $importFile, $matches)) {
                $importerName = $matches[1];
                $className = "\\app\\importers\\" . $importerName . "Importer";
                $actions[$importerName] = [
                    'class' => $className,
                    'filename' => $this->filename,
                    'modelName' => $this->modelName,
                    'dryRun' => $this->dryRun,
                    'stopOnErrors' => $this->stopOnErrors,
                    'deleteRecords' => $this->deleteRecords
                ];
            }
        }
        return $actions;
    }

    /**
     * Parse the query parameters into the corresponding member variables.
     */
    protected function parseQueryParams() {
        $params = \Yii::$app->request->queryParams;

        if (isset($params['modelName'])) {
            $this->modelName = $params['modelName'];
        }

        if (isset($params['filename'])) {
            $this->filename = $params['filename'];
        }

        if (isset($params['dryRun'])) {
            $this->dryRun = filter_var($params['dryRun'], FILTER_VALIDATE_BOOLEAN);
        }

        if (isset($params['stopOnErrors'])) {
            $this->stopOnErrors = filter_var($params['stopOnErrors'], FILTER_VALIDATE_BOOLEAN);
        }

        if (isset($params['deleteRecords'])) {
            $this->deleteRecords = filter_var($params['deleteRecords'], FILTER_VALIDATE_BOOLEAN);
        }


    }

}
