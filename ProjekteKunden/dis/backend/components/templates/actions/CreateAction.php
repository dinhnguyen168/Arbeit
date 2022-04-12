<?php
/**
 * Created by PhpStorm.
 * User: alali
 * Date: 28.01.2019
 * Time: 10:16
 */

namespace app\components\templates\actions;


use Yii;
use app\components\templates\BaseTemplate;
use app\components\templates\ModelTemplate;
use yii\web\ServerErrorHttpException;

/**
 * This action is used to create model and form json templates
 * Class CreateAction
 * @package app\components\templates\actions
 */
class CreateAction extends BaseAction
{
    /**
     * the template should be passed in the post
     * @return BaseTemplate
     * @throws ServerErrorHttpException
     */
    public function run() {
        /* @var $model BaseTemplate */
        $model = new $this->templateClass([
            'scenario' => BaseTemplate::SCENARIO_CREATE
        ]);
        $templateData = Yii::$app->request->post('template');
        $modelName = Yii::$app->request->post('model');
        $model->load($templateData, '');
        if ($model->validate()) {
            if ($model->save()) {
                $model->afterCreate($modelName);
                Yii::$app->response->setStatusCode(201);
            } elseif ($model->hasErrors()) {
                throw new ServerErrorHttpException('Failed to create the template for unknown reason.');
            }
        }

        return $model;
    }
}