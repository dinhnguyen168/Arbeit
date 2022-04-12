<?php
namespace app\components\templates;

use app\modules\api\common\controllers\FormController;
use Yii;
use yii\base\Exception;
use yii\helpers\Inflector;
use yii\helpers\Json;
use ZipArchive;
/**
 * Class FormTemplate
 *
 * Describes a form template, that can be edited in the template manager.
 * A form template creates a form to edit data.
 *
 * @package app\components\templates
 * @property array filterDataModels Form filter data models
 * @property array requiredFilters Form required filters
 */
class FormTemplate extends BaseTemplate
{
    /**
     * @var string Path for the json file of form templates
     */
    protected static $templatesPath = __DIR__ . "/../../dis_templates/forms";

    /**
     * @var string Path for the PHP classes of form templates
     */
    protected static $formClassPath =  __DIR__ . "/../../forms";

    /**
     * @var string Name of the form. Is used to create the filename.
     */
    public $name;

    /**
     * @var string Name of the data model (i.e. "CoreCore")
     */
    public $dataModel;

    /**
     * @var FormTemplateField[] Array of the TemplateFields of the form. When loading a json structure, this is a json
     * array but is converted into an array of objects of class FormTemplateField afterwards.
     */
    public $fields;

    public $subForms;
    public $supForms;

    /**
     * @var string[] Array of available subforms (forms of child records)
     */
    private $_availableSubForms;

    /**
     * @var string[] Array of available superforms (forms of parent record)
     */
    private $_availableSupForms;

    /**
     * @var ModelTemplate form's data model object
     */
    private $_dataModelTemplate;

    protected function fixFormName ($formName) {
        $formName = Inflector::camel2id(preg_replace('/\s+/', '', ucwords($formName)));
        $formName = trim(preg_replace('/[0-9]+/', '-$0-', $formName), '-');
        return str_replace('--', '-', $formName);
    }

    public function getFilterDataModels () {
        return $this->_dataModelTemplate->getFilterDataModels();
    }

    public function getRequiredFilters () {
        if ($this->_dataModelTemplate) {
            return $this->_dataModelTemplate->getRequiredFilters();
        }
        return [];
    }

