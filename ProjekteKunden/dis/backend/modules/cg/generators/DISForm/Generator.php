<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace app\modules\cg\generators\DISForm;

use app\components\templates\FormTemplateField;
use Yii;
use yii\base\Exception;
use yii\gii\CodeFile;
use yii\helpers\Inflector;

/**
 * This generator will generate one or multiple ActiveRecord classes for the specified database table.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class Generator extends \yii\gii\Generator
{
    public $templateName = '{}';
    public $templates = [
        'default' => '@app/modules/cg/generators/DISForm/default',
        'ArchiveFile_files' => '@app/modules/cg/generators/DISForm/ArchiveFile_files',
    ];

    protected $name = '';
    protected $dataModel = '';
    protected $fields;
    protected $requiredFilters;
    protected $subForms = [];
    protected $supForms = [];
    protected $filterDataModels;

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'DIS Form Generator';
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return 'This generator creates a form to edit a specific table.';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [['templateName'], 'required'],
            [['templateName'], 'string']
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'templateName' => 'Template Name'
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function hints()
    {
        return array_merge(parent::hints(), [
            'templateName' => 'The template name to be loaded',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function requiredTemplates()
    {
        return ['index.vue.php'];
    }

    /**
     * {@inheritdoc}
     */
    public function stickyAttributes()
    {
        return array_merge(parent::stickyAttributes(), ['templateName']);
    }

    public function generate()
    {
        $model = Yii::$app->templates->getFormTemplate($this->templateName);
        $this->name = $model->name;
        $this->dataModel = $model->dataModel;
        $this->fields = $model->fields;
        $this->requiredFilters = $model->requiredFilters;
        if (isset($model->subForms)) {
            $this->subForms = $model->subForms;
        }
        if (isset($model->supForms)) {
            $this->supForms = $model->supForms;
        }
        $this->filterDataModels = $model->filterDataModels;

        $customCodeTemplate = $this->dataModel . '_' . $this->name;
        if (array_key_exists($customCodeTemplate, $this->templates)) {
            $this->template = $customCodeTemplate;
        }

        $files[] = new CodeFile(
            $this->getGeneratedFormFilePath(),
            $this->render('index.vue.php', [
                'name' => $this->name,
                'dataModel' => $this->dataModel,
                'fieldsGroups' => $this->getFieldsGroups(),
                'validators' => $this->getFormValidators(),
                'simpleFields' => $this->getSimpleFields(),
                'selectInputSources' => $this->getSelectInputsSources(),
                'requiredFilters' => $this->requiredFilters,
                'subForms' => $this->subForms,
                'supForms' => $this->supForms,
                'filterDataModels' => $this->filterDataModels,
                'calculatedFields' => $this->getCalculatedFields()
            ])
        );

        $customFormFile = $this->getCustomFormFilePath();
        if (file_exists($customFormFile)) {
            $files[] = new CodeFile(
                $customFormFile,
                $this->render('index.vue.php', [
                    'name' => $this->name,
                    'dataModel' => $this->dataModel,
                    'fieldsGroups' => $this->getFieldsGroups(),
                    'validators' => $this->getFormValidators(),
                    'simpleFields' => $this->getSimpleFields(),
                    'selectInputSources' => $this->getSelectInputsSources(),
                    'requiredFilters' => $this->requiredFilters,
                    'subForms' => $this->subForms,
                    'supForms' => $this->supForms,
                    'filterDataModels' => $this->filterDataModels,
                    'calculatedFields' => $this->getCalculatedFields()
                ])
            );
        }

        $files[] = new CodeFile(
            $this->getFormClassFilePath(),
            $this->render('form-class.php', [
                'name' => $this->name,
                'namespace' => 'app\\forms',
                'className' => Inflector::id2camel($this->name . ' -form'),
                'parentClassName' => "\\app\\models\\$this->dataModel",
                'formFields' =>  $this->getFormClassFields(),
                'fieldsGroups' => $this->getFieldsGroups()
            ])
        );

        $files[] = new CodeFile(
            $this->getFormSearchClassFilePath(),
            $this->render('form-search-class.php', [
                'name' => $this->name,
                'namespace' => 'app\\forms',
                'className' => Inflector::id2camel($this->name . ' -form-search'),
                'parentClassName' => "\\app\\models\\".$this->dataModel . 'Search'
            ])
        );

        return $files;
    }

    public function getCalculatedFields() {
        $calculatedFields = [];
        /* @var $field FormTemplateField */
        foreach ($this->fields as $field) {
            if (!empty($field->formInput['calculate'])) {
                $calc = $field->formInput['calculate'];
                $calc = trim($calc, "\t\n\r\0\x0B=");
                // replace fields name with form model properties
                $calc = preg_replace('/\[(\S*)\]/m', "this.formModel['$1']", $calc);
                // replace absolute function
                $calc = preg_replace('/ABS/m', "Math.abs", $calc);
                $calculatedFields[$field->name] = trim($calc);
            }
        }
        return $calculatedFields;
    }

    public function getGeneratedFormFilePath() {
        return $this->getCustomFormFilePath() . '.generated';
    }

    public function getCustomFormFilePath() {
        return realpath(Yii::getAlias('@app/../src/forms/')) . '/' . Inflector::id2camel($this->name . ' -form') . '.vue';
    }

    public function getFormClassFilePath() {
        return realpath(Yii::getAlias('@app')) . '/forms/' . Inflector::id2camel($this->name . ' -form') . '.php';
    }

    public function getFormSearchClassFilePath() {
        return realpath(Yii::getAlias('@app')) . '/forms/' . Inflector::id2camel($this->name . ' -form-search') . '.php';
    }

    public function getSelectInputsSources() {
        $sources = [];
        /* @var $field FormTemplateField */
        foreach ($this->fields as $field) {
            if ($field->formInput['type'] == 'select') {
                $sources[$field->name] = $field->formInput['selectSource'];
            }
        }
        return $sources;
    }

    public function getFieldsGroups() {
        $groups = [];
        /* @var $field FormTemplateField */
        foreach ($this->fields as $field) {
            $index = $field->group;
            $groupField = [
                'name' => $field->name,
                'label' => $field->label,
                'description' => $field->description,
                'formInput' => $field->formInput,
                'isNumeric' => false,
                'componentName' => $this->getComponentName($field->formInput['type'])
            ];
            foreach ($field->validators as $validator) {
                if ($validator['type'] == 'number') {
                    $groupField['isNumeric'] = true;
                    break;
                }
            }
            $groups[$index][] = $groupField;
        }
        return $groups;
    }

    public function getFormValidators() {
        $validators = [];
        /* @var $field FormTemplateField */
        foreach ($this->fields as $field) {
            $validators[$field['name']] = $field->validators;
        }
        return $validators;
    }

    public function getComponentName($type)
    {
        switch (strtolower($type)) {
            case 'text':
                return 'DisTextInput';
            case 'autoincrement':
                return 'DisAutoIncrementInput';
            case 'select':
                return 'DisSelectInput';
            case 'switch':
                return 'DisSwitchInput';
            case 'datetime':
                return 'DisDateTimeInput';
            case 'date':
                return 'DisDateInput';
            case 'time':
                return 'DisTimeInput';
            case 'textarea':
                return 'DisTextareaInput';
            default:
                throw new Exception('Not supported input type "'. $type .'"');
        }
    }

    public function getSimpleFields()
    {
        $fields = [];
    /* @var $field FormTemplateField */
        foreach ($this->fields as $field) {
            $fields[] = [
                'name' => $field->name,
                'label' => $field->label,
                'description' => $field->description,
                'group' => $field->group,
                'order' => $field->order,
                'inputType' => $field->formInput['type'],
                'searchable' => $field->searchable
            ];
        }
        return $fields;
    }

    public function getFormClassFields () {
        $modelTemplate = Yii::$app->templates->getModelTemplate($this->dataModel);
        $pseudoColumns = array_filter($modelTemplate->columns, function ($c) { return $c->type == 'pseudo'; });
        $pseudoColumnsNames = array_map(function ($elem) { return $elem['name']; }, $pseudoColumns);
        $simpleFieldsNames = array_map(function ($elem) { return $elem['name']; }, $this->getSimpleFields());
        return array_merge(array_diff($simpleFieldsNames, $pseudoColumnsNames), !empty($this->requiredFilters[0]) ? [$this->requiredFilters[0]['as']] : []);
    }


    public function save($files, $answers, &$results)
    {
        $timestamp = time();
        if(parent::save($files, $answers, $results)) {
            /* @var $model FormTemplate */
            $model = Yii::$app->templates->getFormTemplate($this->templateName);
            $model->generatedAt = $timestamp;
            $model->save(false);
            return true;
        }
    }
}
