<?php
namespace app\components\templates;

use app\components\templates\events\AfterFindEvent;
use app\components\templates\events\TemplatesEventInterface;
use Yii;
use yii\base\Component as BaseComponent;
use yii\base\Event;

/**
 * Class Component
 * @property ModelTemplate[] modelTemplates
 * @property FormTemplate[] formTemplates
 *
 * Gives access to the template classes by providing the component named "templates":
 *      i.e. \Yii::$app->templates->getFormTemplate("HoleForm");
 *
 * @package app\components\templates
 */
class Component extends BaseComponent implements TemplatesEventInterface
{
    private $_modelTemplates = [];
    private $_formTemplates = [];

    public function init()
    {
        parent::init();
        $this->loadAllTemplates();
        Yii::$app->on(self::EVENT_AFTER_TEMPLATE_FIND, [$this, 'afterTemplateFind']);
    }

    /**
     * @return FormTemplate[]
     */
    public function getFormTemplates(): array
    {
        return $this->_formTemplates;
    }

    /**
     * @return ModelTemplate[]
     */
    public function getModelTemplates(): array
    {
        return $this->_modelTemplates;
    }

    private function loadAllTemplates()
    {
        $this->loadModelTemplates();
        $this->loadFormTemplates();
    }

    private function loadModelTemplates()
    {
        foreach (ModelTemplate::getTemplateNames() as $templateName) {
            $template = ModelTemplate::find($templateName);
            if ($template) {
                $this->_modelTemplates[$template->getFullName()] = $template;
            }
        }
    }

    private function loadFormTemplates()
    {
        foreach (FormTemplate::getTemplateNames() as $templateName) {
            $template = FormTemplate::find($templateName);
            if ($template) {
                $this->_formTemplates[$template->name] = $template;
            }
        }
    }

    /**
     * @param $event AfterFindEvent
     */
    public function afterTemplateFind($event)
    {
        $template = $event->template;
        if ($template instanceof FormTemplate) {
            $this->_formTemplates[$template->name] = $template;
        }
        if ($template instanceof ModelTemplate) {
            $this->_modelTemplates[$template->getFullName()] = $template;
        }
    }

    /**
     * Create a new model template from the supplied Json data
     * @param array $data Json data of the model template
     * @return bool Model could be created
     * @throws \yii\base\InvalidConfigException
     */
    public function createModel(array $data)
    {
        $model = new ModelTemplate();
        $model->scenario = ModelTemplate::SCENARIO_CREATE;
        $model->load($data, '');
        if ($model->validate()) {
            if ($model->save()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Update an existing data model from the supplied Json data
     * @param string $name Name of the existing data model
     * @param array $data Json of the modified data model
     * @return bool The data model could be updated.
     */
    public function updateModel(string $name, array $data)
    {
        $model = ModelTemplate::find($name);
        $model->scenario = ModelTemplate::SCENARIO_UPDATE;
        $model->load($data, '');
        if ($model->validate()) {
            if ($model->save()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns a model template or null if it cannot be found
     * @param string $name Name of the model template
     * @return ModelTemplate|null
     */
    public function getModelTemplate(string $name)
    {
        if (isset($this->_modelTemplates[$name]))
            return $this->_modelTemplates[$name];
        else {
            $modelTemplate = ModelTemplate::find($name);
            if ($modelTemplate) $this->_modelTemplates[$modelTemplate->getFullName()] = $modelTemplate;
            return $modelTemplate;
        }
    }

    /**
     * Returns a model template or null if it cannot be found
     * @param string $tableName Name of data table of the model template
     * @return ModelTemplate|null
     */
    public function getModelTemplateByDataTable(string $tableName)
    {
        foreach ($this->_modelTemplates as $modelTemplate) {
            if ($modelTemplate->table == $tableName) return $modelTemplate;
        }
        return null;
    }


    /**
     * Returns a form template or null if it cannot be found
     * @param string $name Name of the form template
     * @return FormTemplate|null
     */
    public function getFormTemplate(string $name)
    {
        return FormTemplate::find($name);
    }

    /**
     * Deletes the model template with the given name
     * @param string $name Name of the model template to be deleted
     */
    public function deleteModelTemplate(string $name)
    {
    }

    /**
     * Returns the module (= table set) name of a model template
     * @param $modelName
     * @return string|null
     */
    public function getModelModule($modelName)
    {
        $model = ModelTemplate::find($modelName);
        if ($model) {
            return $model->module;
        }
        return null;
    }

    public function update($template)
    {
        if ($template instanceof app\components\templates\ModelTemplate)
            $this->_modelTemplates[$template->getFullName()] = $template;
        else
            $this->_formTemplates[$template->name] = $template;
    }

}
