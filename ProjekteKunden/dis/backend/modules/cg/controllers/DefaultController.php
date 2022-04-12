<?php
/**
 * Created by PhpStorm.
 * User: alali
 * Date: 24.09.2018
 * Time: 12:14
 */

namespace app\modules\cg\controllers;

use yii\filters\AccessControl;
use yii\gii\controllers\DefaultController as BaseDefaultController;

class DefaultController extends BaseDefaultController
{
    public function behaviors()
    {
        return array_merge(
            parent::behaviors(),
            [
                'access' => [
                    'class' => AccessControl::class,
                    'rules' => [
                        [
                            'allow' => true,
                            'roles' => ['developer']
                        ]
                    ]
                ]
            ]
        );
    }

    public function beforeAction($action)
    {
//        $this->getView()->theme->pathMap = [
//            '@app/modules/cg/view' => '@vendor/yiisoft/yii2-gii/src/views'
//        ];
        return parent::beforeAction($action); // TODO: Change the autogenerated stub
    }
}