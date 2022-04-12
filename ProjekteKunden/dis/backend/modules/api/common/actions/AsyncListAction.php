<?php
/**
 * Created by PhpStorm.
 * User: alali
 * Date: 16.10.2018
 * Time: 12:24
 */

namespace app\modules\api\common\actions;

use app\components\helpers\DbHelper;
use app\components\templates\ModelTemplate;
use app\rbac\LimitedAccessRule;
use Yii;
use yii\data\SqlDataProvider;

/**
 * action to return the lists values in a from
 * Class AsyncListAction
 * @package app\modules\api\common\actions
 */
class AsyncListAction extends \yii\rest\Action
{
    /**
     * @return array
     */
    public function run()
    {
        $requestParams = Yii::$app->getRequest()->getBodyParams();
        if (empty($requestParams)) {
            $requestParams = Yii::$app->getRequest()->getQueryParams();
        }

        if ($this->checkAccess) {
            call_user_func($this->checkAccess, $this->id);
        }
        /* @var $modelClass \yii\db\BaseActiveRecord */
        $modelClass = $this->modelClass;
        $searchModelClass = $this->controller->searchModelClass;
        $searchModel = Yii::createObject($searchModelClass);
        $modelTemplate = ModelTemplate::find($requestParams['name']);
        $query = $searchModel::find();
        $query = LimitedAccessRule::addLimitedAccessCondition($query);


        // PDO returns integer columns as strings by default
        // We disable that only for this request
        DbHelper::handlePdoPreparedStatements();


        // $q: the search string
        $q = isset($requestParams["q"]) ? $requestParams["q"] : null;
        // $value: the selected value
        $value = isset($requestParams["value"]) ? $requestParams["value"] : null;
        $sort = $requestParams["sort"];
        // the default limit of sql query
        $maxAllowedSetOfRecordsPerRequest = \Yii::$app->params['maxAllowedSetOfRecordsPerRequest'];
        $valueField = null;
        $textField = null;
        if (isset($requestParams["fields"]) && str_contains($requestParams["fields"], ',')) {
            $valueField = explode(',', $requestParams['fields'])[1];
            $textField = explode(',', $requestParams['fields'])[0];
        } else {
            $valueField = $requestParams['fields'];
            $textField = $requestParams['fields'];
        }

        // check if textField is a pseudo column
        $useModelActiveQuery = false;
        if ($modelTemplate->columns[$textField]->type == 'pseudo') {
            $useModelActiveQuery = true;
        }

        if ($useModelActiveQuery) {
            // $valueQuery: the sql query for the selected value
            $valueQuery = null;
            if (!is_null($value) && $value !== 'null') {
                $valueQuery = clone $query;
                $valueQuery->where([$valueField => explode(',', $value)]);
            }

            // $sqlQuery: the default sql query to find all records
            $sqlQuery = clone $query;

            $unbuffered_db = DbHelper::getUnbufferedMysqlDb($modelClass::getDb());
            $out = ['items' => []];

            // getting the selected records
            if ($valueQuery) {
                foreach ($valueQuery->all() as $model) {
                    $out['items'][] = [
                        $valueField => $model->{$valueField},
                        $textField => $model->{$textField}
                    ];
                }
            }

            // getting limited records according to $maxAllowedSetOfRecordsPerRequest.
            foreach ($sqlQuery->batch(50, $unbuffered_db) as $models) {
                foreach ($models as $model) {
                    if (!is_null($q) && $q !== 'null' && $q !== '') {
                        if (stripos($model->{$textField}, $q) !== FALSE && $model->{$valueField} !== $value) {
                            $out['items'][] = [
                                $valueField => $model->{$valueField},
                                $textField => $model->{$textField}
                            ];
                        }
                    } else {
                        $out['items'][] = [
                            $valueField => $model->{$valueField},
                            $textField => $model->{$textField}
                        ];
                    }
                    if(count($out['items']) >= $maxAllowedSetOfRecordsPerRequest) {
                        break;
                    }
                }
                if(count($out['items']) >= $maxAllowedSetOfRecordsPerRequest) {
                    break;
                }
            }

            // sort all records ascending
            usort($out['items'], function ($a, $b) use ($sort) {
                return strcmp($a[$sort], $b[$sort]);
            });

            // sort: set selected records at beginning of the array of founded records
            if ($valueQuery) {
                usort($out['items'], function ($a, $b) use ($sort, $valueField, $value) {
                    if (str_contains($value, ',')) {
                        return in_array($a[$valueField], explode(',', $value)) ? -1 : 1;
                    } else {
                        return $a[$valueField] === $value ? -1 : 1;
                    }
                });
            }

            return $out;
        } else {
            // $valueQuery: the sql query for the selected value
            $valueQuery = null;
            if (!is_null($value) && $value !== 'null') {
                $valueQuery = clone $query;
                $valueQuery->select([$valueField, $textField]);
                $valueQuery->where([$valueField => explode(',', $value)]);
                $valueQuery->orderBy([$sort => SORT_ASC]);
            }

            // $sqlQuery: the default sql query to find all records
            $sqlQuery = clone $query;
            $sqlQuery->select([$valueField, $textField]);
            $sqlQuery->orderBy([$sort => SORT_ASC]);
            $sqlQuery->limit = $maxAllowedSetOfRecordsPerRequest;

            // if search string set, we extend our default sql query to get certain records
            if (!is_null($q) && $q !== 'null') {
                if (strpos($q, '_') !== false) {
                    $q = str_replace('_', '\_', $q);
                }
                if (strpos($q, '%') !== false) {
                    $q = str_replace('%', '\%', $q);
                }
                $sqlQuery->andWhere(['LIKE', $textField, $q .'%', false]);
                $sqlQuery->andWhere(['NOT IN', $valueField, explode(',', $value)]);
            }

            $foundedRecordsLength = $sqlQuery->count();
            /* if ($foundedRecordsLength < $maxAllowedSetOfRecordsPerRequest) {
                $sqlQuery->limit = $foundedRecordsLength;
            } */

            $items = $this->getItems($sqlQuery, $valueQuery, $foundedRecordsLength, $maxAllowedSetOfRecordsPerRequest);
            return $items;
        }
    }

    protected function getItems($sqlQuery, $valueQuery, $foundedRecordsLength, $maxAllowedSetOfRecordsPerRequest) {
        $out = ['items' => []];

        if($valueQuery) {
            $valueCommand = $valueQuery->createCommand();
            $ValueData = $valueCommand->queryAll();
            foreach ($ValueData as $valueItem) {
                array_unshift($out['items'], $valueItem);
            }
        }

        $command = $sqlQuery->createCommand();
        $data = $command->queryAll();
        foreach ($data as $item) {
            $out['items'][] = $item;
        }

        if($foundedRecordsLength > $maxAllowedSetOfRecordsPerRequest) {
            $out['totalCount'] = $foundedRecordsLength;
        }
        return $out;
    }
}