<?php
namespace app\commands;

use app\components\templates\ModelTemplate;
use Yii;
use yii\base\Exception;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;
use app\migrations\Migration;

/**
 * These commands are used to fix issues in the data structure and to automatically generate/update models and forms.
 */
class FixDataController extends Controller
{

    /**
     * Updates the combined_ids of a branch in the database, starting with one record
     * @param string $modelClass Class of the model, i.e. "CoreCore"
     * @param integer $id ID (not combined id) of the model to start with
     */
    public function actionUpdateCombinedIds($modelClass, $id)
    {
        $class = "\\app\\models\\" . $modelClass;
        echo "update Models ...\n";
        $countUpdated = $this->_updateCombinedIDs($class, $id);
        if ($countUpdated == 0)
            $this->stdout("... no corrections applied.\n", Console::FG_GREEN);
        else
            $this->stdout(" ... in total " . $countUpdated . " records corrected\n", Console::FG_YELLOW);

    }

    /**
     * @param $class
     * @param $ids
     * @param string $searchInColumn
     */
    protected function _updateCombinedIDs($class, $ids, $searchInColumn = "id", $indent = 0) {
        $tableName = $class::tableName();
        $foundIDs = [];
        $countUpdated = 0;
        set_time_limit(90);
        $firstOutput = true;

        $combinedIdBehaviorFound = false;
        foreach ($class::find()->where(['IN', $searchInColumn, $ids])->batch() as $models) {
            set_time_limit(90);
            foreach ($models AS $model) {
                $foundIDs[] = $model->id;
                $combinedIdBehaviorFound = false;
                foreach ($model->behaviors as $name => $behavior) {
                    if ($behavior instanceof \app\behaviors\CombinedIdBehavior)
                        $combinedIdBehaviorFound = true;
                    else
                        $model->detachBehavior($name);
                }

                if ($combinedIdBehaviorFound) {
                    if ($firstOutput) {
                        $this->stdout(str_repeat(" ", $indent * 3) . "- " . $tableName . " ...");
                        $firstOutput = false;
                    }
                    $model->trigger(\app\behaviors\CombinedIdBehavior::EVENT_RECALCULATE);
                    if (sizeof($model->dirtyAttributes)) {
                        if (!$model->save()) {
                            \Yii::error($tableName . "::updateDescendantsCombinedIds() Cannot update combined_id on record " . $model->id);
                            $this->stdout("\n" . $tableName . "::updateDescendantsCombinedIds() Cannot update combined_id on record " . $model->id . "\n", Console::FG_RED);
                        } else
                            $countUpdated++;
                    }
                }
            }
        }

        if ($countUpdated == 0) {
            if ($combinedIdBehaviorFound) $this->stdout("\n");
        }
        else
            $this->stdout(" corrected " . $countUpdated . " records\n", Console::FG_YELLOW);

        foreach ($class::findChildModelClasses($class::getModelFullName()) as $childClass) {
            $tableSchema = $childClass::getTableSchema();
            foreach ($tableSchema->foreignKeys AS $foreignKey) {
                if ($foreignKey[0] == $tableName) {
                    unset ($foreignKey[0]);
                    $column = array_keys($foreignKey)[0];
                    if ($foreignKey[$column] == "id") {
                        $countUpdated += $this->_updateCombinedIDs($childClass, $foundIDs, $column, $indent+1);
                        break;
                    }
                }
            }
        }

        return $countUpdated;
    }


