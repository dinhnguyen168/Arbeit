<?php
namespace app\components\templates;

use app\components\templates\events\AfterFindEvent;
use app\components\templates\events\TemplatesEventInterface;
use app\components\templates\interfaces\IFilterHelpersMethods;
use app\migrations\Migration;
use Yii;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;
use yii\base\Model;
use yii\helpers\ArrayHelper;
use yii\helpers\Inflector;
use yii\helpers\Json;
use yii\web\ServerErrorHttpException;


/**
 * Class BaseTemplate
 *
 * Base template for a model template (Class ModelTemplate) or form template (Class FormTemplate)
 *
 * @package app\components\templates
 */
abstract class BaseTemplate extends Model implements TemplatesEventInterface, IFilterHelpersMethods
{
    /**
     * @var string path to the templates folder
     */
    protected static $templatesPath;

    const SCENARIO_CREATE = 'create';
    const SCENARIO_UPDATE = 'update';
    const SCENARIO_DELETE = 'delete';

    /**
     * @var string path of the current template
     */
    protected $_filePath;
    /**
     * @var bool this will be true when creating a new template
     */
    protected $_isNewFile;

    /**
     * @var integer a timestamp determines when a template was created
     */
    public $createdAt;
    /**
     * @var integer a timestamp determines when a template was modified
     */
    public $modifiedAt;
    /**
     * @var integer a timestamp determines when a template was generated
     */
    public $generatedAt;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [['createdAt', 'modifiedAt', 'generatedAt'], 'integer']
        ]);
    }

    /**
     * BaseTemplate constructor.
     * @param $templateName
     * @param array $config
     * @throws InvalidConfigException
     */
    public function __construct(array $config = [])
    {
        if (empty(static::$templatesPath)) {
            throw new InvalidConfigException('templates path is not set');
        }
        $this->_isNewFile = true;
        parent::__construct($config);
    }

    /**
     * search for a template in the templates path
     * @param string $templateName name of the template
     * @return BaseTemplate|null
     * @throws InvalidConfigException
     */
    static function find (string $templateName) {
        $filePath = static::$templatesPath . '/' . $templateName . '.json';
        if (!file_exists($filePath)) {
            return null;
        } else {
            try {
                $json = Json::decode(file_get_contents($filePath));
            }
            catch (InvalidArgumentException $e) {
                \Yii::error("Syntax error in template file '" . realpath($filePath) . "'. Cannot decode json.");
                return null;
            }

            /* @var $model BaseTemplate*/
            $model = Yii::createObject(static::class);
            $model->load($json, '');
            $model->setTemplateFilePath($templateName);
            $model->_isNewFile = false;
            $model->afterFind();
            return $model;
        }
    }

    public function findModelData($templateName) {
        $filePath = static::$templatesPath . '/' . $templateName . '.json';
        if (!file_exists($filePath)) {
            return null;
        } else {
            try {
                $json = Json::decode(file_get_contents($filePath));
            }
            catch (InvalidArgumentException $e) {
                \Yii::error("Syntax error in template file '" . realpath($filePath) . "'. Cannot decode json.");
                return null;
            }

            return $json;
        }
    }

    /**
     * this method will generate the data structure for the connection model related to many to many relation
     * @param $relation ModelTemplateRelations
     * @return array
     */
    public function generateConnectionModelTemplateData($relation) {
        $columns = [
            'id' => [
                'name' => 'id',
                'importSource' => 'SKEY',
                'type' => 'integer',
                'size' => 11,
                'required' => false,
                'primaryKey' => true,
                'autoInc' => true,
                'label' => 'SKEY',
                'description' => 'expedition number; auto incremented id',
                'validator' => '>=0',
                'validatorMessage' => 'required key field, please enter / select a valid value',
                'unit' => '',
                'selectListName' => '',
                'calculate' => '',
                'defaultValue' => '',
            ],
            $this->table.'_id' => [
                'name' => $this->table.'_id',
                'type' => 'integer',
                'size' => 11,
                'required' => true,
                'primaryKey' => false,
                'autoInc' => false,
                'label' => $this->table.' Id',
                'description' => '',
                'validator' => '',
                'validatorMessage' => '',
                'unit' => '',
                'selectListName' => '',
                'calculate' => '',
                'defaultValue' => '',
            ],
            $relation->relatedTable.'_id' => [
                'name' => $relation->relatedTable.'_id',
                'type' => 'integer',
                'size' => 11,
                'required' => true,
                'primaryKey' => false,
                'autoInc' => false,
                'label' => $relation->relatedTable.' Id',
                'description' => '',
                'validator' => '',
                'validatorMessage' => '',
                'unit' => '',
                'selectListName' => '',
                'calculate' => '',
                'defaultValue' => '',
            ],
        ];

        $indices = [
            'id' => [
                'name' => 'id',
                'type' => 'PRIMARY',
                'columns' => [
                    0 => 'id',
                ],
            ],
            $this->table.'_id' => [
                'name' => $this->table.'_id',
                'type' => 'KEY',
                'columns' => [
                    0 => $this->table.'_id',
                ],
            ],
            $relation->relatedTable.'_id' => [
                'name' => $relation->relatedTable.'_id',
                'type' => 'KEY',
                'columns' => [
                    0 => $relation->relatedTable.'_id',
                ],
            ],
        ];

        $relations = [
            $this->table . "__" . $relation->relatedTable . '__'. $this->table.'__id' => [
                'name' => $this->table . "__" . $relation->relatedTable . '__'. $this->table.'__id',
                'foreignTable' => $this->table,
                'localColumns' => [
                    0 => $this->table.'_id',
                ],
                'foreignColumns' => [
                    0 => 'id',
                ],
            ],
            $this->table . "__" . $relation->relatedTable . '__'. $relation->relatedTable.'__id' => [
                'name' => $this->table . "__" . $relation->relatedTable . '__'. $relation->relatedTable.'__id',
                'foreignTable' => $relation->relatedTable,
                'localColumns' => [
                    0 => $relation->relatedTable.'_id',
                ],
                'foreignColumns' => [
                    0 => 'id',
                ],
            ]
        ];

        return [
            "module" => $this->module,
            "name" => Inflector::id2camel(ucfirst($this->table), "_") . Inflector::id2camel(ucfirst($relation->relatedTable), "_"),
            "table" => $this->table . "_" . $relation->relatedTable,
            "columns" => $columns,
            "indices" => $indices,
            "relations" => $relations,
            "behaviors" => [],
            "createdAt" => time(),
            "modifiedAt" => null,
            "generatedAt" => time(),
            //"fullName" => "ProjectExpedition"
        ];
    }

    /* public function injectOneToManyDataInRelatedModelData($relation, $templatData) {
        $templatData["columns"][$this->table.'_'. $relation->displayColumns[0].'_id_1n'] = [
            'name' => $this->table.'_'. $relation->displayColumns[0].'_id_1n',
            'type' => 'integer',
            'size' => 11,
            'required' => false,
            'primaryKey' => false,
            'autoInc' => false,
            'label' => $this->table.'_'. $relation->displayColumns[0].'_id_1n',
            'description' => '',
            'validator' => '',
            'validatorMessage' => '',
            'unit' => '',
            'selectListName' => '',
            'calculate' => '',
            'defaultValue' => '',
        ];
        $templatData["indices"][$this->table.'_'. $relation->displayColumns[0].'_id_1n'] = [
            'name' => $this->table.'_'. $relation->displayColumns[0].'_id_1n',
            'type' => 'KEY',
            'columns' => [
                0 => $this->table.'_'. $relation->displayColumns[0].'_id_1n',
            ],
        ];
        $templatData["relations"][$this->table.'__'.$relation->foreignTable. '__'. $relation->displayColumns[0] .'__foreignkey'] = [
            "name" => $this->table.'__'.$relation->foreignTable. '__'. $relation->displayColumns[0] .'__foreignkey',
            "foreignTable" => $this->table,
            "localColumns" => [
                $this->table.'_'. $relation->displayColumns[0].'_id_1n'
            ],
            "foreignColumns" => [
                "id"
            ]
        ];
        return $templatData;
    } */

    /**
     * this method will inject the data of 'reversed many to many relation' in the related model
     * @param $relation ModelTemplateRelations
     * @param $templateData BaseTemplate the model template of the related model
     * @return BaseTemplate
     */
    public function injectManyToManyDataInRelatedModelData($relation, $templateData) {
        $referenceTable = $this->table;
        if(!isset($templateData["columns"][$relation->oppositeColumnName])) {
            $templateData["columns"][$relation->oppositeColumnName] = [
                'name' => $relation->oppositeColumnName,
                'importSource' => '',
                'type' => 'many_to_many',
                'size' => NULL,
                'required' => false,
                'primaryKey' => false,
                'autoInc' => false,
                'label' => $this->name,
                'description' => '',
                'validator' => '',
                'validatorMessage' => '',
                'unit' => '',
                'selectListName' => '',
                'calculate' => '',
                'defaultValue' => '',
                'pseudoCalc' => '',
                'displayColumn' => 'id',
                'isLocked' => NULL,
                'searchable' => true,
                'oppositionRelation' => true,
                'relatedTable' => $referenceTable
            ];
        }
        if(!isset($templateData["relations"][$templateData["table"].'__' . $referenceTable.'__'. $relation->oppositeColumnName.'__nm'])) {
            $templateData["relations"][$templateData["table"].'__' . $referenceTable.'__'. $relation->oppositeColumnName.'__nm'] = [
                'name' => $templateData["table"].'__' . $referenceTable.'__'. $relation->oppositeColumnName.'__nm',
                'relationType' => 'nm',
                'relatedTable' => $this->table,
                'foreignTable' => NULL,
                'localColumns' => [
                    $relation->oppositeColumnName
                ],
                'foreignColumns' => NULL,
                'displayColumns' => [
                    'id'
                ],
                'isLocked' => NULL,
                'oppositionRelation' => true,
                'oppositeColumnName' => $relation->localColumns[0]
            ];
        }
        return $templateData;
    }

    /* private function deleteOneTomanyDataFromRelatedModel($relation) {
        $templateName = Inflector::id2camel(ucfirst($relation->foreignTable), '_');
        $templateData = $this->findModelData($templateName);

        if(isset($templateData["columns"][$this->table.'_'. $relation->displayColumns[0].'_id_1n'])) {
            unset($templateData["columns"][$this->table.'_'. $relation->displayColumns[0].'_id_1n']);
        }
        if(isset($templateData["indices"][$this->table.'_'. $relation->displayColumns[0].'_id_1n'])) {
            unset($templateData["indices"][$this->table.'_'. $relation->displayColumns[0].'_id_1n']);
        }
        if(isset($templateData["relations"][$this->table.'__'.$relation->foreignTable. '__'. $relation->displayColumns[0] .'__foreignkey'])) {
            unset($templateData["relations"][$this->table.'__'.$relation->foreignTable. '__'. $relation->displayColumns[0] .'__foreignkey']);
        }

        // @var $relatedModel BaseTemplate
        $relatedModel = Yii::createObject(static::class);
        $relatedModel->load($templateData, '');
        $relatedModel->setTemplateFilePath($templateName);
        $relatedModel->_isNewFile = false;
        $relatedModel->afterFind();
        $this->saveCreateRelatedModel($relatedModel);
    } */

    /**
     * this method will delete the data of 'reversed many to many relation' in the related model
     * @param $relation ModelTemplateRelations
     * @return void
     */
    private function deleteManyToManyDataFromRelatedModel($relation) {
        $templateName = Inflector::id2camel(ucfirst($relation->relatedTable), '_');
        $templateData = $this->findModelData($templateName);

        if(isset($templateData["columns"][$relation->oppositeColumnName])) {
            unset($templateData["columns"][$relation->oppositeColumnName]);
        }
        $referenceTable = $this->table === $templateData["table"] ? "self" : $this->table;
        if(isset($templateData["relations"][$templateData["table"].'__' . $referenceTable. '__'.$relation->oppositeColumnName.'__nm'])) {
            unset($templateData["relations"][$templateData["table"].'__' . $referenceTable. '__'. $relation->oppositeColumnName.'__nm']);
        }

        /* @var $relatedModel BaseTemplate*/
        $relatedModel = Yii::createObject(static::class);
        $relatedModel->load($templateData, '');
        $relatedModel->setTemplateFilePath($templateName);
        $relatedModel->_isNewFile = false;
        $relatedModel->afterFind();
        $this->saveCreateRelatedModel($relatedModel, false);
    }

    /**
     * @param $relatedModel BaseTemplate the model template of the related model
     * @param $create boolean
     * @return void
     */
    private function saveCreateRelatedModel($relatedModel, $create = true) {
        if ($relatedModel->validate()) {
            if ($relatedModel->save() === false && !$relatedModel->hasErrors()) {
                throw new ServerErrorHttpException('Failed to update the object for unknown reason.');
            }
            if($create) {
                if ($relatedModel && ($relatedModel->isTableUpdateNeeded())) {
                    $warnings = [];
                    $migration = new Migration();
                    $transaction = $migration->db->beginTransaction();
                    try {
                        $relatedModel->validateDatabaseStructure($warnings, $migration, true);
                        $transaction->commit();
                        if (sizeof($warnings)) {
                            \Yii::warning("The following modifications of table ". $relatedModel->table . " have been corrected:\n" . implode("\n", $warnings));
                        }
                    } catch (Exception $e) {
                        $transaction->rollBack();
                        $relatedModel->restoreBackupVersion();
                        throw $e;
                    }
                }
            }
            if($this->module !== $relatedModel->module) {
                $relatedModel->deleteBackupVersion();
            }
        }
    }

    /**
     * This is the central methode of generating many to many relation
     * after the model will be saved this methode will check the relations searching for many to many relations
     * will create or delete the connection table in the database
     * will create or delete the many to many relations from both relations models
     *
     * @param $templateName string
     * @return void
     */
    public function afterCreate($templateName) {
        if($this instanceof ModelTemplate) {
            $oldAttributes = $this->getOldAttributes();
            if(sizeof($oldAttributes["relations"]) != sizeof($this->relations)) {
                foreach ($oldAttributes["relations"] as $key => $value) {
                    if($value->relationType == 'nm' && !isset($this->relations[$key])){
                        $oldAttributes["relations"][$key]->dropConnectionTable();
                        $this->deleteManyToManyDataFromRelatedModel($oldAttributes["relations"][$key]);
                    }
                    /*if($value->relationType == '1n' && !isset($this->relations[$key])){
                        $this->deleteOneTomanyDataFromRelatedModel($oldAttributes["relations"][$key]);
                    }*/
                }
            }

            if(isset($this->relations)) {
                foreach ($this->relations as $relation) {
                    if(isset($relation->relationType) && $relation->relationType == 'nm') {
                        if(!$relation->oppositionRelation) {
                            $connectionTemplateData = $this->generateConnectionModelTemplateData($relation);
                            $connectionModel = \Yii::createObject(static::class);
                            $connectionModel->load($connectionTemplateData,'');
                            // $modelTemplate->setTemplateFilePath($templateName);
                            $connectionModel->_isNewFile = false;
                            $connectionModel->afterFind();
                            /* @var $connectionModel ModelTemplate */
                            if ($connectionModel && (!$connectionModel->getIsTableCreated())) {
                                $warnings = [];
                                $migration = new Migration();
                                $transaction = $migration->db->beginTransaction();
                                try {
                                    $connectionModel->validateDatabaseStructure($warnings, $migration);
                                    $transaction->commit();
                                    if (sizeof($warnings)) {
                                        \Yii::warning("The following modifications of table ". $connectionModel->table . " have been corrected:\n" . implode("\n", $warnings));
                                    }
                                } catch (\Exception $e) {
                                    $transaction->rollBack();
                                    $connectionModel->restoreBackupVersion();
                                    throw $e;
                                }
                            }
                            $connectionModel->deleteBackupVersion();
                        }

                        $relatedTemplateName = Inflector::id2camel(ucfirst($relation->relatedTable), '_');
                        $relatedTemplateData = $this->findModelData($relatedTemplateName);
                        $relatedModelData = $this->injectManyToManyDataInRelatedModelData($relation, $relatedTemplateData);
                        /* @var $relatedModel BaseTemplate*/
                        $relatedModel = Yii::createObject(static::class);
                        $relatedModel->load($relatedModelData, '');
                        $relatedModel->setTemplateFilePath($relatedTemplateName);
                        $relatedModel->_isNewFile = false;
                        $relatedModel->afterFind();
                        $this->saveCreateRelatedModel($relatedModel, false);
                    }
                    /* if(isset($relation->relationType) && $relation->relationType == '1n') {
                        $templateName = Inflector::id2camel(ucfirst($relation->foreignTable), '_');
                        $templateData = $this->findModelData($templateName);
                        $relatedModelData = $this->injectOneToManyDataInRelatedModelData($relation, $templateData);
                        // @var $relatedModel BaseTemplate
                        $relatedModel = Yii::createObject(static::class);
                        $relatedModel->load($relatedModelData, '');
                        $relatedModel->setTemplateFilePath($templateName);
                        $relatedModel->_isNewFile = false;
                        $relatedModel->afterFind();
                        $this->saveCreateRelatedModel($relatedModel);
                    } */
                }
            }
        }
    }

    public function afterFind()
    {
        Yii::$app->trigger(self::EVENT_AFTER_TEMPLATE_FIND, new AfterFindEvent([
            'template' => $this
        ]));
    }

    /**
     * @return bool isNewFile getter
     */
    public function getIsNewFile () {
        return $this->_isNewFile;
    }

    /**
     * filepath setter
     * @param $templateName string name of the template (without extension)
     */
    public function setTemplateFilePath ($templateName) {
        $this->_filePath = static::$templatesPath . '/' . $templateName . '.json';
    }

    /**
     * this method will be called before saving the template
     * @param bool $setModifiedAt whether to change the modified time
     * @return bool
     */
    public function beforeSave ($setModifiedAt = true) {
        if ($this->_isNewFile) {
            $this->createdAt = time();
        }
        if ($setModifiedAt) {
            $this->modifiedAt = time();
        }
        $this->createBackupVersion();
        return true;
    }

    public function afterSave ($saved) {
        if (!$saved) {
            $this->deleteBackupVersion();
            \Yii::$app->templates->update($this);
        }
    }

    public function getBackupPath () {
        return Yii::getAlias('@runtime') . '/' . substr($this->_filePath, strpos($this->_filePath, 'dis_templates'));
    }

    public function createBackupVersion () {
        if (!$this->_isNewFile) {
            $destination = $this->getBackupPath();
            if (!file_exists(pathinfo($destination)['dirname'])) {
                mkdir(pathinfo($destination)['dirname'], 0777, true);
            }
            if (file_exists($destination)) {
                unlink($destination);
            }
            copy($this->_filePath, $destination);
        }
    }

    public function deleteBackupVersion () {
        $backupPath = $this->getBackupPath();
        if (file_exists($backupPath)) {
            unlink($backupPath);
        }
    }

    public function restoreBackupVersion () {
        $backupPath = $this->getBackupPath();
        if (file_exists($backupPath)) {
            if (file_exists($this->_filePath)) {
                unlink($this->_filePath);
            }
            copy($backupPath, $this->_filePath);
            unlink($backupPath);
        }
    }

    /**
     * @return mixed true if delete is allowed to continue, otherwise a string that specify the error.
     */
    public function beforeDelete () {
        return true;
    }

    /**
     * @param string $setModifiedAt whether to update modifiedAt time (not always required when saving file after generation)
     * @return bool true if the template was saved successfully
     */
    public function save ($setModifiedAt = true) {
        if (!$this->validate() || !$this->beforeSave($setModifiedAt)) {
            return false;
        }
        $saved = file_put_contents($this->_filePath, Json::encode($this->getJsonArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $this->afterSave($saved);
        return $saved;
    }

    /**
     * @return array Array of data to be saved.
     */
    protected function getJsonArray() {
        return ArrayHelper::toArray($this);
    }

    /**
     * delete the current template
     * @return bool true if the template was deleted successfully
     * @throws ServerErrorHttpException
     */
    public function delete () {
        if (!$this->validate()) {
            throw new ServerErrorHttpException('deletion validation error');
        }
        if ($this->beforeDelete()) {
            $this->cleanUpGeneratedFiles();
            return unlink($this->_filePath);
        }
    }

    /**
     * @inheritdoc
     */
    public function validate($attributeNames = null, $clearErrors = true)
    {
        $valid = parent::validate($attributeNames, $clearErrors);
        if ($this->_isNewFile && file_exists($this->_filePath)) {
            $this->addError('templateFile', 'cannot create already existing template');
            $valid = false;
        }
        return $valid;
    }

    /**
     * get all the available templates of the current type (inside the specified templates path)
     * @return array of the base file name (with extensions) in the templates file
     */
    public static function getTemplateFiles () {
        $files = [];
        foreach (glob(static::$templatesPath . '/*.json') as $template) {
            $files[] = pathinfo($template, PATHINFO_BASENAME );
        }
        return $files;
    }

    /**
     * get all the available templates of the current type (inside the specified templates path)
     * @return array of the base file name (without extensions) in the templates file
     */
    public static function getTemplateNames () {
        $files = [];
        foreach (glob(static::$templatesPath . '/*.json') as $template) {
            $files[] = pathinfo($template, PATHINFO_FILENAME );
        }
        return $files;
    }

    /**
     * loads and populate the template data array (e.g. parsed from json) into the current instance
     * @param array $data templates data
     * @param null $formName
     * @return bool true if the load was sucessful
     */
    public function load($data, $formName = null)
    {
        if(parent::load($data, $formName)) {
            $this->setTemplateFilePath($this->fileName());
            $this->populateAfterLoad();
            return true;
        }
        return false;
    }

    /**
     * removes `..` and `.` from the path and replace them with the absolute path
     * @param $path string the path to be resolved
     * @return string the resolved path
     */
    public function resolvedAbsolutePath ($path) {
        $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        $parts = array_filter(explode(DIRECTORY_SEPARATOR, $path), 'strlen');
        $resolved = [];
        foreach ($parts as $part) {
            if ('.' == $part) continue;
            if ('..' == $part) {
                array_pop($resolved);
            } else {
                $resolved[] = $part;
            }
        }
        return '/' . implode(DIRECTORY_SEPARATOR, $resolved);
    }

    /**
     * template file name from the new form loaded data @see BaseTemplate::load()
     * @return string the file name of the template file
     */
    abstract protected function fileName();

    /**
     * populates the templates data array values into instances when needed (as implemented)
     */
    abstract protected function populateAfterLoad();

    /**
     * get the files that have been generated based on the current template
     * @return array of generated files
     */
    abstract public function getGeneratedFiles();

    /**
     * delete the files that have been generated based on the current template
     */
    abstract public function cleanUpGeneratedFiles();

    abstract public function getDownloadZip();

}
