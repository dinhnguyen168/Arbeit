<?php

namespace app\dis_migration;

use \app\components\templates\ModelTemplateIndex as Index;
use \app\components\templates\ModelTemplateRelations as Relation;


class Model extends \app\components\templates\ModelTemplate
{
    private $oImportSchema;
    private $aImportForeignKeys;


    public function setTableName($cModule, $cTableName) {
        $this->module = ucfirst($cModule);
        $this->table = lcfirst($cModule) . "_" . $this->convertTableName($cTableName);
        $this->name = static::getModelName($cTableName);
        $this->importTable = "EXP_" . strtoupper($cModule) . "_" . $cTableName;
    }


    public static function getModelName($cDataTable)
    {
        return ucfirst(preg_replace_callback("/_([a-z])/", function($matches){
            return strtoupper($matches[1]);
        }, strtolower($cDataTable)));
    }

    public function getFilePath() {
        return $this->_filePath;
    }

    public function importDataStructure($oModule, $cTemplateTableName)
    {
        $oDbSchema = \Yii::$app->importDb->getSchema();
        $this->oImportSchema = $oDbSchema->getTableSchema($this->importTable);
        if ($this->oImportSchema) {
            $this->aImportForeignKeys = array_merge([], $this->oImportSchema->foreignKeys);

            $cRemovePrefix = preg_replace("/_META$/", "", $cTemplateTableName) . "_";
            $oQuery = (new \yii\db\Query())
                ->select('*')
                ->from($cTemplateTableName)
                ->andWhere(['COMPONENT' => preg_replace("/^" . $cRemovePrefix . "/", "", $this->importTable)]);

            foreach ($oQuery->all(\Yii::$app->importDb) AS $aRecord) {
                $column = new Column($this);
                if ($column->importDataStructure($aRecord, $this->oImportSchema)) {
                    $this->addColumn($column);
                };
            }
        }
        else
            echo "Import table " . $this->importTable . " not found\n";
    }

