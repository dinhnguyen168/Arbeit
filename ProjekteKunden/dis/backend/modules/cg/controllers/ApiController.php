<?php
/**
 * Created by PhpStorm.
 * User: alali
 * Date: 07.01.2019
 * Time: 11:14
 */

namespace app\modules\cg\controllers;

use app\behaviors\template\TemplateManagerBehaviorInterface;
use app\components\templates\actions\CreateAction;
use app\components\templates\actions\DeleteAction;
use app\components\templates\actions\SummaryAction;
use app\components\templates\actions\UpdateAction;
use app\components\templates\actions\ViewAction;
use app\components\templates\BaseTemplate;
use app\components\templates\FormTemplate;
use app\components\templates\ModelTemplate;
use app\components\templates\ModelTemplateColumn;
use app\migrations\Migration;
use Exception;
use Yii;
use yii\filters\AccessControl;
use yii\filters\auth\HttpBearerAuth;
use yii\gii\CodeFile;
use yii\helpers\Inflector;
use yii\helpers\Json;
use yii\rest\Controller;
use app\modules\cg\generators\DISForm\Generator as FormGenerator;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;
use ZipArchive;

class ApiController extends Controller
{
    public function behaviors()
    {
        return array_merge(
            parent::behaviors(),
            [
                'authenticator' => [
                    'class' => HttpBearerAuth::class
                ],
                'access' => [
                    'class' => AccessControl::class,
                    'rules' => [
                        [
                            'allow' => true,
                            'actions' => ['summary', 'get-model-template', 'get-form-template'],
                            'roles' => ['@']
                        ],
                        [
                            'allow' => true,
                            'roles' => ['developer']
                        ]
                    ]
                ]
            ]
        );
    }

    public function actions()
    {
        return array_merge(parent::actions(), [
            'create-model' => [
                'class' => CreateAction::class,
                'templateClass' => ModelTemplate::class
            ],
            'update-model' => [
                'class' => UpdateAction::class,
                'templateClass' => ModelTemplate::class
            ],
            'get-model-template' => [
                'class' => ViewAction::class,
                'templateClass' => ModelTemplate::class
            ],
            'delete-model' => [
                'class' => DeleteAction::class,
                'templateClass' => ModelTemplate::class
            ],
            'create-form' => [
                'class' => CreateAction::class,
                'templateClass' => FormTemplate::class
            ],
            'update-form' => [
                'class' => UpdateAction::class,
                'templateClass' => FormTemplate::class
            ],
            'get-form-template' => [
                'class' => ViewAction::class,
                'templateClass' => FormTemplate::class
            ],
            'delete-form' => [
                'class' => DeleteAction::class,
                'templateClass' => FormTemplate::class
            ],
            'summary' => [
                'class' => SummaryAction::class
            ]
        ]);
    }
    public function actionBehaviors () {
        $behaviorsDirectory = realpath(Yii::getAlias("@app/behaviors/template")) . "/";
        $behaviorsInfo = [];
        foreach (glob($behaviorsDirectory . "*Behavior.php") as $behaviorFile) {
            /* @var $className TemplateManagerBehaviorInterface */
            $className = "app\\behaviors\\template\\" . basename($behaviorFile, ".php");;
            $behaviorsInfo[] = [
                'behaviorClass' => $className,
                'name' => $className::getName(),
                'parameters' => $className::getParameters()
            ];
        }
        return $behaviorsInfo;
    }

