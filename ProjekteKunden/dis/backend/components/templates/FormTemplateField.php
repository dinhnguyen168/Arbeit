<?php
namespace app\components\templates;


use yii\base\Model;

/**
 * Class FormTemplateField
 *
 * Describes one input file of a form template
 *
 * @package app\components\templates
 *
 * @property boolean searchable
 */
class FormTemplateField extends Model
{
    /**
     * @var string Name of the field
     */
    public $name;

    /**
     * @var string Label for the field
     */
    public $label;

    /**
     * @var Description that is shown, when the field gets the focus
     */
    public $description;

    /**
     * @var string Validators for the field
     */
    public $validators;

    /**
     * @var array Input type for the field
     */
    public $formInput;

    /**
     * @var string Name of the group in which this field is displayed
     */
    public $group;

    /**
     * @var integer Order of the field in the group
     */
    public $order;
    /**
     * @var string field formatter in data table
     */
    public $formatter;
    /**
     * @var boolean
     */
    public $showAsAdditionalFilter = false;

    /**
     * @var FormTemplate Form template to which this field belongs
     */
    private $_form;


    /**
     * FormTemplateField constructor.
     * Assign form.
     * @param $parentForm
     * @param array $config
     */
    public function __construct($parentForm, array $config = [])
    {
        $this->_form = $parentForm;
        parent::__construct($config);
    }

    public function getSearchable () {
        $formDataModelTemplate = \Yii::$app->templates->getModelTemplate($this->_form->dataModel);
        return $formDataModelTemplate->columns[$this->name]->searchable;
    }

    public function setSearchable () {}

    /**
     * {@inheritdoc}
     * @return array
     */
    public function rules()
    {
        return [
            [['name', 'label', 'formInput', 'group', 'order'], 'required'],
            [['name', 'label', 'description', 'group', 'formatter'], 'string'],
            [['order'], 'integer'],
            ['validators', 'default', 'value' => []],
            ['validators', 'each', 'rule' => ['validateValidator']],
            ['formInput', 'validateFormInput'],
            ['formatter', 'validateFormatter'],
            [['searchable', 'showAsAdditionalFilter'], 'boolean'],
//            [['formatter'], function ($attribute, $params, $validator) {
//                if (!str_contains($this->{$attribute}, '${value')) {
//                    $this->addError($attribute, 'formatter must have ${value} in it.');
//                }
//            }],
        ];
    }

    /**
     * TODO: How can the validity of a validator be testet?
     * @return bool
     */
    public function validateValidator () {
        return true;
    }


    /**
     * Validates the form input value
     * @return bool true
     */
    public function validateFormInput () {
        $modelTemplateColumn = $this->_form->getDataModelTemplateColumn($this->name);
        if ($modelTemplateColumn && $modelTemplateColumn->type == 'pseudo') {
            $this->formInput['disabled'] = true;
        }

        if ($this->formInput['calculate'] > "") {
            $this->validateCalculate($this->formInput['calculate']);
        }
        if ($this->formInput['type'] === 'select') {
            $this->validateSelectSource();
        }
    }

    protected function validateCalculate ($calc) {
        if (str_contains($calc, '$this') || str_contains($calc, 'this->')) {
            $this->addError('calculate', 'Please use javascript notation, not PHP! ("this." instead of "$this->"');
        }
        $calc = preg_replace("/this\\.formModel\\[['\"]([^'\"]+)['\"]\\]/", "this.$1", $calc);
        $calc = preg_replace('/\[([a-zA-Z_]+)\]/', 'this.$1', $calc);

        $validColumns = [];
        foreach ($this->_form->fields as $fieldModel) {
            $validColumns[] = $fieldModel->name;
        }

        $matches = [];
        $invalidColumns = [];
        if (preg_match_all('/this\\.([a-zA-Z_]+)(?!\\()/', $calc, $matches)) {
            foreach ($matches[1] as $columnName) {
                if (!in_array($columnName, $validColumns)) {
                    $invalidColumns[] = $columnName;
                }
            }
            if (count($invalidColumns) > 0) {
                if (count($invalidColumns) > 1)
                    $this->addError('calculate', implode(', ', $invalidColumns) . " are not valid column names");
                else
                    $this->addError('calculate', $invalidColumns[0] . " is not valid column name");
            }
        }
    }

    public function validateFormatter() {
        $formatter = $this->formatter;
        if (str_contains($formatter, '$this') || str_contains($formatter, 'this->')) {
            $this->addError('formatter', 'Please use javascript notation, not PHP! ("this." instead of "$this->"');
            return;
        }
        $formatter = preg_replace("/this\\.formModel\\[['\"]([^'\"]+)['\"]\\]/", "this.$1", $formatter);
        $formatter = preg_replace('/\[([a-zA-Z_]+)\]/', 'this.$1', $formatter);

        $validColumns = [];
        foreach ($this->_form->fields as $fieldModel) {
            $validColumns[] = $fieldModel->name;
        }

        $matches = [];
        $invalidColumns = [];
        if (preg_match_all('/this\\.([a-zA-Z_]+)(?!\\()/', $formatter, $matches)) {
            foreach ($matches[1] as $columnName) {
                if (!in_array($columnName, $validColumns)) {
                    $invalidColumns[] = $columnName;
                }
            }
            if (count($invalidColumns) > 0) {
                if (count($invalidColumns) > 1)
                    $this->addError('formatter', implode(', ', $invalidColumns) . " are not valid column names");
                else
                    $this->addError('formatter', $invalidColumns[0] . " is not valid column name");
            }
        }
    }

