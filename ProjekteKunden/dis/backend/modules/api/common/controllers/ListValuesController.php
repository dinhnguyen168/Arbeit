<?php
/**
 * Created by PhpStorm.
 * User: alali
 * Date: 24.09.2018
 * Time: 10:17
 */

namespace app\modules\api\common\controllers;

use app\components\helpers\DbHelper;
use app\models\core\DisList;
use app\models\core\DisListItem;
use yii\data\ActiveDataProvider;
use yii\data\SqlDataProvider;
use yii\filters\AccessControl;
use yii\filters\auth\HttpBearerAuth;
use yii\rest\ActiveController;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

class ListValuesController extends ActiveController
{
    /**
     * @var DisList the parent list of the current item
     */
    private $_list;
    public $modelClass = '\app\models\core\DisListItem';
    public $serializer = [
        'class' => 'yii\rest\Serializer',
        'collectionEnvelope' => 'items',
    ];

    public function behaviors()
    {
        return array_merge(
            parent::behaviors(),
            [
                'authenticator' => [
                    'class' => HttpBearerAuth::class
                ],
                'access' => [
                    'class' => AccessControl::class,
                    'only' => ['index', 'view', 'options', 'create', 'update', 'delete', 'list-names', 'select-list'],
                    'rules' => [
                        [
                            'allow' => true,
                            'matchCallback' => function ($rule, $action) {
                                $user = \Yii::$app->user;
                                if ($this->_list && $this->_list->is_locked) {
                                    return $user->can('sa');
                                } else {
                                    return $user->can('operator');
                                }
                            }
                        ],
                        [
                            'allow' => true,
                            'actions' => ['index', 'view', 'options', 'list-names', 'list', 'list-info', 'update-list-info'],
                            'roles' => ['viewer', '@']
                        ]
                    ]
                ]
            ]);
    }

    protected function loadList ($listName) {
        $list = DisList::findOne(['list_name' => $listName]);
        if (!$list) {
            $list = $list = new DisList([
                'list_name' => $listName
            ]);
            $list->save();
        }
        $this->_list = $list;
    }

    public function beforeAction($action)
    {
        $queryParams = \Yii::$app->getRequest()->getQueryParams();
        if (isset($queryParams['filter']) && isset($queryParams['filter']['listname'])) {
            $this->loadList($queryParams['filter']['listname']);
            unset($queryParams['filter']['listname']);
            $queryParams['filter']['list_id'] = $this->_list->id;
            \Yii::$app->getRequest()->setQueryParams($queryParams);
        }
        $bodyParams = \Yii::$app->getRequest()->getBodyParams();
        if (isset($bodyParams['listname'])) {
            $this->loadList($bodyParams['listname']);
            unset($bodyParams['listname']);
            $bodyParams['list_id'] = $this->_list->id;
            \Yii::$app->getRequest()->setBodyParams($bodyParams);
        }
        return parent::beforeAction($action);
    }

    public function prepareListItemsDataProvider($action, $filter)
    {
        $requestParams = \Yii::$app->getRequest()->getBodyParams();
        if (empty($requestParams)) {
            $requestParams = \Yii::$app->getRequest()->getQueryParams();
        }

        $modelClass = $this->modelClass;
        $searchModel = \Yii::createObject($modelClass . "Search");
        $searchModel->load($requestParams, 'filter');
        $query = $searchModel::find();
        $query = $searchModel->addQueryColumnsAndAttributes($query);

        $dataProvider = null;
        // If there is no extra filter, speed up things by avoiding to use models
        if ($filter && sizeof($filter) == 1) {
            $sqlQuery = clone $query;
            $sqlQuery->select('id,list_id,display,remark,uri,sort');
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
        }

        if ($dataProvider == null) {
            $dataProvider = new ActiveDataProvider([
                'query' => $query,
                'pagination' => false,
                'sort' => [
                    'params' => $requestParams,
                ],
            ]);
        }

        return $dataProvider;
    }

    public function actions()
    {
        $actions = parent::actions();
        $actions['index']['prepareDataProvider'] = [$this, 'prepareListItemsDataProvider'];
        $actions['index']['dataFilter'] = [
            'class' => 'yii\data\ActiveDataFilter',
            'searchModel' => function () {
                return (new \yii\base\DynamicModel(['id' => null, 'list_id' => null, 'display' => null, 'remark' => null, 'uri' => null, 'sort' => null]))
                    ->addRule('id', 'integer')
                    ->addRule('list_id', 'integer')
                    ->addRule('display', 'string')
                    ->addRule('remark', 'string')
                    ->addRule('uri', 'string')
                    ->addRule('sort', 'integer');
            },
        ];
        $actions['update']['checkAccess'] = [$this, 'checkModelActionAccess'];
        $actions['delete']['checkAccess'] = [$this, 'checkModelActionAccess'];
        return $actions;
    }

    /**
     * @param $action string
     * @param $model DisListItem
     */
    public function checkModelActionAccess ($action, $model)
    {
        $list = $model->list;
        $user = \Yii::$app->user;
        if ($list->is_locked && !$user->can('sa')) {
            throw new ForbiddenHttpException();
        } else if (!$user->can('operator')) {
            throw new ForbiddenHttpException();
        }
    }

    protected function verbs()
    {
        $verbs = parent::verbs();
        $verbs['list-names'] = ['GET'];
        $verbs['list-info'] = ['GET'];
        $verbs['update-list-info'] = ['PUT'];
        return $verbs;
    }

    public function actionListNames()
    {
        return array_column(DisList::find()
            ->select(['list_name'])
            ->distinct()
            ->all(), 'list_name');
    }

    public function actionListInfo($listname)
    {
        $list = DisList::findOne(['list_name' => $listname]);
        if ($list) {
            return $list;
        }
        throw new NotFoundHttpException("Unable to find list with name $listname");
    }

    public function actionUpdateListInfo ($listname) {
        $list = DisList::findOne(['list_name' => $listname]);
        if (!$list) {
            throw new NotFoundHttpException("Unable to find list with name $listname");
        }
        $user = \Yii::$app->user;
        $postData = \Yii::$app->getRequest()->post();
        if (($postData["is_locked"] || $list->is_locked) && !$user->can('sa')) {
            throw new ForbiddenHttpException();
        }
        $list->load(\Yii::$app->getRequest()->post(), '');
        $list->save();
        return $list;
    }
}