    public function actionGetFormTemplateSeed ($modelName) {
        /* @var $model ModelTemplate */
        $parentModel = null;
        $model = Yii::$app->templates->getModelTemplate($modelName);
        if ($model->parentModel > "") {
            $parentModel = Yii::$app->templates->getModelTemplate($model->parentModel);
        }
        $templateSeed = [
            'availableFieldTemplates' => [],
            'initialTemplate' => [
                'name' => '',
                'dataModel' => $model->fullName,
                'fields' => []
            ]
        ];
        /* @var $column ModelTemplateColumn */
        $order = 0;
        foreach ($model->columns as $key => $column) {
            $parentReferenceColumn = empty($parentModel) ? -1 : Inflector::camel2id(trim(preg_replace('/[0-9]+/', '-$0-', $parentModel->name)), '_') . '_id';
            if ($column->name !== 'id' && ($parentModel == null || $column->name != $parentReferenceColumn)) {
                $order++;
                $fieldObject = [
                    "name" => $column->name,
                    "label" => $column->label,
                    "description" => $column->description,
                    "validators" => [],
                    "formInput" => [
                        "type" => 'text',
                        "disabled" => false,
                        "calculate" => $column->calculate
                    ]
                ];
                // has required validator?
                if ($column->required) {
                    $fieldObject['validators'][] = [ 'type' => 'required'];
                }
                // set input type
                switch ($column->type) {
                    case 'boolean':
                        $fieldObject['formInput']['type'] = 'switch';
                        break;
                    case 'dateTime':
                        $fieldObject['formInput']['type'] = 'datetime';
                        break;
                    case 'date':
                        $fieldObject['formInput']['type'] = 'date';
                        break;
                    case 'time':
                        $fieldObject['formInput']['type'] = 'time';
                        break;
                    case 'double':
                    case 'integer':
                        $fieldObject['formInput']['type'] = 'text';
                        $fieldObject['validators'][] = [ 'type' => 'number' ];
                        break;
                    case 'string':
                        $fieldObject['formInput']['type'] = 'text';
                        $fieldObject['validators'][] = [ 'type' => 'string' ];
                        break;
                }
                // check for list inputs
                if (!empty($column->selectListName)) {
                    $fieldObject['formInput']['type'] = 'select';
                    $fieldObject['formInput']['selectSource'] = [
                        'type' => 'list',
                        'listName' => $column->selectListName,
                        'textField' => 'remark',
                        'valueField' => 'display'
                    ];
                }
                $fieldObject['group'] = '-group1';
                $fieldObject['order'] = $order;

                $templateSeed['availableFieldTemplates'][$fieldObject['name']] = $fieldObject;
                if ($column->required) {
                    $templateSeed['initialTemplate']['fields'][] = $fieldObject;
                }
            }
        }

        return $templateSeed;
    }

    /**
     * @return BaseTemplate|ModelTemplate|null
     * @throws ServerErrorHttpException
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\base\NotSupportedException
     * @throws \yii\db\Exception
     */
    public function actionCreateModelTable () {
        $modelName = Yii::$app->request->post('model');
        $model = ModelTemplate::find($modelName);
        /* @var $model ModelTemplate */
        if ($model) {
            $warnings = [];
            $migration = new Migration();
            $transaction = $migration->db->beginTransaction();
            try {
                $model->validateDatabaseStructure($warnings, $migration, true);
                $transaction->commit();
                if (sizeof($warnings)) {
                    \Yii::warning("The following modifications of table ". $model->table . " have been corrected:\n" . implode("\n", $warnings));
                }
            } catch (Exception $e) {
                $transaction->rollBack();
                $model->restoreBackupVersion();
                throw $e;
            }
        }
        $model->deleteBackupVersion();
        return $model;
    }

