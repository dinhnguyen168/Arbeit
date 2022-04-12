<?php
/**
 * Created by PhpStorm.
 * User: alali
 * Date: 28.01.2019
 * Time: 11:45
 */

namespace app\components\templates\actions;


use Yii;
use app\components\templates\BaseTemplate;
use app\components\templates\ModelTemplate;
use yii\base\Action;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;

/**
 * updates a template
 * Class UpdateAction
 * @package app\components\templates\actions
 */
class UpdateAction extends BaseAction
{
    /**
     * the new template should be passed in the post
     * @param $name string name of the template you want to update
     * @return BaseTemplate
     * @throws NotFoundHttpException
     * @throws ServerErrorHttpException
     */
    public function run($name) {
        /* @var $model BaseTemplate */
        $templateData = Yii::$app->request->post('template');
        $model = $this->findTemplate($name);
        $model->scenario = BaseTemplate::SCENARIO_UPDATE;
        $model->load($templateData, '');
        if ($model->validate()) {
            if ($model->save() === false && !$model->hasErrors()) {
                throw new ServerErrorHttpException('Failed to update the object for unknown reason.');
            } else {
                $model->afterCreate($name);
            }
        }

        // if ($this->templateClass == app\components\templates\FormTemplate::class) {
        //     \app\controllers\FormController::updateAccessRights();
        // }

        return $model;
    }
}