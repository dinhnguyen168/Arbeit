<?php
/**
 * Created by PhpStorm.
 * User: alali
 * Date: 28.01.2019
 * Time: 12:34
 */

namespace app\components\templates\actions;


use app\components\templates\FormTemplate;
use app\components\templates\ModelTemplate;
use yii\base\Action;
use yii\helpers\ArrayHelper;

/**
 * returns a summary of the available templates in the system.
 * Class SummaryAction
 * @package app\components\templates\actions
 */
class SummaryAction extends Action
{
    public function run() {
        $modules = [];
        $models = [];
        $forms = [];
        foreach (\Yii::$app->templates->getFormTemplates() as $form) {
            if ($form) {
                $forms[] = ArrayHelper::toArray($form, [
                    'app\\components\\templates\\FormTemplate' => [
                        'name',
                        'dataModel',
                        'modifiedAt',
                        'generatedAt',
                        'generatedFiles',
                        'customVueFile'
                    ]
                ]);
            }
        }
        foreach (\Yii::$app->templates->getModelTemplates() as $model) {
            if ($model) {
                if (!in_array($model->module, $modules)) {
                    $modules[] = $model->module;
                }
                $models[] = ArrayHelper::toArray($model, [
                    'app\\components\\templates\\ModelTemplate' => [
                        'name',
                        'module',
                        'table',
                        'parentModel',
                        'fullName',
                        'modifiedAt',
                        'generatedAt',
                        'isTableCreated',
                        'tableGenerationTimestamp',
                        'generatedFiles',
                        'columns'
                    ]
                ]);
            }
        }

        return [
            'modules' => $modules,
            'models' => $models,
            'forms' => $forms,
        ];
    }
}