    public function actionSaveModelForm () {
        $generate = Yii::$app->request->post('generate');
        $template = Yii::$app->request->post('json');
        $fileName = $template['formName'] . '-form.json';
        $filePath = Yii::getAlias("@app/dis_templates/forms") . "/" . $fileName;
        $oldTemplate = null;
        if (file_exists($filePath)) {
            $oldTemplate = Json::decode(file_get_contents($filePath));
            $template['createdAt'] = $oldTemplate['createdAt'];
        } else {
            $template['createdAt'] = time();
        }
        $template['generatedAt'] = time();
        $template['modifiedAt'] = time();
        if (file_put_contents($filePath, Json::encode($template, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT))) {
            // call gii generate
            /**
             * @var $formGenerator FormGenerator
             */
            $formGenerator = new FormGenerator();
            $formGenerator->json = Json::encode($template, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $files = $formGenerator->generate();
            $hasError = false;
            $lines = [];
            if ($generate) {
                foreach ($files as $file) {
                    $relativePath = $file->getRelativePath();
                    if ($file->operation !== CodeFile::OP_SKIP) {
                        $error = $file->save();
                        if (is_string($error)) {
                            $hasError = true;
                            $lines[] = "$relativePath \n <span class=\"text-error\">$error</span>";
                        } else {
                            $lines[] = $file->operation === CodeFile::OP_CREATE ? " generated $relativePath" : " overwrote $relativePath";
                        }
                    } else {
                        $lines[] = "   skipped $relativePath";
                    }
                }
            }
            $params['answer'] = implode('\n', $lines);
            $params['hasError'] = $hasError;
            $params['files'] = $files;
            return $params;
        }
        return [
            "hasError" => true,
            "files" => [],
            "answer" => "Cannot save template file"
        ];
    }

    /**
     * @param $id
     * @return array|\yii\gii\Generator
     * @throws NotFoundHttpException
     */
    public function actionView($id)
    {
        $generator = $this->loadGenerator($id);
        $params = [];

        $generate = Yii::$app->request->post('generate');
        $answers = Yii::$app->request->post('answers');

        if ($generator->validate()) {
            $files = $generator->generate();
            if ($generate == true && !empty($answers)) {
                $params['hasError'] = !$generator->save($files, (array) $answers, $results);
                $params['results'] = $results;
            } else {
                $params['files'] = $files;
                $params['answers'] = $answers;
            }
        } else {
            return $generator;
        }

        return $params;
    }

    /**
     * @param $id
     * @param $file
     * @return string
     * @throws NotFoundHttpException
     */
    public function actionPreview($id, $file)
    {
        $generator = $this->loadGenerator($id);
        if ($generator->validate()) {
            foreach ($generator->generate() as $f) {
                if ($f->id === $file) {
                    $content = $f->preview();
                    if ($content !== false) {
                        return  '<div class="content">' . $content . '</div>';
                    }
                    return '<div class="error">Preview is not available for this file type.</div>';
                }
            }
        }
        throw new NotFoundHttpException("Code file not found: $file");
    }

    /**
     * @param $id
     * @param $file
     * @return bool|string
     * @throws NotFoundHttpException
     */
    public function actionDiff($id, $file)
    {
        $generator = $this->loadGenerator($id);
        if ($generator->validate()) {
            foreach ($generator->generate() as $f) {
                if ($f->id === $file) {
                    $diff = $f->diff();
                    if (empty($diff)) {
                        return 'Identical';
                    } else {
                        return $diff;
                    }
                }
            }
        }
        throw new NotFoundHttpException("Code file not found: $file");
    }

    /**
     * @param $type
     * @param $oldName
     * @param $newName
     * @return FormTemplate|ModelTemplate
     * @throws ServerErrorHttpException
     * @throws \yii\base\InvalidConfigException
     */
    public function actionDuplicate($type, $oldName, $newName) {
        if ($type === 'model') {
            $model = Yii::$app->templates->getModelTemplate($oldName);
            $newModel = new ModelTemplate([
                'scenario' => BaseTemplate::SCENARIO_CREATE
            ]);
            $module = $model->module;
            $newModel->module = $module;
            $newModel->name = substr($newName, strlen($module));
            $newModel->table = Inflector::camel2id($newName, '_');
            $copiedAttributes = $model->toArray();
            unset($copiedAttributes['module']);
            unset($copiedAttributes['name']);
            unset($copiedAttributes['table']);

            // rename FKs
            $newFKs = [];
            foreach ($copiedAttributes['relations'] as $key => $value) {
                $newFKName = preg_replace("/(^|_)" . $model->table . "(_|$)/", "$1" . $newModel->table . "$2", $key);
                $newFKs[$newFKName] = array_merge($value, [ "name" => $newFKName ]);
            }
            $copiedAttributes['relations'] = $newFKs;

            $newModel->load($copiedAttributes, '');
            $newModel->save();
            return $newModel;
        }
        if ($type === 'form') {
            $form = Yii::$app->templates->getFormTemplate($oldName);
            $newForm = new FormTemplate([
                'scenario' => BaseTemplate::SCENARIO_CREATE
            ]);
            $newForm->name = $newName;
            $copiedAttributes = $form->toArray();
            unset($copiedAttributes['name']);
            $newForm->setAvailableSubForms(array_keys($form->subForms));
            unset($copiedAttributes['subForms']);
            $newForm->setAvailableSupForms(array_keys($form->supForms));
            unset($copiedAttributes['supForms']);
            $newForm->load($copiedAttributes, '');
            $newForm->save();
            return $newForm;
        }
        throw new ServerErrorHttpException("type '$type' is not supported");
    }

    public function actionRenameForm($name, $newName) {
        $formTemplate = Yii::$app->templates->getFormTemplate($name);
        if (!$formTemplate) {
            throw new NotFoundHttpException("'$name' form template was not found");
        }
        return $formTemplate->rename($newName);
    }
    /**
     * @param $type
     * @param $name
     * @return \yii\console\Response|\yii\web\Response
     * @throws ServerErrorHttpException
     */
    public function actionDownload($type, $name) {
        if ($type === 'model') {
            $model = Yii::$app->templates->getModelTemplate($name);
            $zipPath = $model->getDownloadZip();
            if (file_exists($zipPath)){
                return Yii::$app->response->sendFile($zipPath);
            } else {
                throw new ServerErrorHttpException('Zip file was not generated.');
            }
        }
        if ($type === 'form') {
            $form = Yii::$app->templates->getFormTemplate($name);
            $zipPath = $form->getDownloadZip();
            if (file_exists($zipPath)){
                return Yii::$app->response->sendFile($zipPath);
            } else {
                throw new ServerErrorHttpException('Zip file was not generated.');
            }
        }
        throw new ServerErrorHttpException("type '$type' is not supported");
    }

    /**
     * @param $type
     * @param $filename
     * @return FormTemplate|ModelTemplate
     * @throws ServerErrorHttpException
     */
    public function actionVerifyUploadedTemplate($type, $filename) {
        $templatePath = Yii::getAlias('@app') . '/data/upload/' . $filename;
        if ($type === 'model') {
            try {
                return $this->createUploadedModelTemplate(file_get_contents($templatePath));
            } catch (Exception $e) {
                throw new ServerErrorHttpException(sprintf("Can't create table: %s. Check filesystem permissions and make sure that you're uploading a valid Model template", $e->getMessage()));
            } finally {
                unlink($templatePath);
            }
        }
        if ($type === 'form') {

            try {
                return $this->createUploadedFormTemplate(file_get_contents($templatePath));
            } catch (Exception $e) {
                $message = $e->getMessage();
                throw new ServerErrorHttpException(sprintf("Can't create form: %s." . (preg_match("/already exists/", $message) ? "" : " Make sure that you're uploading a valid Form template."), $message));
            } finally {
                unlink($templatePath);
            }
        }
        throw new ServerErrorHttpException("type '$type' is not supported");
    }

    /**
     * @param $templateString
     * @return ModelTemplate
     * @throws \yii\base\InvalidConfigException
     */
    protected function createUploadedModelTemplate ($templateString) {
        $model = new ModelTemplate([
            'scenario' => BaseTemplate::SCENARIO_CREATE
        ]);
        $model->load(Json::decode($templateString), '');
        $model->save();
        return $model;
    }

    /**
     * @param $templateString
     * @return FormTemplate
     * @throws \yii\base\InvalidConfigException
     */
    protected function createUploadedFormTemplate ($templateString) {
        $form = new FormTemplate([
            'scenario' => BaseTemplate::SCENARIO_CREATE
        ]);
        $templateAttributes = Json::decode($templateString);
        $form->setAvailableSubForms(array_keys($templateAttributes['subForms']));
        unset($templateAttributes['subForms']);
        $form->setAvailableSupForms(array_keys($templateAttributes['supForms']));
        unset($templateAttributes['supForms']);
        $form->load($templateAttributes, '');
        $form->save();
        return $form;
    }

    /**
     * @param $filename
     * @return FormTemplate|ModelTemplate
     * @throws ServerErrorHttpException
     * @throws \yii\base\Exception
     */
    public function actionVerifyUploadedZip($filename) {
        $zipPath = Yii::getAlias('@app') . '/data/upload/' . $filename;
        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new \yii\base\Exception("couldn't open the zipped file");
        }
        try {
            $zippedFiles = $this->getZippedFilesList($zip);
            $type = $this->guessTemplateType($zippedFiles, $templateFile);
            $templateModel = ($type === 'model') ? $this->createUploadedModelTemplate($zip->getFromName($templateFile)) : $this->createUploadedFormTemplate($zip->getFromName($templateFile));
            if (!$templateModel->hasErrors()) {
                $this->extractZippedFiles($zip);
            }
            return $templateModel;
        } catch (Exception $e) {
            throw new ServerErrorHttpException("make sure that you're uploading a valid zip file");
        } finally {
            $zip->close();
            unlink($zipPath);
        }
    }

