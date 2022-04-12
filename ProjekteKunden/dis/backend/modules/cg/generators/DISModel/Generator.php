<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace app\modules\cg\generators\DISModel;

use app\components\templates\BaseTemplate;
use app\components\templates\ModelTemplate;
use app\components\templates\ModelTemplateColumn;
use Yii;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\db\Connection;
use yii\db\Schema;
use yii\db\TableSchema;
use yii\gii\CodeFile;
use yii\helpers\ArrayHelper;
use yii\helpers\Inflector;
use yii\base\NotSupportedException;
use yii\helpers\Json;


/**
 * This generator will generate one or multiple ActiveRecord classes for the specified template.
 */
class Generator extends \yii\gii\generators\model\Generator
{

    const REGEX_CONTAINS_FUNCTION = "/^(?:'[^\\']*'|" . '"[^\\"]*"' . "|[^'\"\\(]+)*[\\(]/";

    /**
     * @inheritdoc
     */
    public $generateRelationsFromCurrentSchema = false;
    /**
     * @inheritdoc
     */
    public $ns = 'app\models';
    /**
     * @inheritdoc
     */
    public $baseNs = 'app\models\base';
    /**
     * @inheritdoc
     */
    public $baseClassPrefix = 'Base';

    /**
     * @var string the template name to generate files form
     */
    public $templateName = '';
    // public $dataModel = '';

