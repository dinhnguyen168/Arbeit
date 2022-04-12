<?php

namespace app\dis_migration;

use app\components\templates\ModelTemplate;

class Module
{

    protected static $aSortModules = ["project", "core"];
    public static $aSortModels = ["program_name", "expedition", "site", "hole", "core", "section"];

    protected $name;

    protected $models = [];
    protected $modelsByFullname = [];
    protected $modelsByTablename = [];
    protected $modelsByImportTablename = [];

    public function __construct($name = "") {
        $this->name = ucfirst(strtolower(PREG_REPLACE("/^EXP_(.+)_META$/", "$1", $name)));
    }

    public static function getModulesFromImportServer() {
        $modules = [];
        $dbSchema = \Yii::$app->importDb->getSchema();
        $matches = [];
        foreach ($dbSchema->tableNames as $tableName) {
            if (preg_match("/EXP_(.+)_META$/", $tableName, $matches)) {
                $modules[] = ucfirst(strtolower($matches[1]));
            }
        }
        return $modules;
    }

    public static function getExistingModules() {
        $modules = [];
        foreach (\Yii::$app->templates->getModelTemplates() as $template) {
            if (!in_array($template->module, $modules)) {
                $modules[] = $template->module;
            }
        }
        return array_unique($modules);
    }


    public function loadFromImportServer($cTemplateTableName = "EXP_CORE_META", $aTableNames = [])
    {
        foreach (\Yii::$app->templates->getModelTemplates() as $model) {
            $this->addModel($model);
        }

        $this->name = ucfirst(strtolower(PREG_REPLACE("/^EXP_(.+)_META$/", "$1", $cTemplateTableName)));
        if (is_null($aTableNames) || sizeof($aTableNames) == 0) {
            $aTableNames = [];
            $oCommand = \Yii::$app->importDb->createCommand("SELECT DISTINCT COMPONENT FROM " . $cTemplateTableName);
            foreach ($oCommand->queryAll() as $aRow) {
                $aTableNames[] = $aRow["COMPONENT"];
            }
        }

        foreach ($aTableNames as $cTableName) {
            set_time_limit (60);
            $cFullTableName = $cTableName;
            if (preg_match("/_META$/", $cTemplateTableName)) {
                $cFullTableName = preg_replace("/_META$/", "", $cTemplateTableName) . "_" . $cTableName;
            }

            if (isset ($this->modelsByImportTablename[$cFullTableName])) {
                $model = $this->modelsByImportTablename[$cFullTableName];
                echo "Cannot import " . $model->module . $model->name . ": Json-Template already exists!\n";
            }
            else {
                $model = new Model();
                $model->setTableName ($this->name, $cTableName);
                $model->importDataStructure($this, $cTemplateTableName);
                $this->addModel($model, true);
            }
        }

        $cModule = $this->name;
        uksort($this->models, function ($a, $b) use ($cModule) {
            $a = strtolower(preg_replace("/^" . $cModule . "/i", "", $a));
            $b = strtolower(preg_replace("/^" . $cModule . "/i", "", $b));
            $nA = array_search($a, Module::$aSortModels);
            $nB = array_search($b, Module::$aSortModels);
            if ($nA !== FALSE && $nB !== FALSE)
                return $nA < $nB ? -1 : 1;
            else if ($nB === FALSE)
                return -1;
            else if ($nA === FALSE)
                return 1;
            else
                return 0;
        });

        foreach ($this->models as $model) {
            set_time_limit (60);
            $model->importDataStructureRelations($this);
        }

        foreach ($this->models as $model) {
            if (!$model->validate()) {
                echo "Errors in model " . $model->getFullName() . ":\n";
                foreach ($model->getErrorSummary(false) as $error) {
                    echo "    " . $error . "\n";
                }
            }
        }

    }


    public function saveModels() {
        foreach ($this->models as $model) {
            $model->setTemplateFilePath ($model->getFullName());
            if ($model->save()) {
                echo "Saved Json-Template of model " . $model->getFullName() . "\n";
            }
        }
    }


    public function postProcessRelations() {
        foreach ($this->models as $model) {
            $model->postProcessRelations($this);
        }
    }


    protected function addModel($model, $new = false) {
        $this->modelsByFullname[$model->getFullName()] = $model;
        $this->modelsByTablename[$model->table] = $model;
        $this->modelsByImportTablename[$model->importTable] = $model;
        if ($new) {
            $this->models[$model->getFullName()] = $model;
        }
    }

