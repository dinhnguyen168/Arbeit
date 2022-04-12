<?php
namespace app\components\templates;

use app\migrations\Migration;
use Yii;
use yii\db\Exception;
use yii\helpers\Inflector;
use yii\helpers\Json;
use yii\web\ServerErrorHttpException;
use ZipArchive;

/**
 * Class ModelTemplate
 *
 * Describes a model template that can be edited in the template manager.
 * A model template is used to create a data table and the PHP data model files to validate data, etc.
 * Based on a model template you can create form templates (in the template manager).
 *
 * @package app\components\templates
 * @property string fullName model full name composed of module name and model name
 */
class ModelTemplate extends BaseTemplate
{
    /**
     * @var string Path for the json files of existing model templates
     */
    protected static $templatesPath = __DIR__ . '/../../dis_templates/models';

    /**
     * @var string Module (= table set) to which this model template belongs
     */
    public $module;

    /**
     * @var string Name of the data model
     */
    public $name;
    /**
     * @var string Name of the data table
     */
    public $table;

    /**
     * @var string Name of the import table from which the data will be copied using the DisMigrateController on the command line.
     */
    public $importTable;

    /**
     * @var string Name of the parent data model
     */
    public $parentModel;

    /**
     * @var ModelTemplateColumn[] Array of the TemplateFields of the form. When loading a json
     * structure, this is a json array but is converted into an array of objects of class ModelTemplateColumn afterwards.
     */
    public $columns = [];

    /**
     * @var ModelTemplateIndex[] Array of the Indices of the data table. When loading a json structure, this is a json
     * array but is converted into an array of objects of class ModelTemplateIndex afterwards.
     */
    public $indices = [];

    /**
     * @var ModelTemplateRelations[] Array of the Foreign keys of the data table. When loading a json structure, this is a json
     * array but is converted into an array of objects of class ModelTemplateRelation afterwards.
     */
    public $relations = [];

    /**
     * @var ModelTemplateBehavior[] array of behaviors of the model class
     */
    public $behaviors;

    /**
     * @var integer Unix timestamp
     */
    public $createdAt;
    /**
     * @var integer Unix timestamp
     */
    public $modifiedAt;
    /**
     * @var integer Unix timestamp
     */
    public $generatedAt;

    private $_oldAttributes;

