<?php
/**
 * Created by PhpStorm.
 * User: alali
 * Date: 01.03.2019
 * Time: 11:59
 */

namespace app\modules\api\common\controllers\base;

use app\modules\api\common\actions\AsyncListAction;
use app\modules\api\common\actions\DefaultsAction;
use app\modules\api\common\actions\DeleteAction;
use app\modules\api\common\actions\DuplicateAction;
use app\modules\api\common\actions\FilterListsAction;
use app\modules\api\common\actions\IndexAction;
use app\modules\api\common\actions\HarvestAction;
use app\modules\api\common\actions\ReportsAction;
use app\modules\api\common\actions\PrintAction;
use app\modules\api\common\controllers\interfaces\ITemplatedClassController;
use app\modules\api\common\controllers\interfaces\ITemplatedFormActiveController;
use app\modules\api\common\controllers\interfaces\ITemplatedModelActiveController;
use Yii;
use yii\base\InvalidConfigException;
use yii\filters\AccessControl;
use yii\filters\auth\HttpBearerAuth;
use yii\rest\ActiveController;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\rest\Serializer;

abstract class TemplatedClassActiveController extends ActiveController implements ITemplatedClassController
{
    public $searchModelClass;
    public $serializer = [
        'class' => Serializer::class,
        'collectionEnvelope' => 'items',
    ];
    /**
     * @var string name of the model/form to be used
     */
    private $_name;

    public function getName(): string
    {
        return $this->_name;
    }

