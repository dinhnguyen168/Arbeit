<?php

namespace app\commands;

use yii\console\Controller;
use yii\console\ExitCode;
use app\dis_migration\Module;
use app\dis_migration\Listvalues;

/**
 * Class DisMigrateController
 *
 * Functions to import tables and data from an old DIS installation on a Microsoft SQL server.
 * The steps are:
 * - List the available modules (= table sets): show-import-modules
 * - Import the data structure of one or more modules (= table sets): import-data-structure
 * - Create the data tables in the MySQL database server: create-data-tables
 * - Import the records from the SQL server into the MySQL server: import-data
 *
 * - Import list values: import-list-values
 * @package app\commands
 */
class DisMigrateController extends Controller
{

    /**
     * Show modules (table sets) available on the Microsoft SQL server
     */
    public function actionShowImportModules() {
        $moduleNames = Module::getModulesFromImportServer();
        foreach ($moduleNames as $moduleName) {
            echo $moduleName . "\n";
        }
        echo "";
    }


    /**
     * Import data structure of one or more modules (table sets) using meta data table the Microsoft SQL server
     * @param $cImportModule Name of one or more modules (table sets); a data table for every module must exist, i.e. "EXP_CORE_META"
     */
    public function actionImportDataStructures($cModule) {
        $aModules = func_get_args();
        Module::sortModules($aModules);
        foreach ($aModules as $cModule) {
            $oModule = new Module();
            $oModule->loadFromImportServer("EXP_" . strtoupper($cModule) . "_META");
            $oModule->saveModels();
        }
        return ExitCode::OK;
    }

    /**
     * Creates missing data tables for one or more modules (table sets)
     * @param string $cModule Name of one or more modules to create tables for; leave empty to create for all modules
     */
    public function actionCreateDataTables($cModule = "") {
        $aModules = func_get_args();
        if (sizeof($aModules) == 0) $aModules = Modules::getExistingModules();
        Module::sortModules($aModules);

        foreach ($aModules as $cModule) {
            $oModule = new Module($cModule);
            $oModule->generateTables();
        }
        return ExitCode::OK;
    }

    /**
     * Import data from the import Microsoft SQL server for one or more modules (table sets); leave empty for all
     * @param string $cModule Module to import data for; leave empty to import all modules
     */
    public function actionImportData($cModule = "") {
        $aModules = func_get_args();
        if (sizeof($aModules) == 0) $aModules = Modules::getExistingModules();
        Module::sortModules($aModules);

        foreach ($aModules as $cModule) {
            $oModule = new Module($cModule);
            $oModule->importData();
        }
        return ExitCode::OK;
    }




    /**
     * Show listvalues lists available on the Microsoft SQL server
     */
    public function actionShowImportListvalues() {
        $lists = Listvalues::getListsFromSqlServer();
        foreach ($lists as $list) {
            echo $list . "\n";
        }
        echo "";
    }


    /**
     * Import listvalues (tables "L_*") from the import Microsoft SQL server
     * @param string $cList Name of listvalue table (Prefix "L_" can be ommitted); Enter "all" to import all lists
     * lists are imported
     */
    public function actionImportListValues($cList, $cUpdate = "false") {
        $cUpdate = strtolower($cUpdate);
        $bUpdate = ($cUpdate == "update") || filter_var($cUpdate, FILTER_VALIDATE_BOOLEAN);
        if ($cList == "all") {
            echo "import all listvalue tables ...\n";
            Listvalues::importAllFromSqlServer($bUpdate);
        }
        else {
            $cList = preg_replace("/^L_/", "", strtoupper($cList));
            Listvalues::importFromSqlServer($cList, $bUpdate);
        }
        echo "\n";
        return ExitCode::OK;
    }

}