    /**
     *
     */
    public function validateSelectSource () {
        $selectSource = $this->formInput['selectSource'];
        $formDataModelTemplate = \Yii::$app->templates->getModelTemplate($this->_form->dataModel);
        $columnDataType = $formDataModelTemplate->columns[$this->name]->type;
        if (isset($this->formInput['multiple']) && $this->formInput['multiple'] && !in_array($columnDataType, ['string_multiple', 'many_to_many', 'pseudo'])) {
            // the current column must be a string
            $this->addError($this->name, "Multiple can only be set on a string (multiple) column.");
        }
        if ($selectSource['type'] === 'api') {
            if (empty($selectSource['model'])) {
                $this->addError($this->name, "Model name is required.");
            }

            $linkedModelTemplate = \Yii::$app->templates->getModelTemplate($selectSource['model']);
            if (!$linkedModelTemplate)
                $this->addError ($this->name, "Linked model not found:" . $selectSource['model']);
            else {
                if (!isset($selectSource['textField']) || $selectSource['textField'] == '') $this->addError($this->name, "Select text field missing/empty");
                if (!isset($selectSource['valueField']) || $selectSource['valueField'] == '')
                    $this->addError($this->name, "Select value field missing/empty");
                else if (!isset($linkedModelTemplate->columns[$selectSource['valueField']]))
                    $this->addError($this->name, "Linked model template (" . $linkedModelTemplate->fullName . ") does not have a column '" . $selectSource['valueField'] . "'");
            }

            // check whether data types of the source value field and the current column matches
            if (isset($this->formInput['multiple']) && !$this->formInput['multiple'] && !empty($selectSource['valueField'])) {
                // ['integer', 'double', 'string', 'string_multiple', 'dateTime', 'date', 'time']
                $foreignModelTemplate = \Yii::$app->templates->getModelTemplate($selectSource['model']);
                if (empty($foreignModelTemplate)) {
                    $this->addError($this->name, "The list source model (" . $selectSource['model']. ") does not exist");
                } else {
                    $foreignColumnDataType = $foreignModelTemplate->columns[$selectSource['valueField']]->type;
                    if ($columnDataType !== $foreignColumnDataType) {
                        $this->addError($this->name, "Value Column data type ($foreignColumnDataType) does not match the input column data type ($columnDataType).");
                    }
                    if (!empty($selectSource['extraFilter'])) {
                        $cRegexp = '/(?:^|&)([a-zA-Z_-]+) *(!==|!=|==|=|<=|<|>=|>) *([^&#]+)/';
                        if (preg_match_all($cRegexp, $selectSource['extraFilter'], $matched, PREG_SET_ORDER)) {
                            foreach ($matched as $match) {
                                $column = $match[1];
                                if (!isset($foreignModelTemplate->columns[$column])) {
                                    $this->addError($this->name, "column $column in extraFilter does not exist in linked table $foreignModelTemplate->table");
                                }

                                $value = $match[3];
                                // TODO: Check value
                            }
                        }
                        else {
                            $this->addError('formInput', "the select source extra filter is invalid.");
                        }

                    }
                }
            }
        }else if ($selectSource['type'] === 'many_relation') {
            if (empty($selectSource['model'])) {
                $this->addError('formInput', "Model name is required.");
            }
            $foreignModelTemplate = \Yii::$app->templates->getModelTemplate($selectSource['model']);
            if (empty($foreignModelTemplate)) {
                $this->addError('formInput', "The source model (" . $selectSource['model']. ") does not exist");
            }
        } else if ($selectSource['type'] === 'one_relation') {
            if (empty($selectSource['model'])) {
                $this->addError('formInput', "Model name is required.");
            }
            $foreignModelTemplate = \Yii::$app->templates->getModelTemplate($selectSource['model']);
            if (empty($foreignModelTemplate)) {
                $this->addError('formInput', "The source model (" . $selectSource['model']. ") does not exist");
            }
        } else {
            if (empty($this->formInput['selectSource']['listName'])) {
                $this->addError('formInput', "list name cannot be empty.");
            }
            if (!in_array($columnDataType, ['string_multiple', 'string', 'textarea', 'pseudo'])) {
                $this->addError('formInput', "data type of List Value column must be a string. ($columnDataType) was found.");
            }
        }
        if (empty($selectSource['valueField'])) {
            $this->addError('formInput', "Value Column name is required.");
        }
    }

    /**
     * Converts the content of the calculate field into a string appropriate for Javascript
     * @return string
     */
    public function getJsCalculate () {
        if ($this->formInput['calculate'] > "") {
            $calc = $this->formInput['calculate'];
            $calc = trim($calc, "\t\n\r\0\x0B=");

            $regex = '/^((?!this.formModel).)*$/m';
            if(preg_match($regex, $calc)) {
                // replace fields name with form model properties
                $calc = preg_replace('/\[(\S*)\]/m', "this.formModel['$1']", $calc);
            }
            // replace absolute function
            $calc = preg_replace('/ABS/m', "Math.abs", $calc);
            return $calc;
        }
        return "";
    }

    /**
     * TODO??
     * @return array
     */
    public function fields () {
        return array_merge(parent::fields(), [
            'searchable',
            'formInput' => function ($model) {
                return array_merge($model->formInput, [
                    'jsCalculate' => $this->getJsCalculate()
                ]);
            }
        ]);
    }
}
