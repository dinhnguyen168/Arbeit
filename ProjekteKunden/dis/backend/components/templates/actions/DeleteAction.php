<?php
/**
 * Created by PhpStorm.
 * User: alali
 * Date: 28.01.2019
 * Time: 13:44
 */

namespace app\components\templates\actions;


use app\components\templates\FormTemplate;
use app\components\templates\ModelTemplate;
use Yii;
use app\components\templates\BaseTemplate;
use yii\db\Exception;
use yii\web\ForbiddenHttpException;
use yii\web\ServerErrorHttpException;

class DeleteAction extends BaseAction
{
    /**
     * Deletes a template
     * @param $name string name of tmplate
     * @throws ServerErrorHttpException
     * @throws \yii\web\NotFoundHttpException
     */
    public function run($name) {
        /* @var $model BaseTemplate */
        $model = $this->findTemplate($name);

        if ($model instanceof ModelTemplate && $model->fullName === 'ArchiveFile') {
            throw new ServerErrorHttpException('You are not allowed to delete ArchiveFile model.');
        }
        if ($model instanceof FormTemplate && $model->dataModel === 'ArchiveFile') {
            throw new ServerErrorHttpException('You are not allowed to delete ArchiveFile form.');
        }

        if ($model->delete() === false) {
            throw new ServerErrorHttpException('Failed to delete the object for unknown reason.');
        }

        // if ($this->templateClass == \app\components\templates\FormTemplate::class) {
        //     \app\controllers\FormController::updateAccessRights();
        // }

        Yii::$app->getResponse()->setStatusCode(204);
    }
}