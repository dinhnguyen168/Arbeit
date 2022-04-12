<?php
namespace app\commands;

use yii\console\Controller;
use yii\console\ExitCode;
use app\dis_migration\Module;
use app\dis_migration\Listvalues;

/**
 * Class ImportDataController
 *
 * Import data from different sources using importers implemented in backend/importers/
 *
 * @package app\commands
 */
class ImportDataController extends Controller
{

    /**
     * Import an uploaded file
     * @param $importClassName which import class should be used (i.e. "Csv" for the class "CsvImporter" in directory "importer")
     * @param $fileId ID of the file in the archive_file table
     * @param null $modelName (Optional depending on the import class): Name of the data model (i.e. "CoreSection")
     */
    public function actionImportFromArchiveFile ($importClassName, $fileId, $modelName = null) {
        $importClass = $this->getImportClass($importClassName);
        if ($importClass) {
            $importer = new $importClass;
            $importer->importFromArchiveFile($fileId, $modelName);
        }
    }

    /**
     * Create the full class name for an importer
     * @param $importClassName
     * @return null|string
     */
    protected function getImportClass ($importClassName) {
        $class = "app\\importers\\" . $importClassName . "Importer";
        if (class_exists($class))
            return $class;
        else {
            echo "Import class not found: " . $class . "\n";
            return null;
        }
    }

}
