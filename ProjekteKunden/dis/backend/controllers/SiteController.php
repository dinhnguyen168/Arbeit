<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;

class SiteController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'error' => [
                'layout' => 'main',
                'class' => 'yii\web\ErrorAction',
            ]
        ];
    }
}