    public function init()
    {
        $params = \Yii::$app->request->queryParams;
        if (isset($params['name'])) {
            $this->_name = $params['name'];
            //$formName = Inflector::camelize($this->_name . '-form');
            if ($this instanceof ITemplatedModelActiveController) {
                $modelClass = $this->getDataModelNameSpace() . '\\' . $this->getDataModelClassName();
                $formSearchClass = $modelClass . 'Search';
            }
            if ($this instanceof ITemplatedFormActiveController) {
                $modelClass = $this->getDataFormNameSpace() . '\\' . $this->getDataFormClassName();
                $formSearchClass = $modelClass . 'Search';
            }

            if (isset($modelClass) && class_exists($modelClass)) {
                $this->modelClass = $modelClass;
                if (class_exists($formSearchClass)) {
                    $this->searchModelClass = $formSearchClass;
                }
            }
        }
        try {
            parent::init();
        } catch (InvalidConfigException $e) {
            throw new NotFoundHttpException('model not found');
        }
    }

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
                    'only' => ['index', 'harvest', 'view', 'options', 'create', 'update', 'delete', 'defaults', 'duplicate', 'filter-lists', 'async-lists', 'reports', 'print'],
                    'rules' => [
                        [
                            'allow' => true,
                            'actions' => ['create', 'update', 'delete', 'defaults', 'duplicate', 'print'],
                            'roles' => ['operator']
                        ],
                        [
                            'allow' => true,
                            'actions' => ['index', 'harvest', 'view', 'reports', 'print'],
                            'roles' => ['viewer']
                        ],
                        [
                            'allow' => true,
                            'actions' => ['options', 'filter-lists', 'async-lists'],
                            'roles' => ['@']
                        ],
                        [
                            'allow' => true,
                            'matchCallback' => function ($rule, $action) {
                                if (Yii::$app->user->can('form-*:' . Yii::$app->controller->getPermissionAction($action->id))) {
                                    return true;
                                }
                                $permissionNames = Yii::$app->controller->getPermissionNames($action->id);
                                foreach ($permissionNames as $permissionName) {
                                    if (Yii::$app->user->can($permissionName)) {
                                        return true;
                                    }
                                }
                                return false;
                            }
                        ]
                    ]
                ]
            ]
        );
    }

    private function getPermissionAction ($action): string
    {
        return in_array($action, ['index', 'harvest', 'view', 'reports', 'options', 'filter-lists', 'async-lists', 'print']) ? 'view' : 'edit';
    }

    private function getPermissionNames ($action): array
    {
        $formPermissions = [];
        if ($this instanceof ITemplatedFormActiveController) {
            $formName = "form-" . \yii\helpers\Inflector::camel2id ($this->name);
            $formPermissions[] = $formName . ':' . $this->getPermissionAction($action);
        }
        if ($this instanceof ITemplatedModelActiveController){
            // search for related forms
            foreach (Yii::$app->templates->formTemplates as $formTemplate) {
                if ($formTemplate->dataModel === $this->getName()) {
                    $formPermissions[] = "form-" . $formTemplate->name . ':' . $this->getPermissionAction($action);
                }
            }
        }
        return $formPermissions;
    }

    public function recordLevelCheckAccess($action, $model = null): bool
    {
        $controller = Yii::$app->controller;
        if ($controller instanceof ITemplatedModelActiveController) {
            $modelClass = $controller->getDataModelTemplate()->getCustomClass();
        }
        if ($controller instanceof ITemplatedFormActiveController) {
            $modelClass = $controller->getFormDataModelTemplate()->getCustomClass();
        }
        $permissionNames = Yii::$app->controller->getPermissionNames($action);
        if (in_array($action, ['index', 'harvest', 'filter-lists', 'async-lists'])) {
            return true;
        }
        foreach ($permissionNames as $permissionName) {
            if (\Yii::$app->user->can($permissionName, [
                'modelClass' => $modelClass,
                'model' => $model,
                'action' => $action
            ])) {
                return true;
            }
        }
        throw new ForbiddenHttpException();
    }

    private $actionsWitchContainsUuid = ['index', 'harvest', 'update', 'create', 'view', 'duplicate', 'defaults'];
    public function beforeAction($action)
    {
        $beforeAction = parent::beforeAction($action);
        if(in_array($action->id, $this->actionsWitchContainsUuid)) {
            $session = \Yii::$app->session;
            if(!$session->has('uuids')) {
                $session->set('uuids', []);
            }
        }
        return $beforeAction;
    }

    public function afterAction($action, $result)
    {
        $afterAction = parent::afterAction($action, $result);
        if(in_array($action->id, $this->actionsWitchContainsUuid)) {
            $session = \Yii::$app->session;
            if ($session->has('uuids')) {
                $session->remove('uuids');
            }
        }
        return $afterAction;
    }

    public function actions()
    {
        $actions = parent::actions();
        $actions['index']['class'] = IndexAction::class;
        $actions['index']['checkAccess'] = [$this, 'recordLevelCheckAccess'];
        $actions['harvest'] = [
            'class' => HarvestAction::class,
            'modelClass' => $this->modelClass
        ];
        $actions['harvest']['checkAccess'] = [$this, 'recordLevelCheckAccess'];
        $actions['view']['checkAccess'] = [$this, 'recordLevelCheckAccess'];
        $actions['create']['checkAccess'] = [$this, 'recordLevelCheckAccess'];
        $actions['update']['checkAccess'] = [$this, 'recordLevelCheckAccess'];
        $actions['delete']['class'] = DeleteAction::class;
        if ($this->searchModelClass != null) {
            $actions['index']['dataFilter'] = [
                'class' => 'yii\data\ActiveDataFilter',
                'searchModel' => $this->searchModelClass
            ];
            $actions['harvest']['dataFilter'] = [
                'class' => 'yii\data\ActiveDataFilter',
                'searchModel' => $this->searchModelClass
            ];
        }
        $actions['delete']['checkAccess'] = [$this, 'recordLevelCheckAccess'];
        $actions['duplicate'] = [
            'class' => DuplicateAction::class,
            'modelClass' => $this->modelClass
        ];
        $actions['duplicate']['checkAccess'] = [$this, 'recordLevelCheckAccess'];
        $actions['defaults'] = [
            'class' => DefaultsAction::class,
            'modelClass' => $this->modelClass
        ];
        $actions['defaults']['checkAccess'] = [$this, 'recordLevelCheckAccess'];
        $actions['filter-lists'] = [
            'class' => FilterListsAction::class,
            'modelClass' => $this->modelClass
        ];
        $actions['filter-lists']['checkAccess'] = [$this, 'recordLevelCheckAccess'];
        $actions['async-lists'] = [
            'class' => AsyncListAction::class,
            'modelClass' => $this->modelClass
        ];
        $actions['async-lists']['checkAccess'] = [$this, 'recordLevelCheckAccess'];
        $actions['reports'] = [
            'class' => ReportsAction::class,
            'modelClass' => $this->modelClass
        ];
//        $actions['reports']['checkAccess'] = [$this, 'recordLevelCheckAccess'];
        $actions['print'] = [
            'class' => PrintAction::class,
            'modelClass' => $this->modelClass
        ];
        $actions['print']['checkAccess'] = [$this, 'recordLevelCheckAccess'];
        return $actions;
    }

    /**
     * {@inheritdoc}
     */
    protected function verbs()
    {
        $verbs = parent::verbs();
        $verbs['defaults'] = ['POST'];
        $verbs['duplicate'] = ['POST'];
        $verbs['filterLists'] = ['GET'];
        $verbs['reports'] = ['GET'];
        $verbs['print'] = ['GET'];
        $verbs['harvest'] = ['GET'];
        return $verbs;
    }
}