    public $templates = [
        'default' => '@app/modules/cg/generators/DISModel/default',
    ];
    /**
     * @var array parsed template json
     */
    protected $json = [];
    /**
     * deprecated
     */
    protected $otherModels = null;

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'DIS Data Model Generator';
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return 'This generator generates the ActiveRecords classes for the specified json template.';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            ['templateName', 'required'],
            ['templateName', 'string']
        ];
    }


    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'templateName' => 'Model template name'
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function hints()
    {
        return array_merge(parent::hints(), [
            'templateName' => 'Name of the template to be loaded by the corresponding template engine',
        ]);
    }

    /**
     * search templates for current mode ancestor
     * @param $model
     * @return array of ancestor models
     */
    protected function getAncestorModels($model) {
        $ancestorModels = [];
        $parentModel = $this->getParentModel($model);
        while ($parentModel) {
            $ancestorModels[$parentModel->module . $parentModel->name] = $parentModel;
            if ($parentModel->fullName == $model->fullName) {
                break;
            }
            $parentModel = $this->getParentModel($parentModel);
        }
        return $ancestorModels;
    }

    /**
     * This methode will find the related models in the many to many relations
     * @param $data ModelTemplate
     * @param $relationType string
     * @return ModelTemplate[]
     */
    protected function getRelatedModels($model, $relationType) {
        $relatedModels = [];
        foreach ($model->relations as $relation) {
            if(isset($relation->relationType) && $relation->relationType == $relationType) {
                $relatedModels[] = Yii::$app->templates->getModelTemplate(ucfirst(Inflector::id2camel($relationType == 'nm' ? $relation->relatedTable : $relation->foreignTable, "_")));
            }
        }
        return $relatedModels ? $relatedModels : null;
    }

    /**
     * return the current model parent
     * @param $model
     * @return ModelTemplate|null
     */
    protected function getParentModel($model) {
        if ($model->parentModel > "")
            return Yii::$app->templates->getModelTemplate($model->parentModel);
        else
            return null;
    }

    /**
     * generate filterDataModels definition to be used in the form template (see user-help/form-structure)
     * @param $model
     * @return array of filter data model definition
     */
    protected function getAncestorFormFilters($model) {
        $formFilters = [];
        $aAncestorModels = array_reverse($this->getAncestorModels($model));
        $cNextRequireValue = "";
        $cNextRequireAs = "";
        foreach ($aAncestorModels as $ancestorName => $ancestor) {
            $isSelfParentReference = $ancestor->fullName == $model->fullName;
            $filterValueColumn = $this->getFilterValueColumn($ancestor, $model);
            if ($filterValueColumn > "") {
                $cName = $isSelfParentReference ? 'parent' : lcfirst($ancestor->name);
                $referenceColumn = Inflector::camel2id(trim(preg_replace('/[0-9]+/', '-$0-', $isSelfParentReference ? 'parent' : $ancestor->name)), '_') . '_id';
                $formFilter = '"' . $cName . '" => ["model" => "' . $ancestorName . '", "value" => "id", "text" => "' . $filterValueColumn . '", "ref" => "' . $referenceColumn . '"';
                if ($cNextRequireValue > "") {
                    $formFilter .= ', "require" => ["value" => "' . $cNextRequireValue . '", "as" => "' . $cNextRequireAs . '"]';
                }
                $formFilter .= ']';
                $formFilters[] = $formFilter;
                $cNextRequireValue = $cName;
                $cNextRequireAs = $cName . '_id';
            }
        }
        return $formFilters;
    }

    protected function getArchiveFileFiltersNames($model) {
        $filtersNames = [];
        $aAncestorModels = array_reverse($this->getAncestorModels($model));
        foreach ($aAncestorModels as $ancestorName => $ancestor) {
            $isSelfParentReference = $ancestor->fullName == $model->fullName;
            $filterValueColumn = $this->getFilterValueColumn($ancestor, $model);
            if ($filterValueColumn > "" && !$isSelfParentReference) {
                $filtersNames[] = lcfirst($ancestor->name);
            }
        }
        return $filtersNames;
    }

    protected function getFilesColumn($model) {
        $cFilesColumn = null;
        if ($model->getFullName() !== "ArchiveFile") {
            $oFilesTemplate = \Yii::$app->templates->getModelTemplate('ArchiveFile');
            if ($oFilesTemplate) {
                foreach ($oFilesTemplate->relations as $oRelation) {
                    if ($oRelation->foreignTable == $model->table) {
                        $cFilesColumn = $oRelation->localColumns[0];
                        break;
                    }
                }
            }
        }
        return $cFilesColumn;
    }

    /**
     * return the column that holds the fk column value
     * @param $jsonModel ModelTemplate
     * @param null $referenceJsonModel
     * @return string column name
     */
    protected function getFilterValueColumn($jsonModel, $referenceJsonModel = null) {
        switch ($jsonModel->module . $jsonModel->name) {
            case "ProjectExpedition":
                return "exp_acronym";

            case "ProjectProgram":
                if ($referenceJsonModel && $referenceJsonModel->module == "Project" && $referenceJsonModel->name == "Expedition")
                    return "program_name";
                else
                    return "";

            default:
                return $this->generateNameAttribute($jsonModel);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function requiredTemplates()
    {
        // @todo make 'query.php' to be required before 2.1 release
        return ['model.php'/*, 'query.php'*/];
    }

    /**
     * {@inheritdoc}
     */
    public function stickyAttributes()
    {
        return array_merge(parent::stickyAttributes(), ['templateName']);
    }

    /**
     * @param TableSchema $table
     * @param ModelTemplate $jsonModel
     * @return array of column labels
     */
    public function generateLabels($table, $jsonModel = null) {
        $labels = parent::generateLabels($table);
        foreach ($this->generatePseudoColumns($jsonModel) as $pseudoColumnName => $pseudoColumn) {
            $labels[$pseudoColumnName] = Inflector::camel2words($pseudoColumnName);
        }
        foreach ($this->getManyToManyColumns($jsonModel) as $manyToManyColumnName => $manyToManyColumn) {
            $labels[$manyToManyColumnName] = $manyToManyColumnName;
        }
        foreach ($this->getOneToManyColumns($jsonModel) as $oneToManyColumnName => $oneToManyColumn) {
            $labels[$oneToManyColumnName] = $oneToManyColumnName;
        }
        foreach ($labels as $name => $label) {
            if (isset($jsonModel->columns[$name])) {
                $column = $jsonModel->columns[$name];
                if ($column->label > '') {
                    $labels[$name] = $column->label;
                }
            }
        }
        return $labels;
    }

    /**
     * return the search type to be used depending on the model column type
     * @param $columnType string
     * @return string search type
     */
    public function getSearchType ($columnType) {
        switch ($columnType) {
            case 'integer':
            case 'double':
                return 'number';
            case 'dateTime':
                return 'datetime';
            case 'boolean':
            case 'string':
                return $columnType;
            default:
                return 'string';
        }
    }

    public function generateProperties($table, $jsonModel = null) {
        $properties = parent::generateProperties($table);
        foreach ($properties as & $property) {
            if (isset($jsonModel->columns[$property["name"]])) {
                $column = $jsonModel->columns[$property["name"]];
                if ($column->label > '') {
                    $property["comment"] = $column->label;
                }
                $property["searchType"] = $this->getSearchType($column->type);
            }
            else
                $property["searchType"] = null;
        }
        /*foreach ($jsonModel->relations as $jsonModelRelation) {
            if ($jsonModelRelation->relationType == "nm") {
                $className = $this->generateClassName($jsonModelRelation->relatedTable);
                $relatedModel = ModelTemplate::find($className);
                $propertyName = lcfirst($relatedModel->name);
                $properties[$propertyName] = [
                    "type" => "integer|null",
                    "name" => $propertyName,
                    "comment" => "",
                    "searchType" => $this->getSearchType("integer|null")
                ];
            }
        }*/
        return $properties;
    }

    /**
     * generate the model rules
     * @param TableSchema $table
     * @param ModelTemplate $jsonModel
     * @return array of rules definitions
     */
    public function generateRules($table, $jsonModel = null) {
        $rules = parent::generateRules($table);

        $customValidators = [];
        $validatorColumns = [
            'required' => [],
            'boolean' => [],
            '\app\components\validators\DateValidator' => [],
            '\app\components\validators\DateTimeValidator' => [],
            '\app\components\validators\MultipleValuesStringValidator' => []
        ];
        $validatorOptions = [
            'required' => "",
            'boolean' => "",
            '\app\components\validators\DateValidator' => "",
            '\app\components\validators\DateTimeValidator' => "",
            '\app\components\validators\MultipleValuesStringValidator' => ""
        ];

        foreach ($jsonModel->columns as $column) {
            if ($column->required) {
                $validatorColumns['required'][] = $column->name;
            }
            switch ($column->type) {
                case 'boolean':
                    $validatorColumns['boolean'][] = $column->name;
                    break;
                case 'string_multiple': {
                    $validatorColumns['\app\components\validators\MultipleValuesStringValidator'][] = $column->name;
                    break;
                }
                case 'dateTime':
                    $validatorColumns['\app\components\validators\DateTimeValidator'][] = $column->name;
                    break;
                case 'date':
                    $validatorColumns['\app\components\validators\DateValidator'][] = $column->name;
                    break;

            }

            if ($column->validator > '') {
                $customValidators = array_merge($customValidators, $this->getColumnRules($column));
            }
        }

        foreach ($validatorColumns AS $validatorType => $columns) {
            if (sizeof($columns) > 0) {
                if ($validatorType === '\app\components\validators\MultipleValuesStringValidator') {
                    // prepend to array to update the attribute (from array to string) before calling string validator
                    array_unshift($rules, "[['" . implode("', '", $validatorColumns[$validatorType]) . "'],'" . $validatorType . "'" . $validatorOptions[$validatorType] . "]");
                } else {
                    $rules[] = "[['" . implode("', '", $validatorColumns[$validatorType]) . "'],'" . $validatorType . "'" . $validatorOptions[$validatorType] . "]";
                }
            }
        }

        $rules = array_values(array_merge($rules, $customValidators));
        return $rules;
    }

    /**
     * return the rules of a column
     * @param $columnJson
     * @return array of rules definitions
     */
    protected function getColumnRules($columnJson) {
        $rules = [];

        $matches = [];
        $validator = trim($columnJson->validator);
        if (preg_match('/^(integer|numeric) value ?(.*)$/', $validator, $matches)) {
            // $rules[] = [$columnJson->name, $matches[1]];
            $validator = trim($matches[2]);
        }

        if (preg_match('/^([<>=!]+)(.+)$/', $validator, $matches)) {
            $compareValue = trim($matches[2]);
            $operator = $matches[1];
            switch ($operator) {
                case '<>':
                    $operator = '!==';
                    break;
            }
            $rules[] = "[['" . $columnJson->name . "'], 'compare', 'operator' => '" . $operator . "', 'compareValue' => " . $this->generateString($compareValue) . ", 'type' => '" . (in_array($columnJson->type, ['string', 'text']) ? 'string' : 'number') . "']";
        }
        else if (preg_match('/^between (.+) and (.+)$/i', $validator, $matches)) {
            $compareValue = trim($matches[1]);
            $rules[] = "[['" . $columnJson->name . "'], 'compare', 'operator' => '>=', 'compareValue' => " . $this->generateString($compareValue) . ", 'type' => '" . (in_array($columnJson->type, ['string', 'text']) ? 'string' : 'number') . "']";
            $compareValue = trim($matches[2]);
            $rules[] = "[['" . $columnJson->name . "'], 'compare', 'operator' => '<=', 'compareValue' => " . $this->generateString($compareValue) . ", 'type' => '" . (in_array($columnJson->type, ['string', 'text']) ? 'string' : 'number') . "']";
        }
        else if (preg_match('/^in +\((.+)\)$/i', $validator, $matches)) {
            $values = array_map('trim', explode(',', trim($matches[1])));
            array_walk($values, function(&$val, $index){
                if (filter_var($val, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) !== NULL) {
                    $val = filter_var($val, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ? 'true' : 'false';
                } else if (floatVal($val) == 0 && $val !== "0" && !preg_match("/^('|\").*('|\")$/", $val)) {
                    $val = $this->generateString($val);
                }
            });
            $rules[] = "[['" . $columnJson->name . "'], '\\yii\\validators\\RangeValidator', 'range' => [" . implode(", ", $values) . "], 'strict' => false]";
        }
        else if (preg_match('/^LIKE +\[(.+)\]/i', $validator, $matches)) {
            $regexp = trim($matches[1]);
            $rules[] = "[['" . $columnJson->name . "'], 'regularExpression', 'pattern' => " . $this->generateString($regexp) . "]";
        }


        if ($columnJson->validatorMessage > '') {
            foreach ($rules as $i => $rule) {
                $rules[$i] = rtrim($rule, "]") . ", 'message' => '" .  $columnJson->validatorMessage . "']";
            }
        }

        return $rules;
    }

    /**
     * @param $jsonModel ModelTemplate
     * @return array of search filters
     */
    protected function generateSearchFilters($jsonModel) {
        $filters = [];
        $ancestorModels = array_values($this->getAncestorModels($jsonModel));
        for ($i=0; $i<sizeof($ancestorModels); $i++) {
            $ancestor = $ancestorModels[$i];
            $filter = new \stdClass();
            $filter->table = $ancestor->table;
            foreach ($jsonModel->relations as $relation) {
                if ($relation->foreignTable == $filter->table) {
                    $filter->foreignColumn = $relation->foreignTable . "." . $relation->foreignColumns[0];
                    $filter->localColumn = $jsonModel->table . "." . $relation->localColumns[0];
                }
            }
            if ($i+1 < sizeof($ancestorModels)) {
                $filter->attribute = strtolower($ancestorModels[$i+1]->name) . '_id';
                $filters[] = $filter;
            }
            $jsonModel = $ancestor;
        }
        return array_reverse($filters);
    }



    public function generateSearchRules($table, $jsonModel = null) {
        $rules = [];
        $validatorColumns = [
            'safe' => [],
        ];
        $searchableColumns = array_filter($jsonModel->columns, function ($c) { return $c->searchable; });
        foreach ($searchableColumns as $column) {
            $validatorColumns['safe'][] = $column->name;
        }
        foreach ($this->generateSearchFilters($jsonModel) as $searchFilter) {
            $validatorColumns['safe'][] = $searchFilter->attribute;
        }

        foreach ($validatorColumns AS $validatorType => $columns) {
            if (sizeof($columns) > 0) {
                $rules[] = "[['" . implode("', '", $validatorColumns[$validatorType]) . "'],'" . $validatorType . "']";
            }
        }
        return $rules;
    }

    /**
     * Provide the static default values of columns.
     * Default-Value of column "igsn" contains the objectTag for the IgsnBehavior.
     * @param $jsonModel
     * @return array [<name> => <default value>]
     */
    protected function generateDefaultValues($jsonModel) {
        $defaultValues = [];
        foreach ($jsonModel->columns as $column) {
            if ($column->defaultValue !== '') {
                $defaultValue = $column->defaultValue;
                if (!preg_match(static::REGEX_CONTAINS_FUNCTION, $defaultValue)) {
                    switch ($column->type) {
                        case 'string':
                        case 'text':
                            $defaultValue = $this->generateString($defaultValue);
                            break;
                    }
                    $defaultValues[$column->name] = $defaultValue;
                }
            }
        }
        return $defaultValues;
    }

    /**
     * Provide the default values of columns that contain function calls.
     * igsn columns may not contain default values.
     * @param $jsonModel
     * @return array [<name> => <default value>]
     */
    protected function generateDefaultValuesWithFunctions($jsonModel) {
        $defaultValues = [];
        foreach ($jsonModel->columns as $column) {
            if ($column->name == 'igsn') continue;
            if ($column->defaultValue !== '') {
                $defaultValue = $column->defaultValue;
                if (preg_match(static::REGEX_CONTAINS_FUNCTION, $defaultValue)) {
                    $defaultValues[$column->name] = $defaultValue;
                }
            }
        }
        return $defaultValues;
    }

    protected function findIndex($array, $value)
    {
        foreach($array as $key => $val)
        {
            if(is_array($val)) {
                foreach ($val as $subArrayValue) {
                    if ( $subArrayValue === $value ) {
                        return $key;
                    }
                }
            } else {
                if ( $val[$key] === $value ) {
                    return $key;
                }
            }
        }
        return false;
    }

    protected function generateRelations($jsonModel=null) {
        $relations = parent::generateRelations();
         if (isset($relations[$jsonModel->table]))
             $relations = $relations[$jsonModel->table];
        else
            $relations = [];

        foreach ($relations as $relationKey => $relation) {
            foreach ($jsonModel->relations as $jsonModelRelation) {
                if($jsonModelRelation->relationType == "1n") {
                    $relationName = $this->generateRelationName($relations, $this->getDbConnection()->getTableSchema($jsonModel->table), $jsonModelRelation->localColumns[0], false);
                    if($relationKey == $relationName) {
                        $newRelationName = ucfirst(Inflector::id2camel($jsonModelRelation->localColumns[0],'_'))."1n";
                        $oneToManyRelation = [
                            "return \$this->hasOne" . "(".ucfirst(Inflector::id2camel($jsonModelRelation->foreignTable,'_'))."::className(), ['id' => '".$jsonModelRelation->localColumns[0]."']);",
                            $relation[1],
                            false
                        ];
                        $relations[$newRelationName] = $oneToManyRelation;
                        $index = $this->findIndex($relations, $relation[1]);
                        if(isset($relations[$index])) {
                            unset($relations[$index]);
                        }
                    }
                }
                if($jsonModelRelation->relationType == "nm") {
                    if($relation[1] == ucfirst(Inflector::id2camel($jsonModelRelation->relatedTable,'_')) . ucfirst(Inflector::id2camel($jsonModel->table, '_'))) {
                        $newRelationName = ucfirst(Inflector::id2camel($jsonModelRelation->localColumns[0],'_'))."Nm";
                        $manyToManyRelation = [
                            "return \$this->hasMany" . "(".ucfirst(Inflector::id2camel($jsonModelRelation->relatedTable,'_'))."::className(), ['id' => '".$jsonModelRelation->relatedTable."_id'])->viaTable('".$jsonModelRelation->relatedTable."_".$jsonModel->table."', ['".$jsonModel->table."_id' => 'id']);",
                            ucfirst(Inflector::id2camel($jsonModelRelation->relatedTable,'_')),
                            true
                        ];
                        $relations[$newRelationName] = $manyToManyRelation;
                        $index = $this->findIndex($relations, $relation[1]);
                        if(isset($relations[$index])) {
                            unset($relations[$index]);
                        }
                    }

                    if($relation[1] == ucfirst(Inflector::id2camel($jsonModel->table, '_')) . ucfirst(Inflector::id2camel($jsonModelRelation->relatedTable,'_'))) {
                        $newRelationName = ucfirst(Inflector::id2camel($jsonModelRelation->localColumns[0],'_'))."Nm";
                        $manyToManyRelation = [
                            "return \$this->hasMany" . "(".ucfirst(Inflector::id2camel($jsonModelRelation->relatedTable,'_'))."::className(), ['id' => '".$jsonModelRelation->relatedTable."_id'])->viaTable('".$jsonModel->table."_".$jsonModelRelation->relatedTable."', ['".$jsonModel->table."_id' => 'id']);",
                            ucfirst(Inflector::id2camel($jsonModelRelation->relatedTable,'_')),
                            true
                        ];
                        $relations[$newRelationName] = $manyToManyRelation;
                        $index = $this->findIndex($relations, $relation[1]);
                        if(isset($relations[$index])) {
                            unset($relations[$index]);
                        }
                    }
                }
            }
        }

        $parentModel = $this->getParentModel($jsonModel);
        if ($parentModel) {
            $parentShortName = ucfirst($parentModel->name);
            foreach ($relations as $name => $relation) {
                if ($name == $parentShortName) {
                    $relations["Parent"] = ['return $this->get' . $name . '();', $relation[1], $relation[2]];
                    break;
                }
            }

            $i = 0;
            foreach ($this->getAncestorModels($jsonModel) as $name => $ancestor) {
                $i++;
                if ($ancestor->name !== $parentShortName) {
                    $relations[$ancestor->name] = ['return $this' . str_repeat('->parent', $i) . ';', $name, false];
                }
            }
        }

        return $relations;
    }

    protected function generateCalculateProperties ($jsonModel) {
        $calculatedProps = [];
        foreach ($jsonModel->columns as $column) {
            if ($column->calculate > '' && !preg_match('/^A_\d/', $column->calculate)) {
                $calculate = preg_replace('/\[([a-zA-Z_]+)\]/', '$this->$1', $column->calculate);
                $calculate = preg_replace ('/^= */', '', $calculate);
                $calculate = preg_replace_callback ('/([A-Z]+)\(/', function($matches){
                    return strtolower($matches[1] . '(');
                }, $calculate);

                $calculatedProps[$column->name] = $calculate;
            }
        }
        return $calculatedProps;
    }

    protected function generateNameAttribute ($jsonModel) {
        $valueColumn = Inflector::camel2id(trim(preg_replace('/[0-9]+/', '_$0_', $jsonModel->name)), '_');
        $valueColumn = str_replace('__', '_', $valueColumn);
        if (isset($jsonModel->columns[$valueColumn])) {
            return $valueColumn;
        }
        else {
            foreach ($jsonModel->columns as $column) {
                if ($column->required && !preg_match("/_id$/", $column->name)) {
                    return $column->name;
                }
            }
        }
        return 'id';
    }

    /**
     * @param $model ModelTemplate
     * @return array of columns names
     */
    protected function getMultliValueStringColumns ($model) {
        $columns = [];
        foreach ($model->columns as $column) {
            if ($column->type === 'string_multiple') {
                $columns[] = $column->name;
            }
        }
        return $columns;
    }

    /**
     * @param $model ModelTemplate
     * @return array of columns names
     */
    protected function getManyToManyColumns ($model) {
        $columns = [];
        $relatedModels = $this->getRelatedModels($model, 'nm');
        if(is_array($relatedModels)) {
            foreach($relatedModels as $relatedModel) {
                foreach ($model->columns as $column) {
                    if ($column->type === 'many_to_many' && $relatedModel->table === $column->relatedTable) {
                        $columns[$column->name] = [
                            'attribute' => $column->name,
                            'relatedTable' => $relatedModel->table,
                            'displayColumn' => $column->displayColumn,
                            'searchType' => $relatedModel->columns[$column->displayColumn]->type,
                            'relationName' => lcfirst(\yii\helpers\Inflector::id2camel($column->name, "_"))."Nm",
                            'oppositionRelation' => $column->oppositionRelation
                        ];
                    };
                }
            }
        }

        return $columns;
    }

    protected function getOneToManyColumns ($model) {
        $columns = [];
        $relatedModels = $this->getRelatedModels($model, '1n');
        if(is_array($relatedModels)) {
            foreach($relatedModels as $relatedModel) {
                foreach ($model->columns as $column) {
                    if ($column->type === 'one_to_many' && $relatedModel->table === $column->relatedTable) {
                        $columns[$column->name] = [
                            'attribute' => $column->name,
                            'relatedTable' => $relatedModel->table,
                            'displayColumn' => $column->displayColumn,
                            'searchType' => $relatedModel->columns[$column->displayColumn]->type,
                            'relationName' => lcfirst(\yii\helpers\Inflector::id2camel($column->name, '_'))."1n"
                        ];
                    };
                }
            }
        }

        return $columns;
    }

    /**
     * @param $model ModelTemplate
     * @return string[] of behaviors
     */
    protected function generateBehaviors ($model) {
        $behaviors = [];
        foreach ($model->behaviors as $key => $behavior) {
            $behaviors[$key] = "['class' => '" . $behavior['behaviorClass'] . "'";
            foreach ($behavior->parameters as $param => $val) {
                $behaviors[$key] .= ", '$param' => ";
                if (is_string($val)) {
                    $behaviors[$key] .= "'$val'";
                } elseif (ArrayHelper::isIndexed($val, true)) { // sequential array
                    $behaviors[$key] .= "[";
                    foreach ($val as $valKey => $valValue) {
                        $behaviors[$key] .= (is_numeric($valValue) ? "$valValue" : "'$valValue'");
                    }
                    $behaviors[$key] .= "]";
                } elseif (is_numeric($val)) {
                    $behaviors[$key] .= "$val";
                } elseif (is_bool($val)) {
                    $behaviors[$key] .= $val ? "true" : "false";;
                }
            }
            $behaviors[$key] .= "]";
        }
        return $behaviors;
    }

    /**
     * @param $model ModelTemplate
     */
    protected function generatePseudoColumns ($model) {
        $result = [];
        $pseudoColumns = array_filter($model->columns, function ($c) { return $c->type == 'pseudo'; });
        foreach ($pseudoColumns as $pseudoColumn) {
            $result[$pseudoColumn->name] = $pseudoColumn->getPseudoPhpCode();
        }
        return $result;
    }

    protected function generatePseudoColumnsSearchFilters($model) {
        $results = [];
        /* @var $pseudoColumns ModelTemplateColumn[] */
        $pseudoColumns = array_filter($model->columns, function ($c) { return $c->type == 'pseudo' && preg_match('/^\w+(\.\w+)*$/', $c->pseudoCalc); });
        $ancestors = array_values($this->getAncestorModels($model));
        foreach ($pseudoColumns as $pseudoColumn) {
            $result = $pseudoColumn->getPseudoColumnSearchFilter();
            if ($result)  $results[$pseudoColumn->name] = $result;
        }
        return $results;
    }

    /**
     * Creates a PHP source code comment for an error message
     * @param $error Error message
     * @return string PHP source code
     */
    protected function getErrorMessageSourceCode($error) {
        \Yii::error("Generator.generateSpecializationsSourceCode(): " . $error);
        $src = "/**********************************************\n";
        $src .=" * " . $error . "\n";
        $src .=" **********************************************/ \n";
        return $src;
    }

    /**
     * Creates specialized source code that is being inserted into the generated file.
     * If a specialized class file for the generated class exists in sub folder "specializations" the source code of the
     * contained class is copied.
     * - A "static function __processGeneratorParams" is not copied but used below in processGeneratorParams()
     *
     * @param $className Name of the Class to generate / the template class to use, if exist
     * @return string|string[] PHP source code to insert into the generated class
     */
    protected function generateSpecializationsSourceCode ($className) {
        $source = "";
        $fullClassName = "\\app\\modules\\cg\\generators\\DISModel\\specializations\\" . $className;
        $filename = dirname(__FILE__) . "/specializations/" . $className . ".php";
        if (file_exists($filename)) {
            try {
                require($filename);
                if (!class_exists($fullClassName, false)) {
                    return $this->getErrorMessageSourceCode("Missing class name / wrong namespace in specializations file " . $filename . "? Ignoring this file.");
                }
            }
            catch (\ParseError $e){
                return $this->getErrorMessageSourceCode("Parse error in specialization file " . $filename . ": Line " . $e->getLine() . ", " . $e->getMessage() . "! Ignoring this file.");
            }

            $class = new \ReflectionClass($fullClassName);
            $startLine = $class->getStartLine();
            $endLine = $class->getEndLine();

            $lines = file($filename);
            $lines = array_slice($lines, $startLine, $endLine - $startLine);
            $lines[0] = preg_replace("/^{/", "", trim($lines[0]));
            $lines[sizeof($lines)-1] = preg_replace("/}$/", "", trim($lines[sizeof($lines)-1]));

            try {
                $method = new \ReflectionMethod($fullClassName, '__processGeneratorParams');
                if (!$method->isStatic() || !$method->isPublic()) {
                    return $this->getErrorMessageSourceCode("MethodParse error in specialization file " . $filename . ": Line " . $e->getLine() . ", " . $e->getMessage() . "! Ignoring this file.");
                }

                $startLine2 = $method->getStartLine() - $startLine - 1;
                $endLine = $method->getEndLine() - $startLine;
                array_splice($lines, $startLine2, $endLine - $startLine2);
            }
            catch (\ReflectionException $e) {
                // Method not there, ignore
            }

            $source = implode("", $lines);
        }
        $source = trim(str_replace("\r", "", $source), "\n");
        return $source;
    }

    /**
     * If a specialized class file for the generated class exists in sub folder "specializations"
     * and that class contains a method "__processGeneratorParams", the parameters from the generator
     * that are used in the template file (i.e. "default/baseModel.php") is run through that method
     * which can freely modify it.
     * This way it is possible to modify all aspects of the generated class.
     *
     * @param $className Name of the Class to generate / the template class to use, if exist
     * @param $params Parameters from the generator that are used in the template file (i.e. "default/baseModel.php")
     * @return array Optionally modified parameters
     */
    protected function processGeneratorParams($className, $params) {
        $fullClassName = "\\app\\modules\\cg\\generators\\DISModel\\specializations\\" . $className;
        if (class_exists($fullClassName, false)) {
            if (method_exists($fullClassName, "__processGeneratorParams")) {
                try {
                    $changedParams = call_user_func([$fullClassName, "__processGeneratorParams"], $params);
                    if (is_array($changedParams) && sizeof(array_diff_key($params, $changedParams)) == 0)
                        $params = $changedParams;
                    else
                        \Yii::error("Generator.processGeneratorParams(): Result of calling method '__processGeneratorParams' in class '" . $className . "' has invalid results");
                }
                catch (\Exception $e) {
                    \Yii::error("Generator.processGeneratorParams(): Exception calling method '__processGeneratorParams' in class '" . $className . "':" . $e->getMessage());
                }
            }
        }
        return $params;
    }

    public function generateCustomUses($modelRelations, $connectionModelRelationsSets) {
        $uses = [];
        foreach ($modelRelations as $name => $relation) {
            if(!in_array($relation[1], $uses)) {
                $uses[] = $relation[1];
            }
        }

        if($connectionModelRelationsSets) {
            foreach ($connectionModelRelationsSets as $connectionModelRelations) {
                foreach ($connectionModelRelations as $connectionModelRelationName => $connectionModelRelation) {
                    if(!in_array($connectionModelRelation[1], $uses)) {
                        $uses[] = $connectionModelRelation[1];
                    }
                }
            }
        }

        return $uses;
    }

    public function generate()
    {
        $model = Yii::$app->templates->getModelTemplate($this->templateName);
        if (!$model) die ("Generator.generate() Model not found: " . $this->templateName);
        $db = $this->getDbConnection();
        $this->tableName = $model->table;
            $this->baseClass = preg_replace("/base$/", "core", $this->baseNs) . "\Base";
        $tableSchema = $db->getTableSchema($model->table);
        $modelClassName = $model->module . $model->name;
        $parentModelClassName = $model->parentModel;
            if ($db->schema->getTableSchema($this->tableName) === null) {
                throw new \Exception ("Cannot generate files for model " . $modelClassName . ": Table " . $this->tableName . " does not exist!");
            }
        if(isset($model->relations)) {
            $connectionModels = [];
            foreach ($model->relations as $relation) {
                if (isset($relation->relationType) && $relation->relationType == 'nm') {
                    if(!$relation->oppositionRelation){
                        $templateData = $model->generateConnectionModelTemplateData($relation);
                        $connectionModel = \Yii::createObject(ModelTemplate::class);
                        $connectionModel->load($templateData,'');
                        $connectionModelfilename = tempnam(sys_get_temp_dir(),$connectionModel->name);
                        $connectionModelClassName = $connectionModel->name;
                        $connectionModels[] = [
                            "connectionModelData" => $this->generateConnectionModel($connectionModel, $connectionModelClassName, $relation->relatedTable),
                            "connectionModelfileName" => $connectionModelfilename,
                            "connectionModelClassName" => $connectionModelClassName
                        ];
                    }
                }
            }
        }

        $filename = Yii::getAlias('@' . str_replace("\\", "/", $this->baseNs) . '/' . Inflector::id2camel($this->baseClassPrefix . $modelClassName) . '.php');
        $relations = $this->generateRelations($model);

        $connectionModelRelationsSets = [];
        $connectionModelsFiles = [];
        foreach ($connectionModels as $connectionModel) {
            $connectionModelRelationsSets[] = $connectionModel["connectionModelData"]["relations"];
            $connectionModelsFiles[] = new CodeFile(
                $connectionModel["connectionModelfileName"],
                $this->render('connectionModel.php', $this->processGeneratorParams($this->baseClassPrefix . $connectionModel["connectionModelClassName"], $connectionModel["connectionModelData"])));
        }
        $uses = $this->generateCustomUses($relations, $connectionModelRelationsSets);


        $files[] = new CodeFile(
            $filename,
            $this->render('baseModel.php', $this->processGeneratorParams($this->baseClassPrefix . $modelClassName, [
                'uses' => $uses,
                'tableName' => $model->table,
                'properties' => $this->generateProperties($tableSchema, $model),
                'calculateProperties' => $this->generateCalculateProperties ($model),
                'defaultValues' => $this->generateDefaultValues($model),
                'defaultValuesWithFunctions' => $this->generateDefaultValuesWithFunctions($model),
                'relations' => $relations,
                'className' => $modelClassName,
                'shortName' => $model->name,
                'filterValueColumn' => $this->getFilterValueColumn($model),
                'parentClassName' => $parentModelClassName,
                'labels' => $this->generateLabels($tableSchema, $model),
                'rules' => $this->generateRules($tableSchema, $model),
                'ancestorModels' => $this->getAncestorModels($model),
                'ancestorFormFilters' => $this->getAncestorFormFilters($model),
                'archiveFileFiltersNames' => $this->getArchiveFileFiltersNames($model),
                'json' => Json::encode($model),
                'module' => $model->module,
                'templateName' => $model->name,
                'nameAttribute' => $this->generateNameAttribute($model),
                'multiValueStringColumns' => $this->getMultliValueStringColumns($model),
                'manyToManyColumns' => $this->getManyToManyColumns($model),
                'oneToManyColumns' => $this->getOneToManyColumns($model),
                'behaviors' => $this->generateBehaviors($model),
                'pseudoColumns' => $this->generatePseudoColumns($model),
                'specializationsSourceCode' => $this->generateSpecializationsSourceCode($this->baseClassPrefix . $modelClassName),
                'connectionModelsData' => $connectionModelsFiles,
                'filesColumn' => $this->getFilesColumn($model)
            ]))
        );

        $searchModelClassName = $modelClassName . "Search";
        $filename = Yii::getAlias('@' . str_replace("\\", "/", $this->baseNs) . '/' . Inflector::id2camel($this->baseClassPrefix . $searchModelClassName) . '.php');
        $files[] = new CodeFile(
            $filename,
            $this->render('baseSearchModel.php', $this->processGeneratorParams($this->baseClassPrefix . $searchModelClassName, [
                'searchClassName' => $searchModelClassName,
                'className' => $modelClassName,
                'tableName' => $model->table,
                'shortName' => $model->name,
                'properties' => $this->generateProperties($tableSchema, $model),
                'parentClassName' => $parentModelClassName,
                'labels' => $this->generateLabels($tableSchema, $model),
                'rules' => $this->generateSearchRules($tableSchema, $model),
                'searchFilters' => $this->generateSearchFilters($model),
                'pseudoColumnsSearchFilters' => $this->generatePseudoColumnsSearchFilters($model),
                'module' => $model->module,
                'specializationsSourceCode' => $this->generateSpecializationsSourceCode($this->baseClassPrefix . $searchModelClassName),
                'manyToManyColumns' => $this->getManyToManyColumns($model),
                'oneToManyColumns' => $this->getOneToManyColumns($model),
            ]))
        );



        $filename = Yii::getAlias('@' . str_replace("\\", "/", $this->ns) . '/' . Inflector::id2camel(ucfirst($model->module) . $model->name) . 'Search.php');
        if (!file_exists($filename)) {
            $files[] = new CodeFile(
                $filename,
                $this->render('emptySearchModel.php', $this->processGeneratorParams($searchModelClassName, [
                    'searchClassName' => $searchModelClassName,
                    'className' => $modelClassName,
                    'specializationsSourceCode' => $this->generateSpecializationsSourceCode($searchModelClassName)
                ]))
            );
        }

        $filename = Yii::getAlias('@' . str_replace("\\", "/", $this->ns) . '/' . Inflector::id2camel(ucfirst($model->module) . $model->name) . '.php');
        if (!file_exists($filename)) {
            $files[] = new CodeFile(
                $filename,
                $this->render('emptyModel.php', $this->processGeneratorParams($modelClassName, [
                    'className' => $modelClassName,
                    'specializationsSourceCode' => $this->generateSpecializationsSourceCode($modelClassName),
                ]))
            );
        }

        return $files;
    }

    /**
     * This methode generate the php model of the connection table
     * @param $connectionModel ModelTemplate the template of the connection model
     * @param $modelClassName string the calss name of the current model
     * @param $relatedTable string the name of the related table
     * @return array
     */
    public function generateConnectionModel($connectionModel, $modelClassName, $relatedTable) {
        $db = $this->getDbConnection();
        $tableSchema = $db->getTableSchema($connectionModel->table);
        $parentModelClassName = $connectionModel->parentModel;

        return [
            'tableName' => $connectionModel->table,
            'properties' => $this->generateProperties($tableSchema, $connectionModel),
            'calculateProperties' => $this->generateCalculateProperties ($connectionModel),
            'defaultValues' => $this->generateDefaultValues($connectionModel),
            'defaultValuesWithFunctions' => $this->generateDefaultValuesWithFunctions($connectionModel),
            'relations' => $this->generateRelations($connectionModel),
            'className' => $modelClassName,
            'shortName' => $connectionModel->name,
            'filterValueColumn' => $this->getFilterValueColumn($connectionModel),
            'parentClassName' => $parentModelClassName,
            'labels' => $this->generateLabels($tableSchema, $connectionModel),
            'rules' => $this->generateRules($tableSchema, $connectionModel),
            'ancestorModels' => $this->getAncestorModels($connectionModel),
            'ancestorFormFilters' => $this->getAncestorFormFilters($connectionModel),
            'json' => Json::encode($connectionModel),
            'module' => $connectionModel->module,
            'nameAttribute' => $this->generateNameAttribute($connectionModel),
            'multiValueStringColumns' => $this->getMultliValueStringColumns($connectionModel),
            'behaviors' => $this->generateBehaviors($connectionModel),
            'pseudoColumns' => $this->generatePseudoColumns($connectionModel),
            'specializationsSourceCode' => $this->generateSpecializationsSourceCode($this->baseClassPrefix . $modelClassName),
            'relatedTable' => $relatedTable
        ];
    }

    // no need for empty model for the coonection model.
    /*public function generateEmptyConnectionModel($connectionModel) {
        $modelClassName = $connectionModel->name;
        $emptyConnectionModelfilename = Yii::getAlias('@' . str_replace("\\", "/", $this->ns) . '/' . $modelClassName . '.php');
        return new CodeFile(
            $emptyConnectionModelfilename,
            $this->render('emptyModel.php', $this->processGeneratorParams($modelClassName, [
                'className' => $modelClassName,
                'specializationsSourceCode' => $this->generateSpecializationsSourceCode($modelClassName),
            ]))
        );
    }*/

    public function save($files, $answers, &$results)
    {
        $timestamp = time();
        if(parent::save($files, $answers, $results)) {
            /* @var $model ModelTemplate */
            $model = Yii::$app->templates->getModelTemplate($this->templateName);
            $model->generatedAt = $timestamp;
            $model->save(false);
            return true;
        }
    }
}
