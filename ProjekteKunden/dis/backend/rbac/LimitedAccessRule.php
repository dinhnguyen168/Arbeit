<?php


namespace app\rbac;

use app\migrations\Migration;
use app\models\ProjectExpedition;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

class LimitedAccessRule extends \yii\rbac\Rule
{
    public $name = 'hasLimitedAccess';

    /**
     * @param $query ActiveQuery data provider query
     *
     * @return ActiveQuery updated active query
     */
    public static function addLimitedAccessCondition ($query) {
        $migration = new Migration();
        $user = \Yii::$app->user;
        $authManager = \Yii::$app->authManager;
        $permissions = $authManager->getPermissionsByUser($user->id);
        $limitedAccessPermissions = array_filter($permissions, function ($permission) {
            return $permission->ruleName === 'hasLimitedAccess';
        });
        $conditions = [];
        if (count($limitedAccessPermissions)) {
            /* @var $modelClass ActiveRecord */
            $modelClass = $query->modelClass;
            foreach ($limitedAccessPermissions as $limitedAccessPermission) {
                $permissionData = $limitedAccessPermission->data;
                $combinedId = ltrim($permissionData['combined_id'], '^');
                if ($modelClass::tableName() === "project_expedition") {
                    $expeditionCondition = preg_replace("/_.+$/", "", $combinedId);
                    $conditions[] = ["=", $modelClass::tableName(). '.' . $modelClass::NAME_ATTRIBUTE, $expeditionCondition];
                } elseif ($modelClass::tableName() !== 'project_program') {
                    $conditions[] = $migration->getDbRegEXStatement($modelClass::tableName(). '.' . 'combined_id', "^$combinedId");
                }
            }
            if (count($conditions) == 0) {
                return $query;
            }
            return count($conditions) > 1 ? $query->andWhere(array_merge(["OR"], $conditions)) : $query->andWhere($conditions[0]);
        }
        return $query;
    }
    /**
     * @inheritDoc
     */
    public function execute($user, $item, $params)
    {
        if (!isset($item->data["combined_id"])) {
            return false;
        }
        if(count($params) == 0) {
            return true;
        }
        $id = $item->data["combined_id"];
        if (in_array($params['action'], ['index', 'filter-lists'])) {
            return true;
        }
        if (in_array($params['action'], ['create', 'defaults'])) {
            return $this->checkBodyParamsAccess($id, $params['modelClass']);
        }
        return $this->checkModelAccess($id, $params['model']);
    }

    private function checkBodyParamsAccess($permissionId, $modelClass)
    {
        $bodyParams = \Yii::$app->getRequest()->getBodyParams();
        $tempModel = new $modelClass;
        $tempModel->load($bodyParams, '');
        $parent = $tempModel->parent;
        if ($parent instanceof ProjectExpedition) {
            $combinedId = $parent->expedition . '_';
        } elseif ($parent->parent && $parent->hasProperty('combined_id')) {
            $combinedId = $parent->combined_id;
        }
        return str_starts_with($combinedId, $permissionId);
    }

    private function checkModelAccess($permissionId, $model)
    {
        if ($model && $model->hasProperty('combined_id')) {
            return str_starts_with($model->combined_id, $permissionId);
        }
        return false;
    }
}
