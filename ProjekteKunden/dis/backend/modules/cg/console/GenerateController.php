<?php

namespace app\modules\cg\console;


class GenerateController extends \yii\gii\console\GenerateController
{
    /**
     * @var \app\modules\cg\Module
     */
    public $module;

    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        $actions = [];
        foreach ($this->generators as $name => $generator) {
            $actions[$name] = [
                'class' => GenerateAction::class,
                'generator' => $generator,
            ];
        }
        return $actions;
    }

    public function actionIndex()
    {
        $this->run('/help', ['cg']);
    }
}