    protected function removeModel($model) {
        unset($this->models[$model->getFullName()]);
        unset($this->modelsByFullname[$model->getFullName()]);
        unset($this->modelsByTablename[$model->table]);
        unset($this->modelsByImportTablename[$model->importTable]);
    }

    public function findModel($cModel)
    {
        $cRegEx = "/^" . $cModel . "$/i";
        foreach ($this->models as $model) {
            if (preg_match($cRegEx, $model->cName)) return $model;
        }
        foreach ($this->aReferenceModels as $model) {
            if (preg_match($cRegEx, $model->cName)) return $model;
        }
        return null;
    }


    public function findModelByImportTable($cImportTable) {
        if (isset($this->modelsByTablename[$cImportTable])) {
            return $this->modelsByTablename[$cImportTable];
        }
        return null;
    }

    public function findModelByTable($cTable) {
        if (isset($this->modelsByTablename[$cTable])) {
            return $this->modelsByTablename[$cTable];
        }
        return null;
    }

    public function findModelByName($name) {
        foreach ($this->modelsByFullname as $model) {
            if ($model->name == $name) return $model;
        }
        return null;
    }



    public static function lookupValue($cTable, $cLookupColumnName, $aCondition)
    {
        $value = (new \yii\db\Query())
            ->select($cLookupColumnName)
            ->from($cTable)
            ->andWhere($aCondition)
            ->scalar();

        $msg = "lookupValue(" . $cTable . ", " . $cLookupColumnName . ", " . print_r($aCondition, true) . ") = " . $value;
        return $value;
    }


    public function generateTables()
    {
        $oMigration = new \app\migrations\Migration();
        $aCreateForeignKeysForModels = [];

        foreach (\Yii::$app->templates->getModelTemplates() as $model) {
            if ($model->module == $this->name) $this->addModel($model, true);
        }

        foreach ($this->models as $cModel => $model) {
            set_time_limit(60);
            if ($model->generateTable($oMigration)) {
                echo "created data table " . $model->table . "\n";
                $aCreateForeignKeysForModels[] = $model;
            }
        }

        foreach ($aCreateForeignKeysForModels as $model) {
            set_time_limit(60);
            $model->generateForeignKeys($oMigration);
            echo "created foreign keys for table " . $model->table . "\n";
        }

    }


    protected function sortModelsByParent() {
        $sortedModelNames = [];
        $unsortedModels = array_values($this->models);

        while (sizeof($unsortedModels) > 0) {
            for ($i=sizeof($unsortedModels)-1; $i>=0; $i--) {
                $parentModelName = $unsortedModels[$i]->parentModel;
                if (in_array($parentModelName, array_keys($this->models))) {
                    $p = array_search($parentModelName, $sortedModelNames);
                    if ($p !== FALSE) {
                        array_splice($sortedModelNames, $p+1, 0, [$unsortedModels[$i]->getFullName()]);
                        array_splice($unsortedModels, $i, 1);
                    }
                }
                else {
                    $sortedModelNames[] = $unsortedModels[$i]->getFullName();
                    array_splice($unsortedModels, $i, 1);
                }
            }
        }

        usort($this->models, function ($a, $b) use ($sortedModelNames) {
            $nA = array_search($a->getFullName(), $sortedModelNames);
            $nB = array_search($b->getFullName(), $sortedModelNames);
            return $nA < $nB ? -1 : 1;
        });
    }


    public static function sortModules (&$aModules) {
        usort($aModules, function ($a, $b) {
            $nA = array_search(strtolower($a), Module::$aSortModules);
            $nB = array_search(strtolower($b), Module::$aSortModules);
            if ($nA !== FALSE && $nB !== FALSE)
                return $nA < $nB ? -1 : 1;
            else if ($nB === FALSE)
                return -1;
            else if ($nA === FALSE)
                return 1;
            else
                return 0;
        });
    }

    public function importData()
    {
        foreach (\Yii::$app->templates->getModelTemplates() as $model) {
            if ($model->module == $this->name) $this->addModel($model, true);
        }
        $this->sortModelsByParent();

        foreach ($this->models as $model) {
            set_time_limit (60);
            $model->importData();
        }
    }

}

