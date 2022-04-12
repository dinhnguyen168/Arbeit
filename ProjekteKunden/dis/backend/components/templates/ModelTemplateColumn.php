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
 * to represent a column in a model template
 * Class ModelTemplateColumn
 * @package app\components\templates
 *
 * @property boolean searchable
 */
class ModelTemplateColumn extends Model
{
    const REGEX_CONTAINS_FUNCTION = "/^(?:'[^\\']*'|" . '"[^\\"]*"' . "|[^'\"\\(]+)*[\\(]/";

    /**
     * @var string name of the column
     */
    public $name;
    /**
     * @var string name of the column
     */
    public $oldName = "";
    /**
     * @var string the name of the column in the source table (only for import)
     */
    public $importSource = "";
    /**
     * @var string the type of the column
     */
    public $type;
    /**
     * @var int the size of the column
     */
    public $size = 0;
    /**
     * @var bool whether this column is a required column
     */
    public $required = false;
    /**
     * @var bool whether this column is a primary key (legacy)
     */
    public $primaryKey = false;
    /**
     * @var bool whether this column is an auto increment column (legacy)
     */
    public $autoInc = false;
    /**
     * @var string the column label (will be shown as input label)
     */
    public $label = "";
    /**
     * @var string the column description (will be shown as a hint under the input)
     */
    public $description = "";
    /**
     * @var string column validation rule (see user-help/model-structure for details)
     */
    public $validator = "";
    /**
     * @var string the message to be shown if the value was not valid
     */
    public $validatorMessage = "";
    /**
     * @var string the unit of this column value
     */
    public $unit = "";
    /**
     * @var string the name of the list to be used as an option list for this column (legacy, the value will be set in the form template instead)
     */
    public $selectListName = "";
    /**
     * @var string an equation to calculate this column value (see user-help/model-structure for details)
     */
    public $calculate = "";
    /**
     * @var string|integer|double the default value of the current column
     */
    public $defaultValue = "";
    /**
     * @var string how to get the pseudo column value
     * could be a dot separated string parent.parent.name
     * or a php function that takes $model as a parameter
     */
    public $pseudoCalc = "";

    /**
     * @var string the display column from the related table of many many, one many relation
     */
    public $displayColumn = "";

    /**
     * @var ModelTemplate the template the this column belongs to
     */
    protected $_model;

    /**
     * @var boolean is this column locked for change
     */
    public $isLocked;

    public $oppositionRelation = false;

    public $relatedTable = '';

    public function __construct($parentModel, array $config = [])
    {
        $this->_model = $parentModel;
        parent::__construct($config);
    }

    public function getSearchable () {
        return $this->type != 'pseudo' || ( $this->type == 'pseudo' && preg_match('/^\w+(\.\w+)*$/', $this->pseudoCalc));
    }

    public function setSearchable () {}

