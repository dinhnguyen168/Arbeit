<?php
/**
 * Created by PhpStorm.
 * User: alali
 * Date: 24.09.2018
 * Time: 10:17
 */

namespace app\modules\api\common\controllers;

use app\models\core\Widget;
use yii\db\ActiveRecord;
use yii\filters\AccessControl;
use yii\filters\auth\HttpBearerAuth;
use Yii;
use yii\rest\ActiveController;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;

class WidgetsController extends ActiveController
{
    public $modelClass = Widget::class;

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
                    'only' => ['index', 'view', 'options', 'create', 'update', 'delete', 'bulk-update', 'duplicate'],
                    'rules' => [
                        [
                            'allow' => true,
                            'actions' => ['create', 'update', 'delete', 'bulk-update', 'duplicate'],
                            'roles' => ['developer']
                        ],
                        [
                            'allow' => true,
                            'actions' => ['index', 'view'],
                            'roles' => ['@']
                        ]
                    ]
                ]
            ]
        );
    }

    public function actionDuplicate ($id) {
        $modelClass = $this->modelClass;
        $model = $modelClass::findOne($id);
        if (!isset($model)) {
            throw new NotFoundHttpException("Object not found: " .$id);
        }
        $newModel = new $modelClass($model->attributes);
        $newModel->cloneable = 1;
        $newModel->deletable = 1;
        $newModel->id = null;
        $newModel->order = $modelClass::find()->count();
        if ($newModel->save()) {
            $response = Yii::$app->getResponse();
            $response->setStatusCode(201);
        } elseif (!$newModel->hasErrors()) {
            throw new ServerErrorHttpException('Failed to create the object for unknown reason.');
        }
        return $newModel;
    }

    public function actionBulkUpdate () {
        $modelsData = Yii::$app->getRequest()->getBodyParams();
        if (!is_array($modelsData)) {
            throw new BadRequestHttpException('modelsData is not an array');
        }

        foreach ($modelsData as $modelData) {
            /* @var $model ActiveRecord */
            $modelClass = $this->modelClass;
            $model = $modelClass::findOne($modelData['id']);
            if (!isset($model)) {
                throw new NotFoundHttpException("Object not found: " .$modelData['id']);
            }

            $model->load($modelData, '');
            if ($model->save() === false && !$model->hasErrors()) {
                throw new ServerErrorHttpException('Failed to update the object for unknown reason.');
            }
            if ($model->hasErrors()) {
                return $model;
            }
        }

        return [
            'message' => 'all recored was saved successfully.'
        ];
    }
}