    /**
     * @param ZipArchive $zip
     * @return array
     */
    protected function getZippedFilesList (ZipArchive $zip) {
        $zippedFiles = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $zippedFiles[] = $zip->getNameIndex($i);
        }
        return $zippedFiles;
    }

    /**
     * @param array $zippedFiles
     * @param $templateFile
     * @return string
     * @throws \yii\base\Exception
     */
    protected function guessTemplateType (array $zippedFiles, &$templateFile) {
        foreach ($zippedFiles as $file) {
            if (preg_match('/backend\/dis_templates\/(.+)\/.+\.json/', $file, $output_array)) {
                $templateFile = $output_array[0];
                if ($output_array[1] === 'models') {
                    return 'model';
                }
                if ($output_array[1] === 'forms') {
                    return 'form';
                }
            }
        }
        throw new \yii\base\Exception("couldn't guess the template type.");
    }

    protected function extractZippedFiles (ZipArchive $zip) {
        for ($i = 0; $i < $zip->numFiles; $i++) {
            if (!preg_match('/backend\/dis_templates\/(.+)\/.+\.json/', $zip->getNameIndex($i), $output_array)) {
                file_put_contents(Yii::getAlias("@app") . "/../" . $zip->getNameIndex($i), $zip->getFromIndex($i));
            }
        }
    }

    /**
     * Loads the generator with the specified ID.
     * @param string $id the ID of the generator to be loaded.
     * @return \yii\gii\Generator the loaded generator
     * @throws NotFoundHttpException
     */
    protected function loadGenerator($id)
    {
        if (isset($this->module->generators[$id])) {
            $generator = $this->module->generators[$id];
            $generator->loadStickyAttributes();
            $generator->load(Yii::$app->request->post());

            return $generator;
        }
        throw new NotFoundHttpException("Code generator not found: $id");
    }
}
