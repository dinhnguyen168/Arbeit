<?php
/**
 * Created by PhpStorm.
 * User: alali
 * Date: 10.09.2018
 * Time: 11:42
 */

namespace app\resources;


class User extends \app\models\core\User
{
    public function fields()
    {
        return [
            'id',
            'username',
            'email',
            'token' => function ($model) {
                return $model->api_token;
            },
            'roles' => function ($model) {
                return array_keys(\Yii::$app->authManager->getRolesByUser($model->id));
            },
            'permissions' => function ($model) {
                return array_keys(\Yii::$app->authManager->getPermissionsByUser($model->id));
            },
            'profile' => function ($model) {
                return $model->profile;
            }
        ];
    }
}