    public function importDataStructureRelations($oModule)
    {
        // Get primary key columns and sort them by program, expedition, site, hole, ...
        $primaryKeyColumns = $this->getPrimaryKeyColumns();

        // Create auto increment colum if none exists
        $autoIncColumn = $this->getAutoIncrementColumn();
        if ($autoIncColumn == null) {
            $autoIncColumn = $this->createIdColumn();
        }
        else if ($autoIncColumn->name !== "id") {
            $autoIncColumn->description .= ($autoIncColumn->description > "" ? "; " : "") . "renamed from " . $autoIncColumn->name;
            $autoIncColumn->name = 'id';
        }
        $autoIncColumn->required = false;
        $this->addColumn($autoIncColumn, 0);

        // Create primary index for auto increment column
        $index = new Index($this);
        $index->name = $autoIncColumn->name;
        $index->type = "PRIMARY";
        $index->columns = [$autoIncColumn->name];
        $this->addIndex($index);

        // Create column to easily view the content based identifier
        if (sizeof($primaryKeyColumns) > 1) {
            $oCombinedColumn = $this->createCombinedKeyColumn($primaryKeyColumns);
            if ($oCombinedColumn) $this->addColumn($oCombinedColumn, 1);

            // Remove unused content based primary keys that where use for foreign key relations
            for ($i=0; $i<sizeof($primaryKeyColumns)-2; $i++) {
                unset($this->columns[$primaryKeyColumns[$i]->name]);
            }
        }

        // Find parent table and old (content based) foreign key relation
        $parentModel = null;
        $aLocalColumns = [];
        $aForeignColumns = [];
        $oParentModel = null;

        if (sizeof($this->aImportForeignKeys) > 0) {
            // find parent table and relation by foreign keys
            $aImportForeignKey = array_values($this->aImportForeignKeys)[0];
            $cParentTable = $this->convertTableName($aImportForeignKey[0]);
            $oParentModel = $oModule->findModelByTable($cParentTable);
            if ($oParentModel) {
                foreach ($aImportForeignKey as $cLocal => $cRemote) {
                    if ($cLocal !== 0) {
                        $oLocalColumn = $this->findColumnByImportSource($cLocal);;
                        $oForeignColumn = $oParentModel->findColumnByImportSource($cRemote);
                        if ($oLocalColumn && $oForeignColumn) {
                            $aLocalColumns[] = $oLocalColumn;
                            $aForeignColumns[] = $oForeignColumn;
                        }
                    }
                }
            }
        }

        if (sizeof($aForeignColumns) == 0 && sizeof($primaryKeyColumns) > 0) {
            // find parent table and relation by primary key columns
            if (sizeof($primaryKeyColumns) > 1) {
                $oParentColumn = $primaryKeyColumns[sizeof($primaryKeyColumns) - 2];
                $oParentModel = $oModule->findModelByName(ucfirst($oParentColumn->name));
                if ($oParentModel) {
                    for ($i=0; $i<sizeof($primaryKeyColumns)-2; $i++) {
                        $oForeignColumn = $oParentModel->findColumn($primaryKeyColumns[$i]->name);
                        if ($oForeignColumn) {
                            $aLocalColumns[] = $primaryKeyColumns[$i];
                            $aForeignColumns[] = $oForeignColumn;
                        }
                    }
                }
            }
            else if (($nIndex = array_search(strtolower($this->name), Module::$aSortModels)) > 0) {
                $oParentModel = $oModule->findModel(Module::$aSortModels[$nIndex-1]);
                if ($oParentModel) {
                    $oForeignColumn = $oParentModel->findColumn($primaryKeyColumns[0]->name);
                    if ($oForeignColumn) {
                        $aLocalColumns[] = $primaryKeyColumns[0];
                        $aForeignColumns[] = $oForeignColumn;
                    }
                }
            }
        }


        if ($oParentModel) {
            // create column with parent id
            $this->parentModel = $oParentModel->module . $oParentModel->name;
            $oParentReferenceColumn = $this->createParentReferenceColumn($oParentModel, $aLocalColumns, $aForeignColumns);
            $this->addColumn($oParentReferenceColumn, 1);

            $oParentIdColumn = $oParentModel->getAutoIncrementColumn();
            if ($oParentIdColumn == null) {
                $oParentIdColumn = $oParentModel->getPrimaryKeyColumn();
            }

            // create foreign key to parent table
            $oRealtion = new Relation($this);
            $oRealtion->name = $this->table . "__" . $oParentModel->table . "__parent";
            $oRealtion->foreignTable = $oParentModel->table;
            $oRealtion->localColumns = [$oParentReferenceColumn->name];
            $oRealtion->foreignColumns = [$oParentIdColumn->name];
            $this->addRelation($oRealtion);

            // create index for foreign key columns
            $index = new Index($this);
            $index->name = $oParentReferenceColumn->name;
            $index->type = "KEY";
            $index->columns = [$oParentReferenceColumn->name];
            $this->addIndex($index);

            // remove all primary key columns that do exist in parent table
            $aParentColumnNames = array_keys($oParentModel->columns);
            for ($i=sizeof($this->columns)-1; $i>=0; $i--) {
                $column = array_values($this->columns)[$i];
                if ($column->primaryKey && $column->name !== "id") {
                    if (in_array($column->name, $aParentColumnNames)) {
                        unset($this->columns[$column->name]);
                    }
                }

                $column->primaryKey = ($column == $autoIncColumn);
            }

            // move remaining primary key columns to the front of the new table
            $aRemainingColumnNames = array_keys($this->columns);
            for ($i=sizeof($primaryKeyColumns)-1; $i>=0; $i--) {
                $column = $primaryKeyColumns[$i];
                if (in_array($column->name, $aRemainingColumnNames))
                    $this->addColumn($column, 2);
                else
                    array_splice($primaryKeyColumns, $i, 1);
            }

            // Remove Columns identical to parent table name (i.e. column "core" in model "section")
            if (isset($this->columns[lcfirst($oParentModel->name)])) {
                $removeColumn = $this->columns[lcfirst($oParentModel->name)];
                unset($this->columns[lcfirst($oParentModel->name)]);
                for ($i=0; $i<sizeof($primaryKeyColumns); $i++) {
                    if ($primaryKeyColumns[$i] == $removeColumn) {
                        array_splice($primaryKeyColumns, $i, 1);
                        break;
                    }
                }
            }


            if (sizeof($primaryKeyColumns) > 0) {
                // create unique index for remaining primary key columns
                $index = new Index($this);
                $index->name = $oParentReferenceColumn->name;
                foreach ($primaryKeyColumns as $column) {
                    $index->name .= "__" . $column->name;
                }
                $index->type = "UNIQUE";
                $index->columns = [];
                foreach (array_merge([$oParentReferenceColumn], $primaryKeyColumns) as $column) {
                    $index->columns[] = $column->name;
                }
                $this->addIndex($index);
            }

        }

    }


