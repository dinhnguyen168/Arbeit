<?php
/**
 * Created by PhpStorm.
 * User: alali
 * Date: 24.09.2018
 * Time: 10:17
 */

namespace app\modules\api\common\controllers;

use app\models\core\MessageOfTheDay;
use yii\data\ActiveDataFilter;
use yii\filters\AccessControl;
use yii\filters\auth\HttpBearerAuth;
use yii\rest\ActiveController;

class MessagesController extends ActiveController
{
    public $modelClass = MessageOfTheDay::class;
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
                    'only' => ['index', 'view', 'options', 'create', 'update', 'delete'],
                    'rules' => [
                        [
                            'allow' => true,
                            'actions' => ['create', 'update', 'delete'],
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

    public function actions()
    {
        $actions = parent::actions();

        // customize the data provider preparation with the "prepareDataProvider()" method
        $actions['index']['dataFilter'] = [
            'class' => ActiveDataFilter::class,
            'searchModel' => MessageOfTheDay::class
        ];

        return $actions;
    }

}