    /**
     * Compares the database structure of the model template with that of the database
     * @param $model Name of the model to check. Use "ALL" or "*" for all models.
     * @param $fix Try to fix the data structure. Enter "fix" to apply.
     * @param $delete Try to delete columns,foreignkeys, tables. Enter "delete" to apply.
     */
    public function actionUpdateDatabaseStructure ($model = null, $fix = "", $delete = "") {
        $fix = (strtolower($fix) == "fix");
        $delete = (strtolower($delete) == "delete");

        if ($model == null || in_array(strtolower($model), ["all", "*"])) {
            $models = \Yii::$app->templates->getModelTemplates();
            echo "Validate/Update database structure of models:\n";
            foreach ($models as $model) {
                echo "- " . $model->getFullName() . "\n";
            }
            echo "\n";
        }
        else {
            $model = \Yii::$app->templates->getModelTemplate($model);
            if ($model)
                $models = [$model];
            else {
                echo "Model '" . $model . "' not found\n\n";
                return;
            }
            echo "Validate database structure of model '" . $model->name . "'\n\n";
        }


        $moduleDbPrefixes = [];
        $tables = [];
        $fixModels = [];
        foreach ($models as $model) {
            $warnings = [];
            if (!$model->validateDatabaseStructure($warnings)) {
                echo "" . $model->getFullName() . " (Table '" . $model->table . "'):\n";
                foreach ($warnings as $warning) {
                    echo "  - " . $warning . "\n";
                }
                echo "\n";
                $fixModels[] = $model;
            }
            $moduleDbPrefixes[] = strtolower($model->module) . "_";
            $tables[] = $model->table;

            $warnings = [];
            if(isset($model->relations)) {
                foreach ($model->relations as $relation) {
                    if (isset($relation->relationType) && $relation->relationType == 'nm') {
                        if(!$relation->oppositionRelation) {
                            $connectionTemplateData = $model->generateConnectionModelTemplateData($relation);
                            $connectionModel = \Yii::createObject(ModelTemplate::className());
                            $connectionModel->load($connectionTemplateData,'');
                            if (!$connectionModel->validateDatabaseStructure($warnings)) {
                                echo "" . $connectionModel->getFullName() . " (Table '" . $connectionModel->table . "'):\n";
                                foreach ($warnings as $warning) {
                                    echo "  - " . $warning . "\n";
                                }
                                echo "\n";
                                $fixModels[] = $connectionModel;
                            }
                            $moduleDbPrefixes[] = strtolower($connectionModel->module) . "_";
                            $tables[] = $connectionModel->table;
                        }
                    }
                }
            }
        }

        if ($fix && sizeof($fixModels)) {
            echo "Trying to fix the above problems ...\n";
            foreach ($fixModels as $model) {
                $migration = new Migration();
                echo "Fix model " . $model->getFullName() . " (Table '" . $model->table . "'):\n";
                try {
                    $model->validateDatabaseStructure($warnings, $migration, $delete, false);
                }
                catch(\Exception $e) {
                    echo"Error fixing structure: " . $e->getMessage() . "\n";
                }
                echo "\n";
            }
            echo "\n";

            foreach ($fixModels as $model) {
                $migration = new Migration();
                echo "Fix the relations of model " . $model->getFullName() . " (Table '" . $model->table . "'):\n";
                try {
                    $model->validateDatabaseStructure($warnings, $migration, $delete, true);
                }
                catch(\Exception $e) {
                    echo"Error fixing relations: " . $e->getMessage() . "\n";
                }
                echo "\n";
            }
            echo "\n";
        }

        if (sizeof($models) > 1) {
            $moduleDbPrefixes = array_unique($moduleDbPrefixes);
            $schema = \Yii::$app->db->getSchema();
            foreach ($schema->tableNames as $tableName) {
                if (!in_array($tableName, $tables)) {
                    foreach ($moduleDbPrefixes as $module) {
                        if (substr($tableName, 0, strlen($module)) == $module) {
                            echo "No model template for database table '" . $tableName . "'. Delete it?\n";
                            if ($fix && $delete) {
                                $migration = new Migration();
                                echo "Delete table '" . $tableName . "':\n";
                                try {
                                    $migration->dropTable($tableName);
                                } catch (\Exception $e) {
                                    echo "Error deleting table.\n";
                                }
                                echo "\n";
                            }
                        }
                    }
                }
            }
        }

        echo "\n";
    }


    /**
     * Generates/updates models based on the template files in backend/dis_templates/forms
     * If files do exist they will be reported and only overwritten if parameter overwrite is set.
     * @param $model Name of the model to update/check. Use "ALL" or "*" for all models.
     * @param int $overwrite Overwrite existing files? (1=yes, 0=no)
     */
    public function actionUpdateModels ($model = null, $overwrite = 0) {
        $hintOverwrite = false;
        $anyUpdates = false;

        if ($model == null || in_array(strtolower($model), ["all", "*"])) {
            $models = \Yii::$app->templates->getModelTemplates();
            echo "Validate/Update PHP files of models:\n";
            foreach ($models as $model) {
                echo "- " . $model->getFullName() . "\n";
            }
            echo "\n";
        }
        else {
            $model = \Yii::$app->templates->getModelTemplate($model);
            if ($model)
                $models = [$model];
            else {
                echo "Model '" . $model . "' not found\n\n";
                return;
            }
            echo "Validate PHP files of model '" . $model->name . "'\n\n";
        }

        usort($models, function ($x, $y) {
            if ($x->fullName === 'ArchiveFile') {
                return +1;
            }
            return 0;
        });

        foreach ($models as $modelTemplate) {
            echo $modelTemplate->name . ":\n";
            if (!$modelTemplate->validate()) {
                echo "Errors in model template '" . $modelTemplate->fullName . "':\n";
                echo print_r($modelTemplate->getErrorSummary(true), true) . "\n\n";
                continue;
            }

            $generator = new \app\modules\cg\generators\DISModel\Generator([
                'templateName' => $modelTemplate->fullName
            ]);
            $codeFiles = $generator->generate();
            $echoTemplate = true;
            foreach ($codeFiles as $codeFile) {
                if ($codeFile->operation == \yii\gii\CodeFile::OP_SKIP) continue;

                if ($echoTemplate) {
                    echo $modelTemplate->name . ":\n";
                    $echoTemplate = false;
                }
                $operation = ($codeFile->operation == \yii\gii\CodeFile::OP_CREATE ? "create" : "overwrite");
                $mustOverwrite = ($codeFile->operation == \yii\gii\CodeFile::OP_OVERWRITE);
                $path = str_replace(realpath(\Yii::getAlias("@app/../")), "", $codeFile->path);
                echo " - " . $operation . " " . $path . ($overwrite || !$mustOverwrite ? " ..." : " ?\n");
                if ($overwrite || !$mustOverwrite) {
                    $result = $codeFile->save();
                    if ($result === true)
                        echo " done.\n";
                    else
                        echo "\n   ERROR: " . $result . "\n";
                }
                $hintOverwrite = $hintOverwrite || $mustOverwrite;
                $anyUpdates = true;
            }
        }

        if (!$overwrite && $hintOverwrite) {
            echo "\nProvide parameter to overwrite existing files\n\n";
            return 1;
        }
        else if (!$anyUpdates) {
            echo "\nAll files are up to date.\n\n";
            return 0;
        }

    }