    protected function convertTableName($cDataTable)
    {
        return preg_replace("/^EXP_/i", "", strtolower($cDataTable));
    }

    protected function getPrimaryKeyColumns()
    {
        $primaryKeyColumns = [];
        foreach ($this->columns as $column) {
            if ($column->primaryKey) {
                $primaryKeyColumns[] = $column;
            }
        }

        usort($primaryKeyColumns, function ($a, $b) {
            $nA = array_search(strtolower($a->importSource), Module::$aSortModels);
            $nB = array_search(strtolower($b->importSource), Module::$aSortModels);
            if ($nA !== FALSE && $nB !== FALSE)
                return $nA < $nB ? -1 : 1;
            else if ($nB === FALSE)
                return -1;
            else if ($nA === FALSE)
                return 1;
            else
                return 0;
        });

        return $primaryKeyColumns;
    }

    public function addColumn($column, $nOffset = null)
    {
        unset ($this->columns[$column->name]);
        if (is_null($nOffset))
            $this->columns[$column->name] = $column;
        else
            $this->columns = array_slice($this->columns, 0, $nOffset, true) + [$column->name => $column] + array_slice($this->columns, $nOffset, NULL, true);
    }

    public function findColumnByImportSource ($cImportSource) {
        foreach ($this->columns as $column) {
            if ($column->importSource == $cImportSource) {
                return $column;
            }
        }
        return null;
    }

    public function findColumn ($cName) {
        if (isset($this->columns[$cName]))
            return $this->columns[$cName];
        else
            return null;
    }

    public function addRelation($oRealtion)
    {
        $this->relations[$oRealtion->name] = $oRealtion;
    }

    public function addIndex($index)
    {
        $this->indices[$index->name] = $index;
    }


    protected function createIdColumn()
    {
        foreach ($this->columns as $column) {
            $column->primaryKey = false;
        }

        $oIdColumn = new Column($this);
        $oIdColumn->name = "id";
        $oIdColumn->type = "integer";
        $oIdColumn->size = 11;
        $oIdColumn->description = "auto incremented id";
        $oIdColumn->autoInc = true;
        $oIdColumn->required = false;
        $oIdColumn->primaryKey = true;

        return $oIdColumn;
    }


    protected function createCombinedKeyColumn($aKeyColumns)
    {
        $oCombinedColumn = null;
        if (sizeof($aKeyColumns) > 1) {
            $cSource = "";
            $nSize = 0;
            $cDescription = "";
            foreach ($aKeyColumns AS $column) {
                $cImportValue = "[" . $column->importSource . "]";
                if ($column->type !== "string")
                    $cImportValue = "LTRIM(CAST(" . $cImportValue . " AS varchar))";
                else
                    $cImportValue = "LTRIM(" . $cImportValue . ")";
                $cSource .= ($cSource > "" ? " + '_' + " : "") . $cImportValue;
                $nSize += $column->size;
                $cDescription .= ($cDescription > "" ? ", " : "") . $column->name;
            }

            $oCombinedColumn = new Column($this);
            $oCombinedColumn->name = "combined_id";
            $oCombinedColumn->importSource = $cSource;
            $oCombinedColumn->type = "string";
            $oCombinedColumn->size = $nSize;
            $oCombinedColumn->description = "CombinedKey: " . $cDescription . " (Only for viewing)";
        }
        return $oCombinedColumn;
    }

    protected function getPrimaryKeyColumn() {
        foreach ($this->columns AS $column) {
            if ($column->primaryKey) {
                return $column;
            }
        }
        return null;
    }

    protected function getAutoIncrementColumn() {
        foreach ($this->columns AS $column) {
            if ($column->autoInc) {
                return $column;
            }
        }
        return null;
    }



