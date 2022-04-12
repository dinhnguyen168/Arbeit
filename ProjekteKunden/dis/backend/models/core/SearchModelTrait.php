<?php

namespace app\models\core;


use app\migrations\Migration;
use app\models\core\DisList;
use app\models\core\DisListItem;
use yii\base\Model;
use yii\web\BadRequestHttpException;

/**
 * Trait SearchModelTrait
 *
 * This trait is used in every base search model class ("models/base/*Search.php").
 * Otherwise all the contained methods would have to be copied to every of theses classes.
 */
trait SearchModelTrait
{
    /**
     * @var array a list of linked fields and thier list source
     */
    private $linkedFields = [];

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();
        foreach ($this->attributes as $attribute => $value) {
            if ($attribute) {
                $this->{$attribute} = null;
            }
        }
    }


    /**
     * {@inheritdoc}
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Returns the data model class for the current search model class.
     * @return string
     */
    public function getModelClass() {
        $modelClass = preg_replace("/Search$/", "", get_class($this));
        return $modelClass;
    }


    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $modelClass = $this->getModelClass();
        $query = call_user_func([$modelClass, "find"]);
        $query->modelClass = $modelClass;

        $dataProvider = new \yii\data\ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params, 'filter');

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        $this->addQueryColumnsAndAttributes ($query);
        $sql = $query->createCommand()->getRawSql();

        return $dataProvider;
    }


    public function addQueryColumnsAndAttributes ($query) {
        // check if we are searching a form
        if (defined(get_class($this) . '::FORM_NAME')) {
            $formName = $this::FORM_NAME;
            $formTemplate = \Yii::$app->templates->getFormTemplate($formName);
            $listFields = array_filter($formTemplate->fields, function ($v) { return $v->formInput['type'] == 'select'; });
            foreach ($listFields as $field) {
                $selectSource = $field->formInput['selectSource'];
                $isList = $selectSource['type'] == 'list';
                $linkedModelTemplate = !$isList ? \Yii::$app->templates->getModelTemplate($selectSource['model']) : null;
                if (!$isList) {
                    if (!$linkedModelTemplate) die ("SearchModelTrait::addQueryColumnsAndAttributes() Linked model not found:" . $selectSource['model']);
                    if (!isset($selectSource['valueField'])) die ("SearchModelTrait::addQueryColumnsAndAttributes() valueField missing in form field " . $formName . "." . $field->name);
                    if (!isset($selectSource['textField'])) $selectSource['textField'] = $selectSource['valueField'];
                }
                $this->linkedFields[$field->name] = [
                    'modelClass' => $isList ? DisListItem::class : "\\app\\models\\" . $selectSource['model'],
                    // 'listName' => $isList ? $selectSource['listName'] : null,
                    'listCondition' => $isList ? ['list_name' => $selectSource['listName']] : null,
                    'valueColumnName' => $isList ? 'display' : $selectSource['valueField'],
                    'valueColumnType' => $isList ? 'string' : $linkedModelTemplate->columns[$selectSource['valueField']]->type,
                    'textColumnName' => $isList ? 'remark' : $selectSource['textField'],
                    'textColumnType' => $isList ? 'string' : $linkedModelTemplate->columns[$selectSource['textField']]->type,
                ];
            }
        }
        $this->addQuerySearchAttributes($query);
        $this->addQueryColumns($query);
        return $query;
    }

    /**
     * Adds a filter condition for an attribute. The different possibilities to search for records using the custom filter
     * is explained in the wiki.
     * @param $query Query object to which the filter will be applied
     * @param $attributeName string Name of the attribute (= column)
     * @param $searchType string Type of search (Contains the type of the column, i.e. "number", "date", "boolean", "string"
     */
    protected function addQueryColumn($query, $attributeName, $searchType) {
        if (isset($this->{$attributeName})) {
            $value = $this->{$attributeName};
            $tableName = $this->tableSchema->name;
            if ($value !== null && $value !== "") {
                $this->addFilterToQuery($query, $attributeName, $searchType, $tableName, $value);
            }
        }
    }

    protected function addQueryPseudoColumn($query, $attributeName, $searchType, $targetTable, $targetColumn, $relations = []) {
        if (isset($this->{$attributeName})) {
            $value = $this->{$attributeName};
            if ($value !== null && $value !== "") {
                $this->addFilterToQuery($query, $targetColumn, $searchType, $targetTable, $value);
            }
        }

        foreach ($relations as $relation) {
            $skipJoin = false;
            if ($query->join) foreach ($query->join as $existingJoin) {
                if ($existingJoin[1] == $relation['table'] && $existingJoin[2] == $relation['on']) {
                    // Join already exists!
                    $skipJoin = true;
                    break;
                }
            }
            if (!$skipJoin) $query->leftJoin ($relation['table'], $relation['on']);
        }
    }

    protected function addQueryManyToManyColumn($query, $attributeName, $searchType, $targetTable, $targetColumn, $relationName)
    {
        if (isset($this->{$attributeName})) {
            $value = $this->{$attributeName};
            if ($value !== null && $value !== "") {
                if (!is_array($value)) {
                    if (preg_match_all('/(?:,|^)(\d+)/', $value, $matches)) {
                        $value = $matches[1];
                    }
                }
                if (is_array($value)) {
                    $query->andWhere(['IN', $targetTable . '.id', $value]);
                }
                else {
                    $this->addFilterToQuery($query, $targetColumn, $searchType, $targetTable, $value);
                }
                $query->joinWith ($relationName);
            }
        }
    }

    protected function addQueryOneToManyColumn($query, $attributeName, $searchType, $targetTable, $targetColumn, $relationName)
    {
        if (isset($this->{$attributeName})) {
            $value = $this->{$attributeName};
            if ($value !== null && $value !== "") {
                if (!is_array($value)) {
                    if (preg_match_all('/(?:,|^)(\d+)/', $value, $matches)) {
                        $value = $matches[1];
                    }
                }
                if (is_array($value)) {
                    $query->andWhere(['IN', $targetTable . '.id', $value]);
                }
                else {
                    $this->addFilterToQuery($query, $targetColumn, $searchType, $targetTable, $value);
                }
                $query->joinWith ($relationName);
            }
        }
    }

    protected function addFilterToQuery ($query, $attributeName, $searchType, $tableName, $value)
    {
        $migration = new Migration();
        $fullColumnName = $tableName . '.' . $attributeName;
        $whereConditions = [];

        $originalValue = $value;
        $identical = preg_match("/^!?==/", $value);
        $not = preg_match("/^!=/", $value);
        $equal = preg_match("/^=/", $value);
        $value = trim(preg_replace("/^!?==?/", "", $value));
        $compare = preg_match("/^(>=?|<=?)/", $value);
        $checkNull = ($equal || $not) && $identical && $value == "NULL";

        if (($not || $equal) && $compare) {
            throw new BadRequestHttpException("Invalid Filter value for '$attributeName': may not start with '" . preg_replace("/^([!=<>]+).*$/", "$1", $originalValue) . "'");
        }

        if ($tableName == $this->tableSchema->name && isset ($this->linkedFields[$attributeName]) && !$compare && !$checkNull) {
            $linkedField = $this->linkedFields[$attributeName];
            $subModelClass = $linkedField["modelClass"];
            $subTableName = forward_static_call([$subModelClass, "tableName"]);
            $subQuery = new \yii\db\Query;
            $subQuery->select($linkedField["valueColumnName"]);
            $subQuery->from($subTableName);
            if ($linkedField['listCondition']) {
                $subQuery->innerJoin(DisList::tableName(), 'list_id = dis_list.id')->andWhere($linkedField['listCondition']);
            }
            $this->addFilterToQuery($subQuery, $linkedField["textColumnName"], $linkedField["textColumnType"], $subTableName, $originalValue);
            $whereConditions[] = ['IN', $fullColumnName, $subQuery];
        }

        $condition = null;
        if ($checkNull)
            $condition = [$fullColumnName => NULL];
        else {
            switch ($searchType) {
                case 'number':
                    if (preg_match("/^(>=?|<=?)(.+)$/", $value, $matches)) {
                        if (!is_numeric(ltrim($matches[2]))) {
                            throw new BadRequestHttpException('The filter value must be a numeric value.');
                        }
                        $condition = [$matches[1], $fullColumnName, ltrim($matches[2])];
                    } else {
                        if (!is_numeric($value)) {
                            throw new BadRequestHttpException('The filter value must be a numeric value.');
                        }
                        $condition = [$fullColumnName => $value];
                    }
                    break;

                case 'boolean':
                    $value = strtolower($value);
                    $value = ($value == 'y' ? 'true' : ($value == 'n' ? 'false' : $value));
                    $booleanValue = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                    if ($booleanValue !== null)
                        $condition = [$fullColumnName => ($booleanValue ? 1 : 0)];
                    else
                        throw new BadRequestHttpException("Invalid boolean value for '$attributeName': '" . $originalValue . "'");
                    break;

                case 'date':
                case 'datetime':
                case 'string':
                    if (preg_match("/^(>=?|<=?)(.+)$/", $value, $matches))
                        $whereConditions[] = [$matches[1], $fullColumnName, ltrim($matches[2])];
                    else if ($identical)
                        $condition = [$fullColumnName => $value];
                    else
                        $condition = $migration->getDbRegEXStatement($fullColumnName, $value);
                    break;

                default:
                    $condition = [$fullColumnName => $value];
            }
        }
        if ($condition) {
            if ($not) $condition = ['NOT', $condition];
            $whereConditions[] = $condition;
        }

        if (sizeof($whereConditions)) {
            if (sizeof($whereConditions) > 1) {
                $filterWhere = $not ? ['AND'] : ['OR'];
                foreach ($whereConditions as $condition) $filterWhere[] = $condition;
            } else if (sizeof($whereConditions) == 1)
                $filterWhere = $whereConditions[0];

            $query->andWhere($filterWhere);
        }
    }

    /**
     * Adds a filter condition for an attribute with a relation to a parent data table
     *
     * See the example of the calls to this function, i.e. in "base/BaseCoreSectionSearch.php"
     *
     * @param $query Query object to which the filter will be applied
     * @param $attributeName Name of the attribute (= column)
     * @param $table Name of the (parent) table where the attribute can be found
     * @param $localColumn Name of table plus column name of the child side of the foreign key relation
     * @param $foreignColumn Name of the table plus column name of the parent side of the foreign key relation
     * @param $joins Array of joins to which additional joins are added
     */
    protected function addQuerySearchAttribute($query, $attributeName, $table, $localColumn, $foreignColumn, & $joins) {
        $createJoin = false;
        $value = $this->{$attributeName};
        if ($value) {
            $filterColumn = $table . "." . $attributeName;
            if (preg_match("/^([<>!=]+)(.+)$/", $value, $matches)) {
                $query->andFilterWhere([$matches[1], $filterColumn, ltrim($matches[2])]);
            } else {
                $query->andFilterWhere([$filterColumn => $value]);
            }
            $createJoin = true;
        }
        if ($createJoin || sizeof($joins) > 0) {
            $joins[] = [$table, $localColumn . " = " . $foreignColumn];
        }
    }

    /**
     * Adds a filter condition for an attribute with many to many relation
     *
     *
     * @param $query Query object to which the filter will be applied
     * @param $attributeName Name of the attribute (= column)
     * @param $relationName Name of the relation as in model wriiten
     * @param $relatedtable Name of the related table
     * @param $displayColumn Name of the display column of the related table
     * @param $joins Array of joins to which additional joins are added
     */
    protected function addQuerySearchForRelation($query,$attributeName, $relationName, $relatedtable, $displayColumn, & $joins) {
        $value = $this->{$attributeName};
        if($value) {
            $filterColumn = $relatedtable . "." . $displayColumn;
            $query->andFilterWhere(["like", $filterColumn, $value]);
            $joins[] = $relationName;
        }
    }

    /**
     * Add the joins create by calls to "addQuerySearchAttribute" to the query
     * Checks if the join already exists.
     * @param $query Query object to which the joins will be added
     * @param $joins Array of joins; each element is an array of two element, the name of the table and the join condition
     */
    protected function createSearchJoins ($query, $joins) {
        foreach (array_reverse($joins) as $join) {
            $skipJoin = false;
            if ($query->join) foreach ($query->join as $existingJoin) {
                if ($existingJoin[1] == $join[0] && $existingJoin[2] == $join[1]) {
                    // Join already exists!
                    $skipJoin = true;
                    break;
                }
            }

            if (!$skipJoin) $query->innerJoin($join[0], $join[1]);
        }
    }

}