    public function fields()
    {
        return array_merge(parent::fields(), ['searchable', 'isLocked']);
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['oldName'], 'safe'],
            [['name', 'type', 'label'], 'required'],
            [['displayColumn'], 'required' ,'when' => function($model) {
                return $model->type == 'many_to_many' || $model->type == 'one_to_many';
            }],
            [['type'], 'in', 'range' => ['integer', 'double', 'boolean', 'string', 'string_multiple', 'text', 'dateTime', 'date', 'time', 'pseudo', 'many_to_many', 'one_to_many']],
            [['name', 'importSource', 'type', 'label', 'description', 'validator', 'validatorMessage', 'unit', 'selectListName', 'calculate', 'defaultValue', 'displayColumn'], 'string'],
            [['pseudoCalc'], 'required', 'when' => function ($model) {
                return $model->type === 'pseudo';
            }],
            [['size'], 'number'],
            [['required', 'primaryKey', 'autoInc', 'searchable'], 'boolean'],
            ['calculate', 'validateCalculate'],
            ['pseudoCalc', 'validatePseudoCalc'],
            ['defaultValue', 'validateDefaultValue'],
            [['oppositionRelation', 'relatedTable'], 'safe'],
        ];
    }

    public function validateCalculate($attribute, $params, $validator) {
        $nonValidColumnNames = [];
        if (preg_match_all('/\[([a-zA-Z_]+)\]/', $this->{$attribute}, $matches)) {
            foreach ($matches[1] as $columnName) {
                /* @var ModelTemplateColumn $columnModel */
                $found = false;
                foreach ($this->_model->columns as $columnModel) {
                    if ($columnName == $columnModel->name) {
                        $found = true;
                    }
                }
                if (!$found) {
                    $nonValidColumnNames[] = $columnName;
                }
            }
            if (count($nonValidColumnNames) > 0) {
                $this->addError($attribute, implode(', ', $nonValidColumnNames) . (count($nonValidColumnNames) == 1 ? " is " : " are ") . "not valid column names");
                return false;
            }
        }
        return true;
    }

    public function validatePseudoCalc($attribute, $params, $validator) {
        if (preg_match('/^\w+(\.\w+)*$/', $this->{$attribute})) {
            return $this->validateCommaSeparatedPseudoCalc($attribute, $params, $validator);
        }
        return $this->validatePhpPseudoCalc($attribute, $params, $validator);
    }

    public function validateCommaSeparatedPseudoCalc($attribute, $params, $validator) {
        if (preg_match_all('/(\w+)/', $this->{$attribute}, $matches)) {
            $currentModelTemplate = $this->_model;
            foreach ($matches[0] as $partIndex => $part) {
                if ($part == 'parent') {
                    // load and check parent
                    $ownerModelParent = $currentModelTemplate->parentModel;
                    if ($ownerModelParent) {
                        $currentModelTemplate = \Yii::$app->templates->getModelTemplate($ownerModelParent);
                    } else {
                        $this->addError($attribute, $currentModelTemplate->fullName . ' does not have a parent model');
                        return false;
                    }
                } else {
                    if ($partIndex == sizeof($matches[0])-1) {
                        if ($currentModelTemplate->hasColumn($part))
                            return true;
                        else {
                            $this->addError($attribute, $currentModelTemplate->fullName . ' does not have a column "' . $part . '"');
                            return false;
                        }
                    }
                    else {
                        $relationFound = false;
                        foreach ($currentModelTemplate->relations as $relation) {
                            if (!preg_match('/__parent$/', $relation->name) && $relation->getRelationName() == ucfirst($part)) {
                                $relationFound = true;
                                $relatedModelTemplate = \Yii::$app->templates->getModelTemplateByDataTable($relation->foreignTable);
                                if ($relatedModelTemplate)
                                    $currentModelTemplate = $relatedModelTemplate;
                                else {
                                    $this->addError($attribute, 'Could not find model for related table "' . $relation->foreignTable . '" in ' . $currentModelTemplate->fullName . ' for relation "' . $part . '"');
                                    return false;
                                }
                                break;
                            }
                        }

                        if (!$relationFound) {
                            $this->addError($attribute, $currentModelTemplate->fullName . ' does not have a relation "' . $part . '"');
                            return false;
                        }
                    }
                }
            }
        }
        return false;
    }

    public function validatePhpPseudoCalc($attribute, $params, $validator) {
        $code = 'function ($model) { return ' . $this->{$attribute} . '; };';
        try {
            $eval_code = @eval($code);
            if ($eval_code === false) {
                return false;
            }
            return true;
        } catch (\Exception $e) {
            $this->addError($attribute, $e->getMessage());
            return false;
        } catch (\Throwable $e) {
            $this->addError($attribute, $e->getMessage());
            return false;
        }
    }

    public function validateDefaultValue($attribute, $params, $validator) {
        $defaultValue = $this->{$attribute};
        if (preg_match(static::REGEX_CONTAINS_FUNCTION, $defaultValue)) {
            $code = '$x = ' . $defaultValue . ";";
            try {
                @eval($code);
                return true;
            } catch (\Exception $e) {
                $this->addError($attribute, $e->getMessage());
                return false;
            } catch (\Throwable $e) {
                $this->addError($attribute, $e->getMessage());
                return false;
            }
        }
    }


    /**
     * return schema builder for this column
     * @param Migration $migration
     * @return mixed|\yii\db\ColumnSchemaBuilder
     */
    public function getMigrationColumn (Migration $migration) {
        $migrationColumn = null;
        if ($this->type !== "pseudo" && $this->type !== "many_to_many") {
            if ($this->autoInc) {
                $migrationColumn = $migration->primaryKey();
            } else {
                if ($this->type == 'dateTime') {
                    $migrationColumn = call_user_func([$migration, $migration->getDateTimeType()], 0);
                } elseif ($this->type == 'double') {
                    $migrationColumn = call_user_func([$migration, $migration->getDoubleType()]);
                } elseif ($this->type == 'string_multiple') {
                    $migrationColumn = !empty($this->size) ? call_user_func([$migration, 'string'], $this->size) : call_user_func([$migration, 'string']);
                } elseif ($this->type == 'one_to_many') {
                    $migrationColumn = !empty($this->size) ? call_user_func([$migration, 'integer'], $this->size) : call_user_func([$migration, 'integer']);
                } else {
                    $migrationColumn = !empty($this->size) ? call_user_func([$migration, $this->type], $this->size) : call_user_func([$migration, $this->type]);
                }
            }
            $migrationColumn->comment($this->description);
            if ($this->defaultValue > "" && !preg_match(static::REGEX_CONTAINS_FUNCTION, $this->defaultValue)) {
                $value = strtr( $this->defaultValue, ["true" => "1", "false" => "0"]);
                $migrationColumn->defaultValue($value);
            }
        }
        return $migrationColumn;
    }

    public function getPseudoPhpCode() {
        if ($this->type = 'pseudo') {
            if (preg_match('/^\w+(\.\w+)*$/', $this->pseudoCalc)) {
                // this is a dot separated calculation
                $matches = [];
                if (preg_match_all('/(\w+)/', $this->pseudoCalc, $matches)) {
                    $matches = $matches[0];
                    $conditions = [];
                    for ($i=0; $i<sizeof($matches)-1; $i++) {
                        $condition = "\$this";
                        for ($j=0; $j<=$i; $j++) $condition .= "->" . $matches[$j];
                        $conditions[] = $condition;
                    }
                    $calc = "\$this->" . preg_replace('/\./', '->', $this->pseudoCalc);
                    if (sizeof($conditions)) $calc = "(" . implode (" && ", $conditions) . " ? " . $calc . " : null)";
                    return $calc;
                }
            } else
                return $this->pseudoCalc;
        }
        return '';
    }


    public function getPseudoColumnSearchFilter() {
        if ($this->type = 'pseudo' && preg_match('/^\w+(\.\w+)*$/', $this->pseudoCalc)) {
            $matches = [];
            if (preg_match_all('/(\w+)/', $this->pseudoCalc, $matches)) {
                $currentModelTemplate = $this->_model;
                $relations = [];
                foreach ($matches[0] as $part) {
                    if ($currentModelTemplate == null) {
                        $this->addError("pseudoCalc", $part . ' does not exist');
                        return false;
                    }
                    $relationFollowed = false;
                    if ($part == 'parent') {
                        // load and check parent
                        $ownerModelParent = $currentModelTemplate->parentModel;
                        if ($ownerModelParent) {
                            $relation = $currentModelTemplate->getParentForeignKey();
                            if ($relation) $relations[] = $relation->getSqlRelation();
                            $currentModelTemplate = \Yii::$app->templates->getModelTemplate($ownerModelParent);
                            $relationFollowed = true;
                        } else {
                            $this->addError("pseudoCalc", $currentModelTemplate->fullName . ' does not have a parent model');
                            return false;
                        }
                    } else {
                        foreach ($currentModelTemplate->relations as $relation) {
                            if (!$relation->getIsParentRelation()) {
                                $relations[] = $relation->getSqlRelation();
                                $currentModelTemplate = \Yii::$app->templates->getModelTemplateByDataTable($relation->foreignTable);
                                $relationFollowed = true;
                                break;
                            }
                        }
                    }

                    if ($currentModelTemplate && !$relationFollowed) {
                        return [
                            'targetColumn' => $part,
                            'searchType' => $currentModelTemplate->columns[$part]->type,
                            'targetTable' => $currentModelTemplate->table,
                            'relations' => $relations
                        ];
                    }
                }
            }
        }
        return null;
    }

    /**
     * Validates the database structure of the column against that in the model template
     * @param string[] $warnings Warning messages returned
     * @param Migration|null $migration If the problems should be corrected, a migration object can be supplied
     */
    public function validateDatabaseStructure (& $warnings, $migration = null)
    {
        if ($this->type == "pseudo") return;
        if ($this->type == "many_to_many") return;

        $tableSchema = \Yii::$app->db->getTableSchema($this->_model->table);

        $alternativeMigration = new Migration();
        $columnText = "Column '" . $this->name . "'";
        $compareType = strtr($this->type, ["string_multiple" => "string", "boolean" => $alternativeMigration->getBooleanType(), "one_to_many" => "integer"]);
        $compareDefaultValue = strtr( $this->defaultValue, ["true" => "1", "false" => "0"]);

        $migrationColumn = null;
        if ($migration != null) $migrationColumn = $this->getMigrationColumn($migration);
        $alterColumn = false;
        $searchName = $this->name;
        $foundColumns = array_values(array_filter($tableSchema->columnNames, function($name) use($searchName) { return (strtolower($name) == $searchName); }));
        $searchName = $this->oldName;
        $foundRenamedColumns = array_values(array_filter($tableSchema->columnNames, function($name) use($searchName) { return (strtolower($name) == $searchName); }));

        if (sizeof($foundColumns) == 0 && sizeof($foundRenamedColumns) == 0) {
            $warnings[] = $columnText . " does not exist (type:" . $compareType . ($this->size ? ", size:" . $this->size : "") . ($this->defaultValue ? ", defaultValue: '" . $this->defaultValue : "") . ($this->autoInc ? ", AUTOINC, PRIMARY KEY" : "") . ($this->description ? ", comment='" . $this->description . "'" : "") . ")";
            if ($migrationColumn) $migration->addColumn($this->_model->table, $this->name, $migrationColumn);
        }
        else {
            if (sizeof($foundColumns) > 0)
                $columnName = $foundColumns[0];
            else
                $columnName = $foundRenamedColumns[0];

            $columnText = "Column '" . $columnName . "'";
            $column = $tableSchema->getColumn($columnName);
            if ($column->name != $this->name) {
                $warnings[] = $columnText . " should be renamed to '" . $this->name . "'";
                if ($migration) {
                    $migration->renameColumn ($this->_model->table, $columnName, $this->name);
                }
                if ($this->oldName == "") $this->oldName = $column->name;
            }

            if (strtolower($column->type) != strtolower($compareType)) {
                $warnings[] = $columnText . " is of type '" . $column->type . "' but should be of type '" . $compareType . "'";
                $alterColumn = true;
            }

            if (\Yii::$app->db->driverName !== 'sqlsrv' && $this->type != "double" && $this->size && $column->size != $this->size) {
                $warnings[] = $columnText . " has size '" . $column->size . "' but should have size '" . $this->size . "'";
                $alterColumn = true;
            }

            if ($this->defaultValue > "" && !preg_match(static::REGEX_CONTAINS_FUNCTION, $this->defaultValue)) {
                if ($column->defaultValue != $compareDefaultValue) {
                    $warnings[] = $columnText . " has default value '" . $column->defaultValue . "' but should have default value '" . $compareDefaultValue . "'";
                    $alterColumn = true;
                }
            }
            else if ($column->defaultValue > '') {
                $warnings[] = $columnText . " has default value '" . $column->defaultValue . "' but should none";
                $alterColumn = true;
            }

            if ($this->autoInc != $column->isPrimaryKey) {
                if ($column->isPrimaryKey)
                    $warnings[] = $columnText . " should not be primary key";
                else
                    $warnings[] = $columnText . " should be primary key";
                $alterColumn = true;
            }

            if ($this->description !== $column->comment) {
                if ($this->description == "" && $column->comment > "") {
                    $this->description = $column->comment;
                    $this->_model->modifiedAt = time();
                }
                else {
                    $warnings[] = $columnText . " should have the comment '" . $this->description . "'";
                    $alterColumn = true;
                }
            }

            if ($alterColumn && $migrationColumn) {
                $migration->alterColumn($this->_model->table, $this->name, $migrationColumn);
            }
        }
    }

}
