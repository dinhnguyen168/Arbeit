<?php
/**
 * Created by PhpStorm.
 * User: alali
 * Date: 28.01.2019
 * Time: 12:04
 */

namespace app\components\templates\actions;

/**
 * used to return a template as a json response
 * Class ViewAction
 * @package app\components\templates\actions
 */
class ViewAction extends BaseAction
{
    /**
     * @param $name
     * @return \app\components\templates\BaseTemplate
     * @throws \yii\web\NotFoundHttpException
     */
    public function run($name) {
        return $this->findTemplate($name);
    }
}