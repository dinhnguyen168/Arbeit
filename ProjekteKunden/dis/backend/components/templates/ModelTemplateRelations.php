<?php
/**
 * Created by PhpStorm.
 * User: alali
 * Date: 24.01.2019
 * Time: 14:28
 */

namespace app\components\templates;


use app\migrations\Migration;
use Yii;
use yii\base\Model;
use yii\db\Connection;
use yii\db\Exception;
use yii\helpers\Inflector;
use yii\validators\PunycodeAsset;
use yii\web\ServerErrorHttpException;

/**
 * to represent the relations a model template
 * Class ModelTemplateRelations
 * @package app\components\templates
 */
class ModelTemplateRelations extends Model
{
    /**
     * must be unique
     * @var string name of the key
     */
    public $name;
    /**
     * @var string type of the relation
     */
    public $relationType;
    /**
     * @var string relatedTable of the relation
     */
    public $relatedTable;
    /**
     * the foreign table of the key
     * @var string table name
     */
    public $foreignTable;
    /**
     * @var array of columns names that forms the key
     */
    public $localColumns;
    /**
     * @var array of columns in the foreign table
     */
    public $foreignColumns;
    /**
     * @var array of display columns in the foreign table
     */
    public $displayColumns;

    /**
     * @var ModelTemplate the parent model
     */
    private $_model;

    /**
     * @var boolean is this column locked for change
     */
    public $isLocked;

    public $oppositionRelation = false;

    public $oppositeColumnName = '';

