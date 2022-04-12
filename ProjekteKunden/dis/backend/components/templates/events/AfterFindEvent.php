<?php


namespace app\components\templates\events;


use app\components\templates\BaseTemplate;
use yii\base\Event;
use yii\base\InvalidConfigException;

class AfterFindEvent extends Event
{
    public $template;

    public function init()
    {
        parent::init();
        if (!$this->template instanceof BaseTemplate) {
            throw new InvalidConfigException('template must be instance of BaseTemplate in AfterFindEvent');
        }
    }
}