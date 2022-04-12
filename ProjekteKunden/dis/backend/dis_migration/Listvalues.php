<?php

namespace app\dis_migration;

use yii\helpers\Console;

class Listvalues
{

    public static function getListsFromSqlServer() {
        $lists = [];
        $oDbSchema = \Yii::$app->importDb->getSchema();
        foreach ($oDbSchema->tableNames AS $cTableName) {
            if (preg_match ("/^L_/", $cTableName) && !preg_match ("/_M\d?$/", $cTableName)) {
                $lists[] = preg_replace("/^L_/", "", strtoupper($cTableName));
            }
        }
        return $lists;
    }

    public static function importAllFromSqlServer($bUpdate) {
        foreach (static::getListsFromSqlServer() as $list) {
            static::importFromSqlServer($list, $bUpdate);
        }
    }

    public static function importFromSqlServer($cList, $bUpdate) {
        $oController = \Yii::$app->controller;
        $cLIST = "List " . $oController->ansiFormat($cList, Console::BOLD);
        $cERROR = $oController->ansiFormat("ERROR", Console::FG_RED) . ": " . $cLIST ;
        $cWARNING = $oController->ansiFormat("WARN ", Console::FG_YELLOW) . ": " . $cLIST;

        $cList = preg_replace("/^L_/", "", strtoupper($cList));
        $cImportTable = "L_" . $cList;
        $cTable = "list_values";

        $oDbSchema = \Yii::$app->importDb->getSchema();
        $oSchema = $oDbSchema->getTableSchema($cImportTable);
        if ($oSchema) {
            $nImportRows = (new \yii\db\Query())
                ->from($cImportTable)
                ->count("*", \Yii::$app->importDb);

            if ($nImportRows == 0) {
                echo "Import " . $cLIST . $oController->ansiFormat(" is empty", Console::FG_GREEN) ."\n";
                return true;
            }

            $nExistingRows = (new \yii\db\Query())
                ->select("listname")
                ->from($cTable)
                ->andWhere (["listname" => $cList])
                ->count("*");


            if ($nExistingRows > 0 && !$bUpdate) {
                echo $cERROR . " already exists with " . $nExistingRows . " values!\n";
                return;
            }

            $aMatchColumns = [];
            foreach ($oSchema->columns as $i => $oColumn) {
                if (strtoupper($oColumn->name) == $cList)
                    $aMatchColumns[$oColumn->name] = "display";
                elseif (strtoupper($oColumn->name) == 'REMARKS')
                    $aMatchColumns[$oColumn->name] = "remark";
                elseif (strtoupper($oColumn->name) == 'LIST_ID')
                    $aMatchColumns[$oColumn->name] = "sort";
            }

            $oFirstColumn = reset($oSchema->columns);
            if (!isset($aMatchColumns[$oFirstColumn->name]) && !in_array("display", $aMatchColumns)) {
                $aMatchColumns[$oFirstColumn->name] = "display";
            }

            $aUnknownColumns = [];
            foreach ($oSchema->columns as $oColumn) {
                if (!isset($aMatchColumns[$oColumn->name])) {
                    if (!in_array("display", $aMatchColumns)) {
                        $aMatchColumns[$oColumn->name] = "display";
                        echo $cWARNING . ": Using column " . $oColumn->name . " as display value\n";
                    } else {
                        $aUnknownColumns[] = $oColumn->name;
                    }
                }
            }
            if (sizeof($aUnknownColumns) > 0) {
                echo $cWARNING . " Unknown columns " . json_encode($aUnknownColumns) . " !\n";
            }

            if (!in_array("display", $aMatchColumns)) {
                echo $cERROR . " cannot be imported: Missing column with display value!\n";
                return false;
            }


            $aColumnsSQL = [];
            foreach ($aMatchColumns as $cSrc => $cTrg) {
                $aColumnsSQL[] = $cSrc . " AS " . $cTrg;
            }

            echo "Import " . $cLIST . " ";

            $nRow = 0;
            foreach ((new \yii\db\Query())
                         ->select($aColumnsSQL)
                         ->from($cImportTable)
                         ->batch(100, \Yii::$app->importDb) as $aRows) {
                set_time_limit(100);
                foreach ($aRows AS $aRow) {
                    $aRow = ["listname" => $cList] + $aRow;

                    try {
                        \Yii::$app->db->createCommand()->insert($cTable, $aRow)->execute();
                        echo ".";
                    }
                    catch (\yii\db\IntegrityException $e) {
                        if (!$bUpdate) echo "\n" . $cWARNING . ": Skip duplicate record " . json_encode($aRow) . "!\n";
                    }

                    flush();
                    $nRow++;
                }
                if ($nRow >= $nImportRows) break;
            }

            $nExistingRows = (new \yii\db\Query())
                ->select("listname")
                ->from($cTable)
                ->andWhere (["listname" => $cList])
                ->count("*");

            if ($nExistingRows == $nImportRows)
                echo $oController->ansiFormat("success", Console::FG_GREEN) . "\n";
            else
                echo $oController->ansiFormat("incomplete: ", Console::FG_YELLOW) . ($nImportRows - $nExistingRows) . " missing rows\n";
        }
        else
            echo $cERROR . ": Import table " . $cImportTable . " not found\n";

    }

}