    public function __construct($parentModel, array $config = [])
    {
        $this->_model = $parentModel;
        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name'], 'required'],
            [['displayColumns', 'oppositionRelation', 'oppositeColumnName'], 'safe'],
            [['localColumns', 'foreignColumns'], 'each', 'rule' => ['string']],
            ['foreignColumns', 'validateForeignColumns'],
            [['name', 'foreignTable', 'relatedTable', 'relationType', 'oppositeColumnName'], 'string']
        ];
    }

    public function fields()
    {
        return array_merge(parent::fields(), ['isLocked']);
    }



    public function validateForeignColumns ($attribute, $params, $validator) {
        /* @var $connection Connection */
        $connection = Yii::$app->db;
        /* @var $dbSchema \yii\db\mysql\Schema */
        $dbSchema = $connection->getSchema();
        $tables = $dbSchema->getTableSchemas();//returns array of tbl schema's
        foreach($tables as $tbl)
        {
            if ($tbl->name === $this->foreignTable) {
                if ($tbl->primaryKey != $this->foreignColumns) {
                    $this->addError($attribute, 'foreign columns must be primary keys');
                }
            }
        }
    }

    /**
     * add the foreign key to the schema builder
     * @param Migration $migration
     * @throws ServerErrorHttpException
     */
    public function generateForeignKey(Migration $migration = null)
    {
        if ($migration == null) {
            $migration = new Migration();
        }
        ob_start();
        try {
            $name = $this->getShortenedName();
            $dbFK = $this->findDbForeignKey();
            if ($dbFK) {
                try {
                    $migration->dropForeignKey($name, $this->_model->table);
                }
                catch (\Exception $e) {}
            }

            $onUpdate = $migration->getFKOnUpdate($this->_model->table, $this->foreignTable);
            $migration->addForeignKey($name, $this->_model->table, $this->localColumns, $this->foreignTable, $this->foreignColumns, $migration->getRestrict(), $onUpdate);
        } catch (Exception $dbe) {
            throw new ServerErrorHttpException($dbe->getMessage());
        } catch (\Exception $e){
            throw new ServerErrorHttpException('Error creating the FK ' . $this->name);
        }
        ob_end_clean();
    }

    public function getShortenedName() {
        $name = $this->name;
        // Foreign key constraint names are limited to 64 characters
        if (strlen($name) > 60) {
            $name = substr($this->_model->table, 0, 63-15) . "_" . time() . ":" . rand(0, 999);
        }
        return $name;
    }

    public function dropForeignKey(Migration $migration = null) {
        if ($migration == null) {
            $migration = new Migration();
        }
        $name = $this->getShortenedName();
        ob_start();
        try {
            $migration->dropForeignKey($name, $this->_model->table);
        }
        catch (\Exception $e){}
        ob_end_clean();
    }

    public function getCompare() {
        $cCode = $this->foreignTable . ";";
        if (is_array($this->localColumns)) {
            sort ($this->localColumns);
            $cCode .= implode(",", $this->localColumns) . ";";
        }
        else
            $cCode .= $this->localColumns . ";";

        if (is_array($this->foreignColumns)) {
            sort ($this->foreignColumns);
            $cCode .= implode(",", $this->foreignColumns);
        }
        else
            $cCode .= $this->foreignColumns;
        return $cCode;
    }

    public function getRelationName()
    {
        $key = $this->localColumns[0];
        $multiple = false;

        if (!empty($key) && strcasecmp($key, 'id')) {
            if (substr_compare($key, 'id', -2, 2, true) === 0) {
                $key = rtrim(substr($key, 0, -2), '_');
            } elseif (substr_compare($key, 'id_', 0, 3, true) === 0) {
                $key = ltrim(substr($key, 3, strlen($key)), '_');
            }
        }
        if ($multiple) {
            $key = Inflector::pluralize($key);
        }
        $name = $rawName = Inflector::id2camel($key, '_');

        $i = 0;
        while ($this->_model->hasColumn(lcfirst($name))) {
            $name = $rawName . ($i++);
        }

        return $name;
    }

    public function getSqlRelation() {
        $on = [];
        for ($i=0; $i<sizeof($this->foreignColumns); $i++) {
            $on[] = $this->_model->table . "." . $this->localColumns[$i] . " = " . $this->foreignTable . "." . $this->foreignColumns[$i];
        }
        return ['table' => $this->foreignTable, 'on' => implode(" AND ", $on)];
    }

    public function getIsParentRelation() {
        if ($this->_model->parentModel) {
            $parentModelTemplate = \Yii::$app->templates->getModelTemplate($this->_model->parentModel);
            return ($parentModelTemplate &&
                    $this->foreignTable == $parentModelTemplate->table &&
                    sizeof($this->foreignColumns) == 1 &&
                    $this->foreignColumns[0] == "id");
        }
        return false;
    }


    /**
     * Validates the database structure of the foreign key against that in the model template
     * @param string[] $warnings Warning messages returned
     * @param Migration|null $migration If the problems should be corrected, a migration object can be supplied
     */
    public function validateDatabaseStructure (& $warnings, $migration = null)
    {
        if ($this->relationType == "nm") return;
        $foreignKey = $this->findDbForeignKey();
        if (!$foreignKey) {
            $warnings[] = "Missing foreign key '" . $this->getShortenedName() . "' ['" . implode("', '", $this->localColumns) . "'] => " . $this->foreignTable . ".['" . implode("', '", $this->foreignColumns) . "']";
            if ($migration) {
                $onUpdate = $migration->getFKOnUpdate($this->_model->table, $this->foreignTable);
                $migration->addForeignKey($this->getShortenedName(), $this->_model->table, $this->localColumns, $this->foreignTable, $this->foreignColumns, $migration->getRestrict(), $onUpdate);
            }
        }
        else {
            $name = array_keys($foreignKey)[0];
            $columns = $foreignKey[$name];
            unset($columns[0]);
            $localColumns = array_keys($columns);
            $remoteColumns = array_values($columns);
            for($i=0; $i<sizeof($localColumns); $i++) {
                if ($this->localColumns[$i] != $localColumns[$i]) {
                    $warnings[] = "Relation '" . $name . ": Remote column " . ($i+1) . " is '" . $remoteColumns[$i] . "' but should be '" . $this->remoteColumns[$i] . "'";
                    if ($migration) {
                        $migration->dropForeignKey($name, $this->_model->table);
                        $onUpdate = $migration->getFKOnUpdate($this->_model->table, $this->foreignTable);
                        $migration->addForeignKey($this->getShortenedName(), $this->_model->table, $this->localColumns, $this->foreignTable, $this->foreignColumns, $migration->getRestrict(), $onUpdate);
                    }
                    break;
                }
            }
        }
   }

    /**
     * Find the foreign key in the database.
     * Return null or an one element associative array like in \yii\db\TableSchema::$foreignKeys
     * @return array|null Foreign key in the database
     */
    public function findDbForeignKey() {
        $tableSchema = \Yii::$app->db->getTableSchema($this->_model->table);
        $namePrefix = preg_replace("/_\\d+:\\d+$/", "", $this->getShortenedName());
        $foreignKeys = $tableSchema->foreignKeys;
        foreach ($foreignKeys as $name => $foreignKey) {
            if (substr($name, 0, strlen($namePrefix)) == $namePrefix) {
                if ($foreignKey[0] == $this->foreignTable) {
                    unset($foreignKey[0]);
                    $localColumns = array_keys($foreignKey);
                    if (sizeof($this->localColumns) == sizeof($localColumns)) {
                        $fits = true;
                        for ($i=0; $i<sizeof($this->localColumns); $i++) {
                            if ($this->localColumns[$i] != $localColumns[$i]) {
                                echo "column different: " . $this->localColumns[$i] . " vs. " . $localColumns[$i] . "\n";
                                $fits = false;
                                break;
                            }
                        }
                        if ($fits) return [$name => $tableSchema->foreignKeys[$name]];
                    }
                }
            }
        }
        return null;
    }

    public function dropConnectionTable() {
        $tableSchema = \Yii::$app->db->getTableSchema($this->_model->table . "_" . $this->relatedTable);
        if($tableSchema) {
            $migration = new Migration();
            $migration->dropTable($tableSchema->name);
        }
    }

}
