<?php
/**
 * Created by PhpStorm.
 * User: alali
 * Date: 24.01.2019
 * Time: 14:28
 */

namespace app\components\templates;


use app\migrations\Migration;
use yii\base\Model;

/**
 * to represent an index in the model template
 * Class ModelTemplateIndex
 * @package app\components\templates
 */
class ModelTemplateIndex extends Model
{
    /**
     * @var string name of the key, must be unique
     */
    public $name;
    /**
     * @var string type of the key PRIMARY, INDEX or UNIQUE
     */
    public $type;
    /**
     * @var array of columns names that forms this key
     */
    public $columns;

    private $_model;

    /**
     * @var boolean is this column locked for change
     */
    public $isLocked;

    public function __construct($parentModel, array $config = [])
    {
        $this->_model = $parentModel;
        parent::__construct($config);
    }

    public function getParentModel () {
        return $this->_model;
    }

    public function rules()
    {
        return [
            [['name', 'type', 'columns'], 'required'],
            [['columns'], 'each', 'rule' => ['string']],
            [['name', 'type'], 'string']
        ];
    }

    public function fields()
    {
        return array_merge(parent::fields(), ['isLocked']);
    }

    public function getName() {
        return $this->type == "PRIMARY" ? "PRIMARY" : $this->name;
    }

    /**
     * adds the key migration to the schema builder
     * @param Migration $migration
     * @param Boolean $dropFirst Before creating try to drop an existing index of that name
     * @param Boolean $showMessages
     */
    public function generateIndex (Migration $migration, $dropFirst = true, $showMessages = false) {
        if (sizeof($this->columns) > 0) {
            if ($this->type == "PRIMARY") {
                if (!$this->_model->columns[$this->columns[0]]->autoInc) {
                    if ($showMessages) ob_start();

                    $existingPrimaryKeys = \Yii::$app->db->getTableSchema($this->_model->table);
                    if (sizeof($existingPrimaryKeys)) {
                        $migration->dropPrimaryKey($this->getName(), $this->_model->table);
                    }

                    // $name = implode("__", $this->columns);
                    $migration->addPrimaryKey($this->getName(), $this->_model->table, $this->columns);
                    if ($showMessages) ob_end_clean();
                }
                // else: Yii generates index for auto increment columns
            } else {
                if ($showMessages) ob_start();
                try {
                    $migration->dropIndex($this->name, $this->_model->table);
                }
                catch (\Exception $e) {
                    // Ignore: No method in Yii to check, if index exists
                }
                $migration->createIndex($this->name, $this->_model->table, $this->columns, $this->type == "UNIQUE");
                if ($showMessages) ob_end_clean();
            }
        }
    }


    /**
     * Validates the database structure of the foreign key against that in the model template
     * @param string[] $warnings Warning messages returned
     * @param Migration|null $migration If the problems should be corrected, a migration object can be supplied
     */
    public function validateDatabaseStructure (& $warnings, $migration = null)
    {
        $indexSchema = $this->getIndexSchema();
        $indexText = "Index '" . $this->name . "'";
        $fix = false;
        $dropExisting = true;
        if (!$indexSchema) {
            $warnings[] = $indexText . " does not exist (type:" . $this->type . ", columns:" . implode(",", $this->columns) . ")";
            $fix = true;
            $dropExisting = false;
        }
        else {
            if ($this->type == "PRIMARY") {
                if (!$indexSchema->getIsPrimary()) {
                    $fix = true;
                    $warnings[] = $indexText . " should be PRIMARY but is not";
                }
            }
            else if ($this->type == "UNIQUE") {
                if (!$indexSchema->getIsUnique()) {
                    $fix = true;
                    $warnings[] = $indexText . " should be UNIQUE";
                }
            }
            else if ($indexSchema->getIsUnique()) {
                $fix = true;
                $warnings[] = $indexText . " is UNIQUE but should not be";
            }

            if (sizeof(array_diff($this->columns, $indexSchema->columns))) {
                $fix = true;
                $warnings[] = $indexText . " should have columns [" . implode(", ", $this->columns) . "] but has [" . implode(", ", $indexSchema->columns) . "]";
            }
        }

        if ($fix && $migration) {
            $this->generateIndex($migration, $dropExisting);
        }
    }


    /**
     * Get the indices of models data table.
     * Workaround until Yii TableSchema provides this.
     * @param ModelTemplate $model Model template
     * @return IndexSchema[] Array of IndexSchema
     * @throws \yii\db\Exception
     */
    public static function getAllDbIndices($model) {
        $migration = new Migration();
        $command = \Yii::$app->db->createCommand($migration->getShowIndexesStatement($model->table));
        //$command->bindParam(":tableName", $model->table, \PDO::PARAM_STR);
        if ($model->getFullName() == "CoreCore") {
            // print_r($command->queryAll()); die();
        }

        $indices = [];
        foreach ($command->queryAll() as $index) {
            $name = $index["Key_name"];
            if (!isset($indices[$name])) {
                $indexSchema = new IndexSchema($name);
                $indexSchema->isUnique = ($index["Non_unique"] == 0);
                $indexSchema->columns[] = $index["Column_name"];
                $indices[$name] = $indexSchema;
            }
            else
                $indices[$name]->columns[] = $index["Column_name"];
        }

        return $indices;
    }

    /**
     * Get the database index for this model template index
     * @return null | IndexSchema Null or IndexSchema
     * @throws \yii\db\Exception
     */
    public function getIndexSchema() {
        $allIndices = static::getAllDbIndices($this->_model);
        $name = $this->getName();
        if (isset($allIndices[$name])) {
            return $allIndices[$name];
        }
        return null;
    }
}


/**
 * Class IndexSchema
 * Workaround until Yii TableSchema provides this.
* @package app\components\templates
 */
class IndexSchema {
    public $name = "";
    public $isUnique = false;
    public $columns = [];

    public function __construct($name) {
        $this->name = $name;
    }

    public function getIsPrimary() {
        return $this->name == "PRIMARY";
    }

    public function getIsUnique() {
        return $this->isUnique;
    }
}