    public function fields()
    {
        return array_merge(parent::fields(), [
            'filterDataModels',
            'requiredFilters'
        ]);
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_CREATE] = ['name', 'dataModel', 'fields', 'availableSupForms', 'availableSubForms', 'createdAt', 'modifiedAt', 'generatedAt'];
        $scenarios[self::SCENARIO_UPDATE] = ['fields', 'availableSupForms', 'availableSubForms', 'createdAt', 'modifiedAt', 'generatedAt'];
        return $scenarios;
    }

    public function getDataModelTemplate() {
        if (!$this->_dataModelTemplate && $this->dataModel) {
            $this->_dataModelTemplate = ModelTemplate::find($this->dataModel);
        }
        return $this->_dataModelTemplate;
    }

    /**
     * Returns the ModelTemplateColumn for the given columnName
     * @param $columnName Name of the column
     * @return ModelTemplateColumn|null
     * @throws \yii\base\InvalidConfigException
     */
    public function getDataModelTemplateColumn ($columnName) {
        $this->getDataModelTemplate();
        if (isset($this->_dataModelTemplate->columns[$columnName]))
            return $this->_dataModelTemplate->columns[$columnName];
        else
            return null;
    }

    /**
     * setter for $_availableSubForms
     * @param array $subForms
     */
    public function setAvailableSubForms (array $subForms) {
        $this->_availableSubForms = $subForms;
    }

    /**
     * setter for $_availableSupForms
     * @param array $supForms
     */
    public function setAvailableSupForms (array $supForms) {
        $this->_availableSupForms = $supForms;
    }

    public function getFormClassShortName () {
        return Inflector::id2camel($this->name . '-form');
    }

    /**
     * Return the full class name of the forms model class based on its name
     * @return string Full class name
     */
    public function getFormClass () {
        return "\\app\\forms\\" . $this->getFormClassShortName();
    }

    /**
     * Return the absolute file path of the forms PHP model class file
     * @return string File path of the forms model class
     */
    public function getFormClassFilePath () {
        return $this->resolvedAbsolutePath(Yii::getAlias(static::$formClassPath . '/' . $this->getFormClassShortName() . '.php'));
    }

    /**
     * Returns the modified time of the forms PHP model class file (or false, if it does not exist)
     * @link https://www.php.net/manual/de/function.filemtime.php
     * @return bool|int
     */
    public function getFormClassFileModified () {
        $filePath = $this->getFormClassFilePath();
        if (file_exists($filePath)) {
            return filemtime($filePath);
        }
        return false;
    }

    public function getFormSearchClassShortName () {
        return Inflector::id2camel($this->name . '-form-search');
    }
    /**
     * Returns the full class name of the forms search model PHP class
     * @return string Full class name of search class
     */
    public function getFormSearchClass () {
        return "\\app\\forms\\" . $this->getFormSearchClassShortName();
    }

    /**
     * Return the absolute file path of the forms search model class PHP file
     * @return string File path of the forms search model class
     */
    public function getFormSearchClassFilePath () {
        return $this->resolvedAbsolutePath(Yii::getAlias('@app') . '/forms/' . $this->getFormSearchClassShortName() . '.php');
    }


    /**
     * Returns the modified time of the forms PHP search model class file (or false, if it does not exist)
     * @link https://www.php.net/manual/de/function.filemtime.php
     * @return bool|int
     */
    public function getFormSearchClassFileModified () {
        $filePath = $this->getFormSearchClassFilePath();
        if (file_exists($filePath)) {
            return filemtime($filePath);
        }
        return false;
    }


    /**
     * Return the absolute file path of the generated vue file of the form
     * @return string
     */
    public function getFormComponentFilePath () {
        return $this->resolvedAbsolutePath(Yii::getAlias(Yii::getAlias("@app") . "/../src/forms/" . $this->getFormClassShortName()  . ".vue.generated"));
    }


    /**
     * Return the absolute file path of the customized vue file of the form
     * @return string
     */
    public function getCustomFormComponentFilePath () {
        return $this->resolvedAbsolutePath(Yii::getAlias(Yii::getAlias("@app") . "/../src/forms/" . $this->getFormClassShortName()  . ".vue"));
    }


    /**
     * Returns the modified time of the generated vue file of the form (or false, if it does not exist)
     * @link https://www.php.net/manual/de/function.filemtime.php
     * @return bool|int
     */
    public function getFormComponentFileModified () {
        $filePath = $this->getFormComponentFilePath();
        if (file_exists($filePath)) {
            return filemtime($filePath);
        }
        return false;
    }


    /**
     * {@inheritdoc}
     * @return array
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [['name', 'dataModel', 'fields'], 'required'],
            [['name', 'dataModel'], 'string'],
            ['name', 'validateName', 'on' => self::SCENARIO_CREATE],
            [['subForms', 'supForms'], 'safe', 'on' => self::SCENARIO_DEFAULT ],
            ['dataModel', 'validateDataModel']
            // [['fields'], 'required'],
        ]);
    }

    /**
     * Validates, that a form template has to have a unique name. If a file for this name already exist, a error is reported.
     */
    public function validateName () {
        if ($this->scenario == self::SCENARIO_CREATE) {
            $existingFormTemplate = \Yii::$app->templates->getFormTemplate($this->name);
            if ($existingFormTemplate) {
                $existingFormTemplateModel = $existingFormTemplate->getDataModelTemplate();
                throw new Exception('A form named "' . $existingFormTemplate->name . '" already exists' . ($existingFormTemplateModel ? ' for model ' . $existingFormTemplateModel->name: ''));
            }
            elseif (!$this->isNewFile)
                $this->addError('name', 'Form name "' . $this->name . '" already exists');
        }
    }

    /**
     * Validates that the data model exists and at least the base class is generated
     */
    public function validateDataModel () {
        $model = Yii::$app->templates->getModelTemplate($this->dataModel);
        if ($model != null) {
            if (!$model->getBaseClassFileModified()) {
                $this->addError('dataModel', "the form data model base class does not exists");
            }
            if (!$model->getBaseSearchClassFileModified()) {
                $this->addError('dataModel', "the form data model base search does not exists");
            }
        } else {
            $this->addError('dataModel', "the form data model ($this->dataModel) does not exists");
        }
    }

    /**
     * Validates the form template and all fields of the form.
     * If an error occurs on a form field, the error message is modified.
     * @param null|string[] $attributeNames
     * @param bool $clearErrors
     * @return bool Is the form template valid?
     */
    public function validate($attributeNames = null, $clearErrors = true)
    {
        $this->_dataModelTemplate = ModelTemplate::find($this->dataModel);
        $thisValid = parent::validate($attributeNames, $clearErrors) && $this->_dataModelTemplate;
        if ($this->_dataModelTemplate) {
            $fieldsValid = true;
            foreach ($this->fields as $formField) {
                if (!$formField->validate()) {
                    $fieldsValid = false;
                    foreach ($formField->getErrors() as $attribute => $message) {
                        $this->addError('fields.' . $formField->name, "$attribute: $message[0]");
                    }
                }
            }
        }
        return $thisValid && $fieldsValid;
    }


    /**
     * Generates the child forms definitions in the json templates
     * based on the selected form names
     * @throws \Exception
     */
    public function generateSubForms() {
        $this->subForms = [];
        foreach ($this->_availableSubForms as $formName) {
            $json = $this->getFormJson($formName);
            if ($json == false) {
                return;
            }
            $dataModel = $json["dataModel"];
            $modelClass = $this->getModelClass($dataModel);
            $shortName = lcfirst($formName);

            $formData = [
                "buttonLabel" => $formName, // Better title of the form
                "url" => "/forms/" . $shortName . "-form",
                "filter" => []
            ];

            $ancestors = constant($modelClass . "::ANCESTORS");
            foreach (array_reverse($ancestors) AS $shortAncestor => $ancestorClassName) {
                if ($shortAncestor !== "program") {
                    $formData["filter"][] = [
                        "unit" => $shortAncestor,
                        "fromField" => ($ancestorClassName == $this->dataModel ? "id" : $shortAncestor . "_id")
                    ];
                }
            }
            $this->subForms[$shortName] = $formData;
        }
    }

    /**
     * Generates the parent forms definitions in the json templates
     * based on the selected form names
     * @throws \Exception
     */
    public function generateSupForms() {
        $this->supForms = [];
        foreach ($this->_availableSupForms as $formName) {
            $json = $this->getFormJson($formName);
            if ($json == false) {
                return;
            }
            $dataModel = $json["dataModel"];
            $modelClass = $this->getModelClass($dataModel);
            $shortName = lcfirst($formName);
            $parentIdField = '';
            $parentForm = self::find($shortName);
            if ($parentForm) {
                $parentFormDataModel = ModelTemplate::find($parentForm->dataModel);
                if ($parentFormDataModel) {
                    $parentIdField = lcfirst($parentFormDataModel->name) . '_id';
                }
            }

            $formData = [
                "buttonLabel" => $formName, // Better title of the form
                "url" => "/forms/" . $shortName . "-form",
                "parentIdField" => $parentIdField,
                "filter" => []
            ];

            $ancestors = constant($modelClass . "::ANCESTORS");
            foreach (array_reverse($ancestors) AS $shortAncestor => $ancestorClassName) {
                if ($shortAncestor !== "program") {
                    $formData["filter"][] = [
                        "unit" => $shortAncestor,
                        "fromField" => ($ancestorClassName == $this->dataModel ? "id" : $shortAncestor . "_id")
                    ];
                }
            }
            $this->supForms[$shortName] = $formData;
        }
    }

    /**
     * Returns the full class of the data model for this form template
     * @param string $modelClassName
     * @return string full class of data model
     * @throws \Exception
     */
    protected function getModelClass($modelClassName = "") {
        if ($modelClassName == "") {
            $modelClassName = $this->dataModel;
        }
        $baseModelPath = \Yii::getAlias("@app/models/base") . "/";
        $baseModelFile = $baseModelPath . "Base" . $modelClassName . ".php";
        if (!file_exists($baseModelFile)) {
            throw new \Exception ("FormsTemplate::getModelClass() baseModelFile does not exist: " . $baseModelFile);
        }
        $modelClass = '\\app\\models\\base\\Base' . $modelClassName;
        return $modelClass;
    }

    /**
     * Returns the json structure of the form template. It is loaded from the corresponding file in the directory $templatesPath
     * @param $formName
     * @return array Json structure of the form template
     */
    protected function getFormJson ($formName){
        $json = false;
        $formFileName = static::$templatesPath . "/" . $formName . ".json";
        if (file_exists($formFileName)) {
            $json = Json::decode(file_get_contents($formFileName));
        }
        return $json;
    }


    /**
     * Before saving the form template as a json file (@see BaseTemplate::save(), some things have to be updated:
     * - SubForms and SuperForms ... TODO
     * - ... TODO
     * @param bool $setModifiedAt
     * @return bool Can be saved
     * @throws \Exception
     */
    public function beforeSave($setModifiedAt = true)
    {
        // generate sub & sup forms
        if (($this->scenario == self::SCENARIO_CREATE || $this->scenario == self::SCENARIO_UPDATE) && (!is_array($this->_availableSubForms) || !is_array($this->_availableSubForms))) {
            throw new \Exception('available sup/sub forms are not set properly');
        }
        if (is_array($this->_availableSubForms)) {
            $this->generateSubForms();
        }
        if (is_array($this->_availableSupForms)) {
            $this->generateSupForms();
        }

        return parent::beforeSave($setModifiedAt);
    }

    /**
     * Template file name from the new form loaded data @see BaseTemplate::load()
     * @return string the file name of the template file
     */
    protected function fileName()
    {
        return $this->name;
    }

    /**
     * Converts the fields, that are loaded as a json structure into FormTemplateField objects
     */
    protected function populateAfterLoad()
    {
        if ($this->dataModel) {
            $this->_dataModelTemplate = ModelTemplate::find($this->dataModel);

            if ($this->_dataModelTemplate) {
                $unsetFields = [];
                foreach ($this->fields as $key => $value) {
                    if (is_array($value)) {
                        if (isset($value["name"]) && isset($this->_dataModelTemplate->columns[$value["name"]]))
                            $this->fields[$key] = new FormTemplateField($this, $value);
                        else
                            $unsetFields[] = $key;
                    }
                }

                foreach (array_reverse($unsetFields) as $key) {
                    unset($this->fields[$key]);
                }
                $this->fields = array_values($this->fields);
            }
        }
    }

    /**
     * Return an array of the generated PHP and vue files for this form templates.
     * The absolute file paths and modified times are returned for each file.
     * @return string[] Array of generated PHP and vue files
     */
    function getGeneratedFiles()
    {
        $generatedFiles = [
            [
                'path' => $this->getFormClassFilePath(),
                'modified' => $this->getFormClassFileModified()
            ],
            [
                'path' => $this->getFormSearchClassFilePath(),
                'modified' => $this->getFormSearchClassFileModified()
            ]
        ];


        $vueCustomFile = $this->getCustomFormComponentFilePath();
        $vueGeneratedFile = $this->getFormComponentFilePath();
        // If a the generated vue file exists or else a customized vue file does also not exist,
        // add the generated vue file.
        if (file_exists($vueGeneratedFile) || !file_exists($vueCustomFile)) {
            $generatedFiles[] = [
                'path' => $this->getFormComponentFilePath(),
                'modified' => $this->getFormComponentFileModified()
            ];

        }
        return $generatedFiles;
    }


    /**
     * Return a the customized vue file and its modification time or null if it does not exist.
     * @return array|null
     */
    function getCustomVueFile() {
        $aFiles = [];
        $vueFile = $this->getCustomFormComponentFilePath();
        if (file_exists($vueFile)) {
            return [
                'path' => $vueFile,
                'modified' => $this->getFormComponentFileModified()
            ];
        }
        else
            return null;
    }

    /**
     * Deletes all generated files and even the customized vue file, if that exists
     */
    public function cleanUpGeneratedFiles()
    {
        // rename customized form if exists
        if ($this->getCustomVueFile() !== null) {
            $customFilePath = $this->getCustomVueFile()['path'];
            $newCustomFilePath = $customFilePath . '.deleted';
            $i = 0;
            while (file_exists($newCustomFilePath . ($i ? $i : ''))) {
                $i++;
            }
            rename($customFilePath, $newCustomFilePath . ($i ? $i : ''));
        }
        foreach ($this->getGeneratedFiles() as $file) {
            if (file_exists($file['path'])) unlink($file['path']);
        }
        $file = $this->getCustomFormComponentFilePath();
        if (file_exists($file)) unlink($file);
    }


    /**
     * Return an array of the forms, that are avaiable in this dis installation.
     * For this reason, the PHP form model classes are searched, since the vue files are optional.
     * @return string[]
     */
    public static function getGeneratedFormNames()
    {
        $names = [];
        foreach (glob(static::$formClassPath . '/*.php') as $file) {
            if (!preg_match("/Search\\.php$/", $file)) {
                $names[] = preg_replace("/Form\.php/", "", basename($file));
            }
        }
        return $names;
    }

    /**
     * When a template is saved, the access rights have to be updated
     * @param bool $setModifiedAt
     * @return bool Form template has been saved
     */
    public function save($setModifiedAt = true) {
        if (parent::save($setModifiedAt)) {
            FormController::updateAccessRights();
            return true;
        }
        return false;
    }


    /**
     * When a template is deleted, the access rights have to be updated
     * {@inheritdoc}
     * @return bool Form template has been deleted
     * @throws \yii\web\ServerErrorHttpException
     */
    public function delete()
    {
        if (parent::delete()) {
            FormController::updateAccessRights();
            return true;
        }
        return false;
    }

    public function getDownloadZip()
    {
        $zip = new ZipArchive();
        $tmp_file = sys_get_temp_dir() . "/" . $this->name. ".zip";
        if(file_exists($tmp_file)){
            $zip->open($tmp_file, ZipArchive::OVERWRITE);
        }
        else{
            $zip->open($tmp_file, ZipArchive::CREATE);
        }
        $zip->addFile($this->_filePath, 'backend/dis_templates/forms/' . $this->name.'.json');
        $zip->addFile($this->getFormClassFilePath(), substr($this->getFormClassFilePath(), strpos($this->getFormClassFilePath(), 'backend/')));
        $zip->addFile($this->getFormSearchClassFilePath(), substr($this->getFormSearchClassFilePath(), strpos($this->getFormSearchClassFilePath(), 'backend/')));
        if ($this->getCustomVueFile() !== null) {
            $customFilePath = $this->getCustomVueFile()['path'];
            $zip->addFile($customFilePath, substr($customFilePath, strpos($customFilePath, 'src/')));
        }
        $zip->close();
        return $tmp_file;
    }

    protected function getExistingGeneratedFilesPaths () {
        return array_map(
            function ($item) {
                return $item['path'];
            },
            array_filter(
                $this->getGeneratedFiles(),
                function ($item) {
                    return $item['modified'];
                }
            )
        );
    }
    /**
     * @param $newFormName string form new name
     * @return array of rename files rules e.g.
     * [
     *  [
     *      "fromPath" => path
     *      "toPath" => path
     *      "contentChanges" => [ "pattern" => regex, "replacement" => string ]
     *  ]
     * ]
     */
    public function getFilesRenameRules ($newFormName) {
        $oldFormName = $this->name;
        $rules = [];
        $oldClassName = $this->getFormClassShortName();
        $newClassName = Inflector::id2camel($newFormName . '-form');
        $filesToRename = $this->getExistingGeneratedFilesPaths();
        if ($this->getCustomVueFile()) {
            $filesToRename[] = $this->getCustomVueFile()['path'];
        }
        foreach ($filesToRename as $filePath) {
            $rules [] = [
                "fromPath" => $filePath,
                "toPath" => preg_replace("/$oldClassName/", $newClassName, $filePath),
                "contentChanges" => [
                    "pattern" => "/$oldClassName/",
                    "replacement" => $newClassName,
                ]
            ];
        }
        return $rules;
    }

    public function rename($newFormName) {
        $existingFormTemplate = \Yii::$app->templates->getFormTemplate($newFormName);
        if ($existingFormTemplate) {
            $existingFormTemplateModel = $existingFormTemplate->getDataModelTemplate();
            throw new Exception('A form "' . $existingFormTemplate->name . '" already exists' . ($existingFormTemplateModel ? ' for model ' . $existingFormTemplateModel->name: ''));
        }

        $newFormName = $this->fixFormName($newFormName);
        $renameRules = $this->getFilesRenameRules($newFormName);
        try {
            foreach ($renameRules as $renameRule) {
                $oldContent = file_get_contents($renameRule['fromPath']);
                if (!$oldContent) {
                    throw new Exception('Unable to read file ' . $renameRule['fromPath']);
                }
                $newContent = preg_replace($renameRule['contentChanges']['pattern'], $renameRule['contentChanges']['replacement'], $oldContent);
                if (str_ends_with($renameRule['toPath'], '.php')) {
                    $newContent = preg_replace("/const FORM_NAME = '$this->name';/", "const FORM_NAME = '$newFormName';", $newContent);
                }
                file_put_contents($renameRule['toPath'], $newContent);
            }
            $newFormTemplate = new self();
            $newFormTemplate->name = $newFormName;
            $attributesToCopy = $this->attributes;
            unset($attributesToCopy['name']);
            $newFormTemplate->load($attributesToCopy, '');
            if ($newFormTemplate->validate()) {
                $newFormTemplate->save();
            } else {
                throw new Exception("Copy of template " . $this->name . " is not valid: " . print_r($newFormTemplate->errors, true));
            }
            // delete original files
            foreach ($renameRules as $renameRule) {
                unlink($renameRule['fromPath']);
            }
            // delete old template
            unlink($this->_filePath);
            return $newFormTemplate;
        } catch (\Exception $e) {
            // delete genereated files
            foreach ($renameRules as $renameRule) {
                if (file_exists($renameRule['toPath'])) {
                    unlink($renameRule['toPath']);
                }
            }
            throw $e;
        }
        return false;
    }

    /**
     * Returns if user may access this form
     * @return bool User may access this form
     */
    public function checkAccess($access = "view") {
        return \Yii::$app->user->can("form-" . $this->name . ":" . $access);
    }

}
