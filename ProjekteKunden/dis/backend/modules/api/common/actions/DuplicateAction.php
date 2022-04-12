<?php

namespace app\modules\api\common\actions;

use Yii;
use app\models\core\Base;
use app\behaviors\UniqueCombinationAutoIncrementBehavior;

/**
 * Class DuplicateAction
 * @package app\modules\api\common\actions
 *
 * Action to duplicate the current record in a form.
 */
class DuplicateAction extends \yii\rest\Action
{
    /**
     * @return ActiveDataProvider
     */
    public function run($id)
    {
        $modelToDuplicate = $this->findModel($id);
        if ($this->checkAccess) {
            call_user_func($this->checkAccess, $this->id, $modelToDuplicate);
        }

        return $this->getDuplicate($modelToDuplicate);
    }

    protected function getDuplicate ($modelToDuplicate) {
        $modelClass = $this->modelClass;
        $fieldsToSkip = [
            'id',
            'combined_id',
            'igsn'
        ];
        foreach ($modelToDuplicate->behaviors() as $behavior) {
            if ($behavior['class'] === UniqueCombinationAutoIncrementBehavior::class) {
                $fieldsToSkip[] = $behavior['fieldToFill'];
            }
        }
        $model = new $modelClass();
        $model->load(Yii::$app->getRequest()->getBodyParams(), '');
        // trigger defaults behaviors
        $model->trigger(Base::EVENT_DEFAULTS);

        // copy all attributes except the ones on $fieldsToSkip
        foreach ($modelToDuplicate->attributes() as $attribute) {
            if (!in_array($attribute, $fieldsToSkip) && empty($model->$attribute)) {
                $model->$attribute = $modelToDuplicate->$attribute;
            }
        }
        return $model;
    }
}
