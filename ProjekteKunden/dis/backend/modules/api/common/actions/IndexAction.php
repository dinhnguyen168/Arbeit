<?php
/**
 * Created by PhpStorm.
 * User: alali
 * Date: 16.10.2018
 * Time: 12:24
 */

namespace app\modules\api\common\actions;

use app\components\helpers\DbHelper;
use app\rbac\LimitedAccessRule;
use Yii;
use yii\data\ActiveDataProvider;
use yii\data\SqlDataProvider;
use yii\db\ActiveQuery;

/**
 * An action to return a filtered list of a form records
 * Class IndexAction
 * @package app\modules\api\common\actions
 */
class IndexAction extends \yii\rest\IndexAction
{
    protected function prepareDataProvider()
    {
        $requestParams = Yii::$app->getRequest()->getBodyParams();
        if (empty($requestParams)) {
            $requestParams = Yii::$app->getRequest()->getQueryParams();
        }

        $filter = null;
        if ($this->dataFilter !== null) {
            $this->dataFilter = Yii::createObject($this->dataFilter);
            if ($this->dataFilter->load($requestParams)) {
                $filter = $this->dataFilter->build();
                if ($filter === false) {
                    return $this->dataFilter;
                }
            }
        }

        if ($this->prepareDataProvider !== null) {
            return call_user_func($this->prepareDataProvider, $this, $filter);
        }
        /* @var $modelClass \yii\db\BaseActiveRecord */
        $modelClass = $this->modelClass;
        $searchModelClass = $this->controller->searchModelClass;
        /* @var $query ActiveQuery */
        if (method_exists($searchModelClass, 'addQueryColumnsAndAttributes') === false) {
            $query = $modelClass::find();
            if (!empty($filter)) {
                $query->andWhere($filter);
            }
        } else {
            $searchModel = Yii::createObject($searchModelClass);
            $searchModel->load($requestParams, 'filter');
            $query = $searchModel::find();
            $query = $searchModel->addQueryColumnsAndAttributes($query);
        }

        $query = LimitedAccessRule::addLimitedAccessCondition($query);

        $pageOfSelectedRecord = $this->findSelectedRecordPage($requestParams, $query);
        if ($pageOfSelectedRecord) {
            $requestParams['page'] = $pageOfSelectedRecord;
        }

        $dataProvider = null;
        if (!$pageOfSelectedRecord && isset($requestParams['fields'])) {
            $sqlQuery = clone $query;
            $sqlQuery->select(explode(',', $requestParams['fields']));
            $command = $sqlQuery->createCommand();

            // PDO returns integer columns as strings by default
            // We disable that only for this request
            DbHelper::handlePdoPreparedStatements();

            $dataProvider = new SqlDataProvider([
                'sql' => $command->getSql(),
                'params' => $command->params,
                'pagination' => false
            ]);
            if (isset($requestParams['sort'])) {
                $sort = ['attributes' => explode(',', $requestParams['sort'])];
                $dataProvider->setSort($sort);
            }

            try {
                $dataProvider->getCount();
            }
            catch (\Exception $e) {
                // Something is wrong; maybe pseudoColumns in selected fields ...
                // Create model based data provider instead
                $dataProvider = null;
            }
        }

        if ($dataProvider == null) {
            $dataProvider = new ActiveDataProvider([
                'query' => $query,
                'pagination' => [
                    'params' => $requestParams,
                    // 'defaultPageSize' => 1,
                    'pageSizeLimit' => [-1, 999999999999]

                ],
                'sort' => [
                    'params' => $requestParams,
                ],
            ]);

            if (method_exists($searchModelClass, 'getExtraSortAttributes') && isset($searchModel)) {
                $dataProvider->sort->attributes = array_merge($dataProvider->sort->attributes, $searchModel->getExtraSortAttributes());
            }
        }

        return $dataProvider;
    }

    protected function findSelectedRecordPage ($requestParams, $query) {
        if (isset($requestParams['selected-record-id'])) {
            // find all pages
            $sqlQuery = clone $query;
            $table = call_user_func([$this->modelClass, "tableName"]);
            $sqlQuery->select($table . '.id');
            $command = $sqlQuery->createCommand();

            $allRecords = new SqlDataProvider([
                'sql' => $command->getSql(),
                'params' => $command->params,
                'pagination' => false
            ]);
            if (isset($requestParams['sort'])) {
                $sort = ['attributes' => explode(',', $requestParams['sort'])];
                $allRecords->setSort($sort);
            }
            $index = 0;

            foreach ($allRecords->models as $item)
            {
                if (strcmp($item['id'], $requestParams['selected-record-id']) == 0)
                    break;
                $index++;
            }
            if ($index == count($allRecords->models))
                return false;
            else
                return ceil(($index + 1) / $requestParams['per-page']);
        }

        /*
         * This perfect solution does not work on newer MySQL server versions (at least >= 8):
         *
         * SELECT findRowNumber.rowNumber, id FROM (SELECT project_site.id, (@rowNumber := @rowNumber + 1) AS rowNumber FROM project_site JOIN (SELECT @rowNumber := 0) r ORDER BY id) findRowNumber WHERE findRowNumber.id=298;
         * results in the correct rowNumber on mysql 5.x but rowNumber=1 on new mysql server
         *
        if (isset($requestParams['selected-record-id']) && intval($requestParams['selected-record-id']) > 0) {
            // find all pages
            $query2 = clone $query;
            $table = call_user_func([$this->modelClass, "tableName"]);
            $query2->select($table . '.id');
            $query2->addSelect('(@rowNumber := @rowNumber + 1) AS rowNumber');
            $query2->join('JOIN', '(SELECT @rowNumber := 0) r');
            if (isset($requestParams['sort'])) {
                $attributes = explode(',', $requestParams['sort']);
                foreach ($attributes as $attribute) {
                    $sort = SORT_ASC;
                    if (substr($attribute, 0, 1 ) === "-") {
                        $sort = SORT_DESC;
                        $attribute = substr($attribute, 1);
                    }
                    $query2->addOrderBy([$attribute => $sort]);
                }
            }

            $query3 = new \yii\db\Query();
            $query3->select('findRowNumber.rowNumber');
            $query3->from (['findRowNumber' => $query2]);
            $query3->where(['findRowNumber.id' => intval($requestParams['selected-record-id'])]);

            $countAllRows = $query2->count();
            $rowNumber = $query3->scalar();
            if ($rowNumber === false || $rowNumber >= $countAllRows)
                return false;
            else
                return intval(ceil(($rowNumber) / intval($requestParams['per-page'])));
        }

         */

    }

}