    /**
     * {@inheritdoc}
     * @return array
     */
    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_CREATE] = ['module', 'name', 'table', 'parentModel', 'columns', 'indices', 'relations', 'importTable', 'behaviors'];
        $scenarios[self::SCENARIO_UPDATE] = ['parentModel', 'columns', 'indices', 'relations', 'behaviors'];
        $scenarios[self::SCENARIO_DELETE] = [];
        return $scenarios;
    }

    function getFilePath () {
        return $this->_filePath;
    }

    /**
     * {@inheritdoc}
     * @return array
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [['module', 'name', 'table', 'columns', 'indices'], 'required'],
            [['module' ,'name' ,'table' , 'parentModel', 'importTable'], 'string'],
            [['createdAt', 'modifiedAt', 'generatedAt'], 'integer'],
            [['relations', 'behaviors'], 'default', 'value' => []]
        ]);
    }

    public function load($data, $formName = null)
    {
        if (isset($data['foreignkeys']) && !isset($data['relations'])) {
            $data['relations'] = $data['foreignkeys'];
            unset($data['foreignkeys']);
        }
        return parent::load($data, $formName);
    }

    /**
     * Returns the old attribute values.
     * @return array the old attribute values (name-value pairs)
     */
    public function getOldAttributes()
    {
        return $this->_oldAttributes === null ? [] : $this->_oldAttributes;
    }

    /**
     * Sets the old attribute values.
     * All existing old attribute values will be discarded.
     * @param array|null $values old attribute values to be set.
     * If set to `null` this record is considered to be [[isNewRecord|new]].
     */
    public function setOldAttributes($values)
    {
        $this->_oldAttributes = $values;
    }

    public function afterFind()
    {
        parent::afterFind();
        $this->setOldAttributes($this->attributes);
    }

    /**
     * @return array Array of data to be saved.
     */
    protected function getJsonArray() {
        $jsonArray = parent::getJsonArray();
        // Remove empty oldName of columns
        foreach ($jsonArray["columns"] as $name => $jsonColumn) {
            if (empty($jsonColumn["oldName"])) {
                unset($jsonArray["columns"][$name]["oldName"]);
            }
        }
        return $jsonArray;
    }


    /**
     * Returns the attribute values that have been modified since they are loaded or saved most recently.
     *
     * The comparison of new and old values is made for identical values using `===`.
     *
     * If the attribute is a complex object, encoded json will be compared
     *
     * @return array the changed attribute values (name-value pairs)
     */
    public function getDirtyAttributes()
    {
        if ($this->_isNewFile) {
            return [];
        }
        $attributes = [];
        if ($this->getOldAttributes() === null) {
            foreach ($this->getAttributes() as $name => $newValue) {
                $attributes[$name] = $newValue;
            }
        } else {
            foreach ($this->getAttributes() as $name => $newValue) {
                // ignore `behaviors` changes
                if ($name === 'behaviors') {
                    continue;
                }
                if (!array_key_exists($name, $this->getOldAttributes())) {
                    $attributes[$name] = $newValue;
                }
                elseif (in_array($name, ['columns', 'indices', 'relations'])) {
                    $dirtyArrayItems = [];
                    foreach ($this->getOldAttributes()[$name] as $oldItemKey => $oldItemValue) {
                        if (array_key_exists($oldItemKey, $newValue) && Json::encode($newValue[$oldItemKey]) !== Json::encode($oldItemValue)) {
                            $dirtyArrayItems[$oldItemKey] = $newValue[$oldItemKey];
                        }
                    }
                    if (count($dirtyArrayItems)) {
                        $attributes[$name] = $dirtyArrayItems;
                    }
                } elseif ($newValue !== $this->getOldAttributes()[$name]) {
                    $attributes[$name] = $newValue;
                }
            }
        }

        return $attributes;
    }

    public function getDeletedAttributes() {
        if ($this->_isNewFile) {
            return [];
        }
        $oldAttributes = $this->getOldAttributes();
        $attributes = $this->getAttributes();
        return [
            'columns' => array_diff_key($oldAttributes['columns'], $attributes['columns']),
            'indices' => array_diff_key($oldAttributes['indices'], $attributes['indices']),
            'relations' => array_diff_key($oldAttributes['relations'], $attributes['relations']),
        ];
    }

    /**
     * Validates the model template and all columns of the model template.
     * If an error occurs on a colum, the error message is modified.
     * @param null|string[] $attributeNames
     * @param bool $clearErrors
     * @return bool Is the form template valid?
     */
    public function validate($attributeNames = null, $clearErrors = true)
    {
        $valid = parent::validate($attributeNames, $clearErrors);
        $dirtyColumns = isset($this->getDirtyAttributes()['columns']) ? $this->getDirtyAttributes()['columns'] : [];
        /* @var ModelTemplateColumn $columnModel */
        foreach ($this->columns as $key => $columnModel) {
            if (array_key_exists($key, $dirtyColumns) && $dirtyColumns[$key]->isLocked) {
                $valid = false;
                $this->addError('columns.'.$key, "This column is locked and can't be updated");
            } elseif (!$columnModel->validate()) {
                $valid = false;
                foreach ($columnModel->getErrors() as $attribute => $message) {
                    $this->addError('columns.'.$key, "$attribute: $message[0]");
                }
            }
        }

        $dirtyIndices = isset($this->getDirtyAttributes()['indices']) ? $this->getDirtyAttributes()['indices'] : [];
        /* @var ModelTemplateIndex $indexModel */
        foreach ($this->indices as $key => $indexModel) {
            if (array_key_exists($key, $dirtyIndices) && $dirtyIndices[$key]->isLocked) {
                $valid = false;
                $this->addError('indices.'.$key, "This index is locked and can't be updated");
            } elseif (!$indexModel->validate()) {
                $valid = false;
                foreach ($indexModel->getErrors() as $attribute => $message) {
                    $this->addError('indices.'.$key, "$attribute: $message[0]");
                }
            }
        }

        $existingKeysCompare = [];
        foreach ($this->relations as $key => $fkModel) {
            if($fkModel->relationType !== 'nm') {
                $compare = $fkModel->getCompare();
                if (in_array($compare, $existingKeysCompare))
                    unset($this->relations[$key]);
                else
                    $existingKeysCompare[] = $compare;
            }
        }

        $dirtyFKs = isset($this->getDirtyAttributes()['relations']) ? $this->getDirtyAttributes()['relations'] : [];
        /* @var ModelTemplateRelations $fkModel */
        foreach ($this->relations as $key => $fkModel) {
            if (array_key_exists($key, $dirtyFKs) && $dirtyFKs[$key] !== $fkModel && $dirtyFKs[$key]->isLocked) {
                $valid = false;
                $this->addError('foreignkeys.'.$key, "This foreign key is locked and can't be updated");
            } elseif (!$fkModel->validate()) {
                $valid = false;
                foreach ($fkModel->getErrors() as $attribute => $message) {
                    $this->addError('foreignkeys.'.$key, "$attribute: $message[0]");
                }
            }
        }

        /* @var ModelTemplateBehavior $behavior */
        $migrateScenario = false;
        foreach (debug_backtrace() as $backTrace) {
            if (isset($backTrace['file']) && str_contains($backTrace['file'], 'm201030_115123_create_files_from_templates.php')) {
                $migrateScenario = true;
                break;
            }
        }

        if (!$migrateScenario) {
            foreach ($this->behaviors as $key => $behavior) {
                if (!$behavior->validate()) {
                    $valid = false;
                    $className = preg_replace("/^.*\\\\/", "", $behavior->behaviorClass);
                    foreach ($behavior->getErrors() as $attribute => $message) {
                        $this->addError('behavior '.$className.'', "$attribute: $message[0]");
                    }
                }
            }
        }

        return $valid;
    }

    /**
     * Returns the full name (module + name) of the model
     * @return string
     */
    public function getFullName () {
        return Inflector::camelize($this->module . '_' . $this->name);
    }

    /**
     * Returns the full class name of the base model class
     * @return string
     */
    public function getBaseClass () {
        return "\\app\\models\\base\\Base" . $this->getFullName();
    }

    /**
     * Returns the file path of the PHP base model file
     * @return string
     */
    public function getBaseClassFilePath () {
        return Yii::getAlias("@app/models/base") . "/Base" . $this->getFullName() . ".php";
    }

    /**
     * Returns the full class name of the base search model class
     * @return string
     */
    public function getBaseSearchClass () {
        return "\\app\\models\\base\\Base" . $this->getFullName() . "Search";
    }

    /**
     * Returns the file path of the PHP base search model file
     * @return string
     */
    public function getBaseSearchClassFilePath () {
        return Yii::getAlias("@app/models/base") . "/Base" . $this->getFullName() . "Search.php";
    }

    /**
     * Returns the full class name of the model class
     * @return string
     */
    public function getCustomClass () {
        return "\\app\\models\\" . $this->getFullName();
    }

    /**
     * Returns the file path of the PHP model file
     * @return string
     */
    public function getCustomClassFilePath () {
        return Yii::getAlias("@app/models") . "/" . $this->getFullName() . ".php";
    }

    /**
     * Returns the full class name of the search model class
     * @return string
     */
    public function getCustomSearchClass () {
        return "\\app\\models\\" . $this->getFullName() . "Search";
    }

    /**
     * Returns the file path of the PHP search model file
     * @return string
     */
    public function getCustomSearchClassFilePath () {
        return Yii::getAlias("@app/models") . "/" . $this->getFullName() . "Search.php";
    }


    /**
     * Returns the modification time (Unix timestamp) of the file of the PHP base model file
     * @return bool|int
     */
    public function getBaseClassFileModified () {
        $filePath = $this->getBaseClassFilePath();
        if (file_exists($this->getBaseClassFilePath())) {
            return filemtime($this->getBaseClassFilePath());
        }
        return false;
    }

    /**
     * Checks if all required class files exist.
     * @return bool All required class files exist
     */
    public function validateClassFilesExist() {
        return file_exists($this->getBaseClassFilePath())
            && file_exists($this->getBaseSearchClassFilePath())
            && file_exists($this->getCustomClassFilePath())
            && file_exists($this->getCustomSearchClassFilePath());
    }

    /**
     * Returns the modification time (Unix timestamp) of the file of the PHP base search model file
     * @return bool|int
     */
    public function getBaseSearchClassFileModified () {
        $filePath = $this->getBaseSearchClassFilePath();
        if (file_exists($this->getBaseSearchClassFilePath())) {
            return filemtime($this->getBaseSearchClassFilePath());
        }
        return false;
    }

    /**
     * Returns the modification time (Unix timestamp) of the file of the PHP model file
     * @return bool|int
     */
    public function getCustomClassFileModified () {
        if (file_exists($this->getCustomClassFilePath())) {
            return filemtime($this->getCustomClassFilePath());
        }
        return false;
    }

    /**
     * Returns the modification time (Unix timestamp) of the file of the PHP search model file
     * @return bool|int
     */
    public function getCustomSearchClassFileModified () {
        if (file_exists($this->getCustomSearchClassFilePath())) {
            return filemtime($this->getCustomSearchClassFilePath());
        }
        return false;
    }

    /**
     * Returns if the table is already generated in the database
     * @return bool
     */
    public function getIsTableCreated () {
        $tableSchema = \Yii::$app->db->schema->getTableSchema($this->table);
        return ($tableSchema != null);
    }

    /**
     * Returns the time (Unix timestamp), when the table was generated in the database
     * This information was written into the comment field of the database.
     * @return bool|int
     * @throws \yii\db\Exception
     */
    public function getTableGenerationTimestamp () {
        $tableSchema = \Yii::$app->db->schema->getTableSchema($this->table);
        if ($tableSchema != null) {
            $migration = new Migration();
            $sql = $migration->getShowTableStatusStatement();
            $command = \Yii::$app->db->createCommand($sql);
            $command->bindValue(':tableName', $this->table);
            foreach ($command->queryAll() as $row) {
                $comment = $row["Comment"];
                $matches = [];
                if (preg_match("/GENERATED:(.*?);/", $comment, $matches)) {
                    $tableGenerated = new \DateTime($matches[1]);
                    return $tableGenerated->getTimestamp();
                }
            }
        }
        return false;
    }

    public function hasColumn ($name) {
        foreach ($this->columns as $key => $column) {
            if ($key == $name) {
                return true;
            }
        }
        return false;
    }

    public function getFilterDataModels () {
        $customClassName = $this->getCustomClass();
        return call_user_func ([$customClassName, "getFormFilters"]);
    }

    public function getRequiredFilters () {
        if (count($this->filterDataModels) > 0) {
            $keys = array_keys($this->filterDataModels);
            $lastKey = end($keys);
            $lastValue = $this->filterDataModels[$lastKey];
            return [["value" => $lastKey, "as" => $lastValue["ref"], "skipOnEmpty" => $this->isSelfReferencing()]];
        }
        return [];
    }

    public function isSelfReferencing () {
        return $this->parentModel == $this->fullName;
    }

    public function isTableUpdateNeeded () {
        $templateJson = Json::decode(file_get_contents($this->_filePath));
        $backupTemplateJson = Json::decode(file_get_contents($this->getBackupPath()));
        $parentModelChanged = $templateJson['parentModel'] !== $backupTemplateJson['parentModel'];
        $columnsChanged = $templateJson['columns'] !== $backupTemplateJson['columns'];
        $indicesChanged = $templateJson['indices'] !== $backupTemplateJson['indices'];
        $relationsChanged = $templateJson['relations'] !== $backupTemplateJson['relations'];
        return $parentModelChanged || $columnsChanged || $indicesChanged || $relationsChanged;
    }

    /**
     * Deletes the database table only if it has no records.
     * @param Migration|null $migration
     * @return bool Database table has been deleted
     * @throws \yii\db\Exception
     */
    public function dropTableIfEmpty (Migration $migration = null) {
        if (!$this->getIsTableCreated()) {
            return true;
        }
        if ($migration == null) {
            $migration = new Migration();
        }
        $command = Yii::$app->db->createCommand("SELECT COUNT(*) AS rowsNum FROM $this->table");
        $result = $command->queryOne();
        if ($result['rowsNum'] == 0) {
            return $this->dropTable($migration);
        }
        return false;
    }

    /**
     * Deletes the database table
     * @param Migration|null $migration
     * @return bool Database table has been deleted
     */
    private function dropTable (Migration $migration = null) {
        if ($migration == null) {
            $migration = new Migration();
        }

        $this->deleteReferencingForeignKeys($migration);

        ob_start();
        $migration->dropTable($this->table);
        ob_end_clean();
        return true;
    }

    /**
     * Creates the table in the database
     * @param Migration|null $migration
     * @param Boolean $showMessages
     * @return bool The database table has been created
     * @throws \yii\base\NotSupportedException
     */
    public function generateTable (Migration $migration = null, $showMessages = false) {
        if ($migration == null) {
            $migration = new Migration();
        }
        $tableSchema = Yii::$app->db->getSchema()->getTableSchema($this->table);
        if ($tableSchema == null) {
            $migrationColumns = [];
            $tableColumns = array_filter($this->columns, function ($c) { return $c->type != 'pseudo'; });
            foreach ($tableColumns as $column) {
                /* @var $column ModelTemplateColumn */
                $migrationColumn = $column->getMigrationColumn($migration);
                if ($migrationColumn) {
                    $migrationColumns[$column->name] = $migrationColumn;
                }
            }

            if (!$showMessages) ob_start();
            $migration->createTable($this->table, $migrationColumns);
            $migration->addCommentOnTable ($this->table, "GENERATED:" . date("Y-m-d H:i:s") . ";");
            if (!$showMessages) ob_end_clean();
            foreach ($this->indices as $index) {
                /* @var $index ModelTemplateIndex */
                $index->generateIndex($migration);
            }

            return true;
        }
        return false;
    }

    /**
     * @param $modelName
     * @return ModelTemplate[] array of model templates that reference this model with foreign keys
     */
    public function getReferencingModelTemplates() {
        $fullName = $this->getFullName();
        $tableName = $this->table;
        return array_filter(\Yii::$app->templates->getModelTemplates(), static function ($otherModel) use ($fullName, $tableName) {
            return // $otherModel->fullName !== $fullName &&
                count(array_filter(
                    $otherModel->relations,
                    static function ($relation) use ($tableName) {
                        return $relation->foreignTable === $tableName;
                    }
                ));
        });
    }

    /**
     * @param Migration $migration
     */
    private function deleteReferencingForeignKeys(Migration $migration)
    {
        $referencingModelTemplates = $this->getReferencingModelTemplates();
        foreach ($referencingModelTemplates as $referencingModelTemplate) {
            foreach ($referencingModelTemplate->relations as $relation) {
                if ($relation->foreignTable === $this->table) {
                    $relation->dropForeignKey($migration);
                }
            }
        }
        /**
         * Error: Constraint violation - record has related data
         * happens when updating ArchiveFile because the message of the day widget's table has
         * a foreign key to table archive_file and when updating (dropping/creating) ArchiveFile this
         * special case should be taken into consideration
         */
        if ($this->fullName === 'ArchiveFile') {
            ob_start();
            $migration->dropForeignKey('fk-message-image-image', 'image_of_the_day');
            ob_end_clean();
        }
    }

    /**
     * @param Migration $migration
     * @throws ServerErrorHttpException
     */
    private function recreateReferencingForeignKeys(Migration $migration)
    {
        $referencingModelTemplates = $this->getReferencingModelTemplates();
        foreach ($referencingModelTemplates as $referencingModelTemplate) {
            foreach ($referencingModelTemplate->relations as $relation) {
                /**
                 * the second condition is important to avoid errors when creating tables
                 * using migration when installing the application for the first time
                 */
                if ($relation->foreignTable === $this->table && \Yii::$app->db->schema->getTableSchema($referencingModelTemplate->table) !== null) {
                    $relation->generateForeignKey($migration);
                }
            }
        }
        /**
         * Error: Constraint violation - record has related data
         * happens when updating ArchiveFile because the message of the day widget's table has
         * a foreign key to table archive_file and when updating (dropping/creating) ArchiveFile this
         * special case should be taken into consideration
         *
         * the second condition is important to avoid errors when creating tables
         * using migration when installing the application for the first time
         */
        $tableSchema = \Yii::$app->db->schema->getTableSchema('image_of_the_day');
        if ($this->fullName === 'ArchiveFile' && $this->table && $tableSchema !== null) {
            ob_start();
            try {
                $dbFk = $tableSchema->foreignKeys['fk-message-image-image'];
                try {
                    if ($dbFk) {
                        $migration->dropForeignKey('fk-message-image-image', 'image_of_the_day');
                    }
                } catch (\Exception $e) {}
                $migration->addForeignKey('fk-message-image-image', 'image_of_the_day', 'image_id', 'archive_file', 'id', $migration->getRestrict(), "CASCADE");
            } catch (Exception $dbe) {
                throw new ServerErrorHttpException($dbe->getMessage());
            } catch (\Exception $e){
                throw new ServerErrorHttpException('Error creating the FK ' . 'fk-message-image-image');
            }
            ob_end_clean();
        }
    }

    /**
     * Generate the foreign keys for the database table
     * @param Migration $migration
     */
    public function generateForeignKeys (Migration $migration) {
        foreach ($this->relations as $relation) {
            /* @var $relation ModelTemplateRelations */
            $relation->generateForeignKey($migration);
        }
    }

    public function getParentForeignKey () {
        foreach ($this->relations as $relation) {
            if ($relation->getIsParentRelation()) return $relation;
        }
        return null;
    }

    /**
     * Returns the file name for the model template
     * @return string
     */
    protected function fileName()
    {
        return Inflector::camelize("$this->module-$this->name");
    }

    /**
     * Converts the columns, indices and foreign keys from json into the corresponding objects.
     */
    protected function populateAfterLoad()
    {
        foreach ($this->columns as $key => $value) {
            $this->columns[$key] = new ModelTemplateColumn($this, $value);
        }
        foreach ($this->indices as $key => $value) {
            $this->indices[$key] = new ModelTemplateIndex($this, $value);
        }
        foreach ($this->relations as $key => $value) {
            $this->relations[$key] = new ModelTemplateRelations($this, $value);
        }
        if (is_array($this->behaviors)) {
            foreach ($this->behaviors as $key => $value) {
                $this->behaviors[$key] = new ModelTemplateBehavior($this, $value);
            }
        }
    }

    /**
     * Returns an array of the generated PHP files and their modification time stamps.
     * @return array
     */
    public function getGeneratedFiles()
    {
        return [
            [
                'path' => $this->getBaseClassFilePath(),
                'modified' => $this->getBaseClassFileModified()
            ],
            [
                'path' => $this->getBaseSearchClassFilePath(),
                'modified' => $this->getBaseSearchClassFileModified()
            ],
            [
                'path' => $this->getCustomClassFilePath(),
                'modified' => $this->getCustomClassFileModified()
            ],
            [
                'path' => $this->getCustomSearchClassFilePath(),
                'modified' => $this->getCustomSearchClassFileModified()
            ]
        ];
    }

    /**
     * Adds a pseudo field "fullName" (for the conversion of the model template into a json structure)
     * @return array
     */
    public function fields()
    {
        return array_merge(parent::fields(), [
            'fullName'
        ]);
    }

    /**
     * Deletes all generated PHP files
     */
    public function cleanUpGeneratedFiles()
    {
        foreach ($this->getGeneratedFiles() as $file) {
            if (file_exists($file['path'])) unlink($file['path']);
        }
    }

    /**
     * Returns all form templates for this model
     * @return \app\components\templates\FormTemplate[]
     */
    public function getFormTemplates() {
        $formTemplates = [];
        foreach (\Yii::$app->templates->getFormTemplates() as $form) {
            if ($form->dataModel === $this->fullName) {
                $formTemplates[] = $form;
            }
        }
        return $formTemplates;
    }

    /**
     * Returns if user may access the data of this model
     * @return bool User may access data of this model
     */
    public function checkAccess($access = "view") {
        foreach ($this->getFormTemplates() as $form) {
            if ($form->checkAccess()) return true;
        }
        return false;
    }

    /**
     * Deletes the database table and all form templates for this model
     * @return bool|mixed The model template, the database table and the form models have been deleted.
     * @throws ServerErrorHttpException
     * @throws \yii\db\Exception
     */
    public function beforeDelete()
    {
        ob_start();
        if ($this->getIsTableCreated()) {
            // check if this model has forms
            if (sizeof($this->getFormTemplates()) > 0) {
                throw new ServerErrorHttpException(sprintf('Please delete all forms for "%s" (Table "%s") first!', $this->fullName, $this->table));
            }

            $command = Yii::$app->db->createCommand("SELECT COUNT(*) AS `rowsNum` FROM $this->table");
            $result = $command->queryOne();
            if ($result['rowsNum'] > 0) {
                throw new ServerErrorHttpException(sprintf('%s: Table "%s" must be empty, is it? Also check Foreign Key constraints.', $this->fullName, $this->table));
            } else {
                if (!$this->dropTable()) {
                    throw new ServerErrorHttpException(sprintf('%s: Unable to drop table "%s". FKs?', $this->fullName, $this->table));
                }
            }
        }

        ob_end_clean();
        return true;
    }

    public function getDownloadZip()
    {
        $zip = new ZipArchive();
        $tmp_file = sys_get_temp_dir() . "/" . $this->getFullName(). ".zip";
        if(file_exists($tmp_file)){
            $zip->open($tmp_file, ZipArchive::OVERWRITE);
        }
        else{
            $zip->open($tmp_file, ZipArchive::CREATE);
        }
        $zip->addFile($this->_filePath, 'backend/dis_templates/models/' . $this->getFullName().'.json');
        foreach ($this->getGeneratedFiles() as $file) {
            if (file_exists($file['path'])) {
                $localPath = substr($file['path'], strpos($file['path'], 'backend/'));
                $zip->addFile($file['path'], $localPath);
            }
        }
        $zip->close();
        return $tmp_file;
    }


    /**
     * Validates the database structure against the model template
     * @param string[] $warnings Warning messages returned
     * @param Migration|null $migration If the problems should be corrected, a migration object can be supplied
     * @param Boolean $delete Should things be deleted from the data table structure during the fix
     * @return bool Is the database structure ok? Otherwise there are warning messages.
     * @throws \yii\base\NotSupportedException
     */
    public function validateDatabaseStructure (& $warnings, $migration = null, $delete = false, $generateRelations = true) {
        $schema = \Yii::$app->db->getSchema();
        if (!in_array($this->table, $schema->tableNames)) {
            $warnings[] = "Table '" . $this->table . "' is missing";
            if ($migration) {
                echo "call generateTable\n";
                $this->generateTable($migration, true);
            }
        }

        $saveModifiedAt = $this->modifiedAt;
        $tableSchema = \Yii::$app->db->getTableSchema($this->table);

        // First delete unused foreign keys; otherwise associated columns cannot be deleted
        $deletedForeignKeys = [];
        $foreignKeyLocalColumns = [];
        if ($migration && $delete) {
            $dbForeignKeyNames = [];
            foreach ($this->relations as $relation) {
                $foreignKeyLocalColumns[] = $relation->localColumns;
                $dbForeignKey = $relation->findDbForeignKey();
                if ($dbForeignKey) $dbForeignKeyNames[] = array_keys($dbForeignKey)[0];
            }
            foreach ($tableSchema->foreignKeys as $foreignKeyName => $columns) {
                if (!in_array($foreignKeyName, $dbForeignKeyNames)) {
                    $warnings[] = "Foreign key '" . $foreignKeyName . "' should be deleted";
                    $migration->dropForeignKey($foreignKeyName, $this->table);
                    $deletedForeignKeys[] = $foreignKeyName;
                }
            }
        }

        // First delete unused indexes; otherwise associated columns cannot be deleted
        $indexNames = [];
        $deletedIndexes = [];
        foreach ($this->indices as $index) {
            $indexNames[] = $index->getName();
        }
        // Look for indices in database that are not in the model and not part of a foreign key relation
        foreach (ModelTemplateIndex::getAllDbIndices($this) as $indexSchema) {
            if (!in_array($indexSchema->name, $indexNames)) {
                $foundInForeignKeys = false;
                foreach ($foreignKeyLocalColumns as $columns) {
                    if (sizeof(array_diff($columns, $indexSchema->columns)) == 0) {
                        $foundInForeignKeys = true;
                        break;
                    }
                }

                if (!$foundInForeignKeys) {
                    $warnings[] = "Index '" . $indexSchema->name . "' should be deleted";
                    if ($migration && $delete) {
                        $migration->dropIndex($indexSchema->name, $this->table);
                        $deletedIndexes[] = $indexSchema->name;
                    }
                }
            }
        }

        $columnNames = [];
        $oldColumnNames = [];
        // Validate existing columns
        foreach ($this->columns as $column) {
            $column->validateDatabaseStructure($warnings, $migration, $delete);
            $columnNames[] = $column->name;
            $oldColumnNames[] = $column->oldName;
        }

        foreach ($this->columns as $column) {
            if ($column->oldName > '') {
                if (in_array($column->oldName, $columnNames)) {
                    $column->oldName = '';
                    $this->modifiedAt = time();
                }
                else
                    $oldColumnNames[] = $column->oldName;
            }
        }


        // Look for columns in database that are not in the model
        foreach ($tableSchema->columnNames as $columnName) {
            if (!in_array($columnName, $columnNames) && !in_array($columnName, $oldColumnNames)) {
                $warnings[] = "Column '" . $columnName . "' should be deleted";
                if ($migration && $delete) $migration->dropColumn($this->table, $columnName);
            }
        }

        if ($generateRelations) {
            $dbForeignKeyNames = [];
            $foreignKeyLocalColumns = [];
            // Validate existing relations
            foreach ($this->relations as $relation) {
                $foreignKeyLocalColumns[] = $relation->localColumns;
                $relation->validateDatabaseStructure($warnings, $migration, $delete);
                $dbForeignKey = $relation->findDbForeignKey();
                if ($dbForeignKey) $dbForeignKeyNames[] = array_keys($dbForeignKey)[0];
            }
            // Look for foreign keys in database that are not in the model
            foreach ($tableSchema->foreignKeys as $foreignKeyName => $columns) {
                if (!in_array($foreignKeyName, $dbForeignKeyNames) && !in_array($foreignKeyName, $deletedForeignKeys)) {
                    $warnings[] = "Foreign key '" . $foreignKeyName . "' should be deleted";
                    if ($migration && $delete) $migration->dropForeignKey($foreignKeyName, $this->table);
                }
            }
            if($migration) {
                $this->recreateReferencingForeignKeys($migration);
            }
        }

        $indexNames = [];
        // Validate existing indices
        foreach ($this->indices as $index) {
            $index->validateDatabaseStructure($warnings, $migration, $delete);
            $indexNames[] = $index->getName();
        }
        // Look for indices in database that are not in the model and not part of a foreign key relation
        foreach (ModelTemplateIndex::getAllDbIndices($this) as $indexSchema) {
            if (!in_array($indexSchema->name, $indexNames) && !in_array($indexSchema->name, $deletedIndexes)) {
                $foundInForeignKeys = false;
                foreach ($foreignKeyLocalColumns as $columns) {
                    if (sizeof(array_diff($columns, $indexSchema->columns)) == 0) {
                        $foundInForeignKeys = true;
                        break;
                    }
                }

                if (!$foundInForeignKeys) {
                    $warnings[] = "Index '" . $indexSchema->name . "' should be deleted";
                    if ($migration && $delete) $migration->dropIndex($indexSchema->name, $this->table);
                }
            }
        }

        if ($migration && $this->modifiedAt > $saveModifiedAt) {
            $this->save();
        }

        return (sizeof($warnings) == 0);
    }
}