    protected function createParentReferenceColumn($oParentModel, $aLocalColumns, $aRemoteColumns)
    {
        $cParentTable = $oParentModel->table;

        $oIdColumn = new Column($this);
        $oIdColumn->name = preg_replace("/^.+_([^_]+)$/", "$1", $cParentTable) . "_id";

        $cParentIdColumn = "id";
        $cParentRefColumn = 'combined_id';
        $cLocalRefColumn = 'combined_id';

        if (!isset($oParentModel->columns[$cParentRefColumn]) && sizeof($aRemoteColumns) < 2 && sizeof($aRemoteColumns) > 0) {
            $oParentRefColumn = $aRemoteColumns[0];
            $cParentRefColumn = $oParentRefColumn->name;
        }

        if (!isset($this->columns[$cLocalRefColumn]) && sizeof($aLocalColumns) < 2 && sizeof($aLocalColumns) > 0) {
            $oLocalRefColumn = $aLocalColumns[0];
            $cLocalRefColumn = $oLocalRefColumn->name;
        }

        $cSrc = 'return function($aImportedRecord) {' . "\n";
        if ($cLocalRefColumn == "combined_id")
            $cSrc .= '$cParentFilterValue = preg_replace("/_[^_]+$/", "", $aImportedRecord["' . $cLocalRefColumn.  '"]); ' . "\n";
        else
            $cSrc .= '//IMPORTCOLUMN:' . $oLocalRefColumn->importSource . ";\n" . '$cParentFilterValue = $aImportedRecord["' . $oLocalRefColumn->importSource . '"]; ' . "\n";
        $cSrc .= 'return \app\dis_migration\Module::lookupValue("' . $cParentTable . '", "' . $cParentIdColumn . '", ["' . $cParentRefColumn . '" => $cParentFilterValue]);' . "\n";
        $cSrc .= '};';

        $oIdColumn->importSource = $cSrc;
        $oIdColumn->type = "integer";
        $oIdColumn->size = 11;
        $oIdColumn->required = true;
        $oIdColumn->description = "parent id (of table " . $cParentTable . ")";

        return $oIdColumn;
    }


    public function postProcessRelations($oModule) {
        foreach ($this->relations as $oRealtion) {
            $oRealtion->postProcess($oModule, $this);
        }
    }


    public function importData() {
        $cTable = $this->table;

        $nExistingRows = (new \yii\db\Query())
            ->from($this->table)
            ->count("*");

        if ($nExistingRows == 0) {
            $oImportDbSchema = \Yii::$app->importDb->getSchema();
            $this->oImportSchema = $oImportDbSchema->getTableSchema($this->importTable);
            if ($this->oImportSchema) {
                echo "Import data from table " . $this->importTable . " into " . $cTable . " ";

                $aFunctionColumns = [];
                $aColumnsSQL = [];
                $aExtraColumnsSQL = [];
                foreach ($this->columns as $column) {
                    if (is_string($column->importSource) && $column->importSource > "") {
                        if (preg_match("/^return function/", $column->importSource)) {
                            $aMatches = [];
                            if (preg_match ('/IMPORTCOLUMN:(.+);/', $column->importSource, $aMatches)) {
                                $aExtraColumnsSQL[] = $aMatches[1];
                            }
                            $column->importSource = eval($column->importSource);
                            $aFunctionColumns[] = $column;
                        } else
                            $aColumnsSQL[] = $column->importSource . " AS " . $column->name;
                    }
                }

                $nRows = (new \yii\db\Query())
                    ->select($aColumnsSQL)
                    ->from($this->importTable)
                    ->count("*", \Yii::$app->importDb);

                $nRow = 0;
                foreach ((new \yii\db\Query())
                             ->select(array_merge($aColumnsSQL, $aExtraColumnsSQL))
                             ->from($this->importTable)
                             ->batch(100, \Yii::$app->importDb) as $aRows) {
                    set_time_limit(100);
                    foreach ($aRows AS $aRow) {
                        //                    die ("data: " . print_r($aRow, true));

                        foreach ($aFunctionColumns AS $column) {
                            $aRow[$column->name] = call_user_func($column->importSource, $aRow);
                        }

                        foreach ($aExtraColumnsSQL as $extraColumn) {
                            unset($aRow[$extraColumn]);
                        }

                        try {
                            \Yii::$app->db->createCommand()->insert($cTable, $aRow)->execute();
                            echo ".";
                        }
                        catch (\Exception $e) {
                            echo "Error inserting record!\n";
                        }
                        flush();
                        $nRow++;
                    }
                    if ($nRow >= $nRows) break;
                }
                echo "\n\n";
            } else
                echo "import table " . $this->importTable . " not found\n";
        }
        else
            echo "Skip import of table " . $this->table . ": not empty!\n";

    }

}

