<?php


namespace app\behaviors\template;


use app\components\templates\ModelTemplate;

interface TemplateManagerBehaviorInterface
{
    /**
     * Get the name of the behavior to show in the behaviors list
     * in the model template form
     * @return string the behavior name
     */
    static function getName();

    /**
     * Get a list of parameters names which should be defined by
     * the user in the model template form
     * @return string[] list of the behavior parameters
     */
    static function getParameters();

    /**
     * Validates the user input for the parameters values
     * @param $modelTemplate ModelTemplate
     * @param $params array ['param1' => 'value1', ...]
     * @param $errors array holds the validation errors
     * @return boolean whether the parameters are valid
     */
    static function validateParametersValues($modelTemplate, $params, &$errors);

    /**
     * Converts/cast the parameters values if necessary. Called when initiating a new instance of
     * a class that implements TemplateManagerBehaviorInterface.
     * @param $params array ['param1' => 'value1', ...]
     * @return array processed parameters
     */
    static function parseParameters($params);
}