    /**
     * Generates/updates forms based on the template files in backend/dis_templates/forms
     * If files do exist they will be reported and only overwritten if parameter overwrite is set.
     * @param $model Name of the form to update/check. Use "ALL" or "*" for all models.
     * @param int $overwrite Overwrite existing files? (1=yes, 0=no)
     */
    public function actionUpdateForms ($form = null, $overwrite = 0) {
        $hintOverwrite = false;
        $anyUpdates = false;
        $errors = 0;
        $forms = [];

        if ($form == null || in_array(strtolower($form), ["all", "*"])) {
            $forms = \Yii::$app->templates->getFormTemplates();
            echo "Validate/Update files of forms:\n";
            foreach ($forms as $form) {
                echo "- " . $form->name . "\n";
            }
            echo "\n";
        }
        else {
            $form = \Yii::$app->templates->getFormTemplate($form);
            if ($form)
                $forms = [$form];
            else {
                echo "Model '" . $form . "' not found\n\n";
                return;
            }
            echo "Validate files of form '" . $form->name . "'\n\n";
        }

        foreach ($forms as $formTemplate) {
            $dataModel = ModelTemplate::find($formTemplate->dataModel);
            if (!$dataModel) {
                echo "Form '" . $formTemplate->name . "': data model '" . $formTemplate->dataModel . "' is missing!\n";
                continue;
            }

            if (!$formTemplate->validate()) {
                echo "Errors in form template '" . $formTemplate->name . "' of model template '" . $dataModel->fullName . "':\n";
                echo "- " . implode("\n- ", $formTemplate->getErrorSummary(true)) . "\n\n";
                $errors++;
                continue;
            }

            $generator = new \app\modules\cg\generators\DISForm\Generator([
                'templateName' => $formTemplate->name
            ]);
            $codeFiles = $generator->generate();
            $echoTemplate = true;
            foreach ($codeFiles as $codeFile) {
                if ($codeFile->operation == \yii\gii\CodeFile::OP_SKIP) continue;

                if ($echoTemplate) {
                    echo $formTemplate->name . ":\n";
                    $echoTemplate = false;
                }
                $operation = ($codeFile->operation == \yii\gii\CodeFile::OP_CREATE ? "create" : "overwrite");
                $mustOverwrite = ($codeFile->operation == \yii\gii\CodeFile::OP_OVERWRITE);

                $path = str_replace(realpath(\Yii::getAlias("@app/../")), "", $codeFile->path);

                if (preg_match("/src\\/forms\\/.*\\.vue$/", $path)) {
                    echo " - Skip custom form '" . $path . "' \n";
                    continue;
                }

                echo " - " . $operation . " " . $path . ($overwrite || !$mustOverwrite ? " ..." : " ?\n");
                if ($overwrite || !$mustOverwrite) {
                    $result = $codeFile->save();
                    if ($result === true)
                        echo " done.\n";
                    else
                        echo "\n   ERROR: " . $result . "\n";
                }
                $hintOverwrite = $hintOverwrite || $mustOverwrite;
                $anyUpdates = true;
            }
        }

        if ($errors > 0) echo "\n" . $errors . " form templates have errors\n\n";
        if (!$overwrite && $hintOverwrite)
            echo "\nProvide parameter to overwrite existing files\n\n";
        else if (!$anyUpdates)
            echo "\nAll files are up to date.\n\n";
    }

    /**
     * Update the IGSN numbers of a model (i.e. CoreCore) or all models
     * @param string $modelName
     */
    public function actionUpdateIgsnNumbers ($modelName = "") {
        if ($modelName) {
            \Yii::$app->igsn->updateModelRecords($modelName, 1);
            \Yii::$app->igsn->updateModelRecords($modelName, 2);
        }
        else
            \Yii::$app->igsn->updateAllModels();
        echo "\n";
    }
}
