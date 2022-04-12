<?php

namespace app\modules\api\common\actions;

use Yii;
use yii\db\IntegrityException;
use yii\web\ConflictHttpException;

/**
 * Class DeleteAction
 * @package app\modules\api\common\actions
 *
 * Action to delete a record in a form.
 */
class DeleteAction extends \yii\rest\DeleteAction
{
    /**
     * Deletes a model.
     * @param mixed $id id of the model to be deleted.
     * @throws ServerErrorHttpException on failure.
     */
    public function run($id)
    {
        $model = $this->findModel($id);

        if ($this->checkAccess) {
            call_user_func($this->checkAccess, $this->id, $model);
        }

        try {
            if ($model->delete() === false) {
                throw new ServerErrorHttpException('Failed to delete the object for unknown reason.');
            }
        } catch (IntegrityException $e) {
            $message = 'DB Integrity error';
            if (isset($e->errorInfo[1]) && $e->errorInfo[1] === 1451) {
                $message = 'Constraint violation - record has related data';
            }
            throw new ConflictHttpException($message);
        }

        Yii::$app->getResponse()->setStatusCode(204);
    }
}