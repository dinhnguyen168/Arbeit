<?php
/**
 * Created by PhpStorm.
 * User: alali
 * Date: 20.03.2019
 * Time: 11:22
 */

namespace app\modules\api\common\models;


use app\components\helpers\DbHelper;
use app\models\ArchiveFile;
use app\models\core\DisListItem;
use app\models\CoreCore;
use app\models\CoreSection;
use app\models\CurationSample;
use app\models\CurationSectionSplit;
use app\models\ProjectExpedition;
use app\models\ProjectHole;
use app\models\ProjectSite;
use app\models\SampleRequest;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

class FilesUploadedFormModel extends \yii\base\Model
{
    /**
     * @var array of file names in the upload folder
     */
    public $selectedFilenames = [];

    /**
     * @var string type of the assigned file
     */
    public $fileType = "";
    /**
     * @var string number of the core box (needed for core box type)
     */
    public $number = "";
    /**
     * @var string remarks about the assigned files
     */
    public $remarks = "";
    /**
     * @var string YYYY-mm-dd the upload date of the file.
     * If empty, the file modified date will be set as a default value
     */
    public $fileDate;
    /**
     * @var string name of the analyst who uploaded the file
     */
    public $analyst;

    public $assignIds;

    public $filenameTemplate = "";

    /**
     * @var bool deprecated
     */
    public $assignFiles = false;

    public function getSelectList ($field = 'expedition') {
        $formTemplate = \Yii::$app->templates->getFormTemplate('files');
        $filterDataModels = $formTemplate->filterDataModels;
        $parentValue = isset($filterDataModels[$field]['require']) && isset($this->assignIds[$filterDataModels[$field]['require']['value']]) ? $this->assignIds[$filterDataModels[$field]['require']['value']] : null;
        $class = '\\app\\models\\' . $filterDataModels[$field]['model'];
        $parentDbAttribute = isset($filterDataModels[$field]['require']) ? $filterDataModels[$field]['require']['as'] : '';
        $aValues = [];
        if ($parentValue > 0 || ($parentValue === null && !isset($filterDataModels[$field]['require']))) {
            $query = call_user_func([$class, "find"]);
            if ($parentValue > 0) $query->andWhere([$parentDbAttribute => $parentValue]);
            $query->select($filterDataModels[$field]['text'] . " AS text, id AS value");
            $command = $query->createCommand();
            $aValues = $command->queryAll();
        }
        return $aValues;
    }

    public function getSelectListValues ($startField = '') {
        $pdo = \Yii::$app->db->getMasterPdo();
        DbHelper::handlePdoPreparedStatements();
        $formTemplate = \Yii::$app->templates->getFormTemplate('files');
        $filterDataModels = $formTemplate->filterDataModels;
        $lists = [];
        $currentAttribute = $startField;
        if ($currentAttribute === '') {
            // return root list (with no parent relation)
            $rootLists = array_filter($filterDataModels, function ($v, $k) { return !isset($v['require']); }, ARRAY_FILTER_USE_BOTH);
            foreach ($rootLists as $key => $value) {
                $lists[$key] = $this->getSelectList($key);
            }

        }
        else {
            $childrenLists = array_filter($filterDataModels, function ($v, $k) use ($currentAttribute) { return isset($v['require']) && $v['require']['value'] == $currentAttribute; }, ARRAY_FILTER_USE_BOTH);
            foreach ($childrenLists as $key => $value) {
                $lists[$key] = $this->getSelectList($key);
                $subChildrenLists = array_filter($filterDataModels, function ($v, $k) use ($key) { return isset($v['require']) && $v['require']['value'] == $key; }, ARRAY_FILTER_USE_BOTH);
                while (sizeof($subChildrenLists) > 0) {
                    $keys = array_keys($subChildrenLists);
                    for ($i=sizeof($keys)-1; $i>=0; $i--) {
                        $key = $keys[$i];
                        $lists[$key] = [];
                        $subChildrenLists = array_merge($subChildrenLists, array_filter($filterDataModels, function ($v, $k) use ($key) { return isset($v['require']) && $v['require']['value'] == $key; }, ARRAY_FILTER_USE_BOTH));
                        unset($subChildrenLists[$key]);
                    }
                }
            }
        }
        return $lists;
    }

    /**
     * @inheritdoc
     */
    public function load ($data, $formName = null){
        $scope = $formName === null ? $this->formName() : $formName;
        if ($scope !== '' && isset($data[$scope])) {
            $data = $data[$scope];
        }

        if (isset($data['actionDelete']))
            $this->scenario = 'delete';
        else if (isset($data['actionSave']))
            $this->scenario = 'save';
        return parent::load ($data, '');
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels () {
        return [
            'fileType' => 'Type',
            'number' => 'Number (i.e. Core box number)',
            'remarks' => 'Remarks',
            'fileDate' => 'Upload Date'
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['selectedFilenames', 'assignIds', 'fileType', 'number', 'remarks', 'filenameTemplate'], 'safe', 'on' => ['save', 'delete']],
            [['analyst', 'fileDate'], 'required', 'on' => 'save'],
            [['fileType'], 'required', 'on' => 'save', 'when' => function($model) {
                // When type is set by the filename, fileType is not required
                return empty($model->filenameTemplate) || !preg_match("/<type>/", $model->filenameTemplate);
            }],
            [['fileDate'],'\app\components\validators\DateTimeValidator'],
            ['selectedFilenames', 'validateSelectedFilenames', 'skipOnEmpty' => false],
            ['number', 'required', 'on' => 'save', 'when' => function ($model) {
                return $model->scenario == 'save' && $model->fileType == 'BW' && empty($model->filenameTemplate);
            }],
            ['number', 'number', 'integerOnly' => true],
            ['assignIds', 'validateID']
        ];
    }


    /**
     * to validate the expedition, site, hole, core and site id's whether they belongs to a valid hierarchy
     * @param $attribute
     */
    public function validateID($attribute) {
        $formTemplate = \Yii::$app->templates->getFormTemplate('files');
        $filterDataModels = $formTemplate->filterDataModels;
        if (!is_array($this->{$attribute})) {
            $this->addError($attribute, $attribute . " must be an attribute");
        } else {
            foreach ($this->{$attribute} as $key => $value) {
                if (!array_key_exists($key, $filterDataModels)) {
                    $this->addError($key, "$key does not belong to assign ids");
                }
            }
        }
//        $value = $this->{$attribute};
//        if ($value > 0) {
//            if (!empty(static::$idHierarchy[$attribute]['parent'])) {
//                $parentAttribute = static::$idHierarchy[$attribute]['parent'];
//                if ($this->{$parentAttribute} == 0) {
//                    $cModelClass = ucfirst(preg_replace("/ID$/", "", $attribute));
//                    $cModelClass = (in_array($cModelClass, ["Core", "Section"]) ? "Core" : "Project") . $cModelClass;
//                    $cModelClass = "\\app\\models\\". $cModelClass;
//
//                    $oModel = call_user_func([$cModelClass, "find"])->where(['id' => $value])->one();
//
//                    if ($oModel) {
//                        $pA = preg_replace("/ID$/", "", $parentAttribute);
//                        $this->{$parentAttribute} = $oModel->{$pA}->id;
//                        $this->validateID($parentAttribute);
//                    }
//                    elseif ($this->scenario !== 'save')
//                        $this->addError($attribute, $attribute . "=" . $value . " not found.");
//                }
//            }
//        }

    }

    /**
     * validates the selected file names
     * @param $attribute
     */
    public function validateSelectedFilenames($attribute) {
        $value = $this->{$attribute};
        if (!is_array($value) || sizeof($value) == 0) {
            $this->addError($attribute, "Please select some files");
        }
    }

    /**
     * delete the selected file names from the upload folder
     */
    public function deleteFiles() {
        foreach ($this->selectedFilenames AS $filename) {
            $file = ArchiveFile::getUploadPath() . $filename;
            if (file_exists($file)) {
                unlink ($file);
            }
        }
    }

    /**
     * assign the selected file to the selected filter
     * @return ArchiveFile|bool|int
     * @throws \yii\web\NotFoundHttpException
     * @throws \yii\web\ServerErrorHttpException
     */
    public function assignFiles() {
        $assigned = 0;
        if (sizeof($this->selectedFilenames) == 0) {
            throw new \yii\web\NotFoundHttpException("Assign files: no files selected!");
        }

        $regexParts = [];
        $regex = null;
        if ($this->filenameTemplate > "") {
            $regex = preg_replace("/(^\\^|\\$$)/", "", $this->filenameTemplate);
            $regex = preg_replace("/\\./", '\\.', $regex);
            $regex = preg_replace("/<[^>]+>/", '([0-9a-zA-Z]+)', $regex);
            $regex = preg_replace("/\\*/", '.*?', $regex);
            $regex = '/^' . $regex . '$/';

            if (preg_match_all("/<[^>]+>/", $this->filenameTemplate, $regexParts)) {
                $regexParts = array_map(function($i) { return $i+1; }, array_flip(array_map(function($entity) {
                    return trim($entity, "<>");
                }, $regexParts[0])));
            }

            $fileTypes = array_map(function($item) {
                                return $item->display;
                            },
                            DisListItem::find()
                            ->joinWith('list')
                            ->where([
                                'list_name' => 'UPLOAD_FILE_TYPE'
                            ])
                            ->select("display")
                            ->all());
        }

        $matches = [];
        $path = ArchiveFile::getUploadPath();
        $errorMessages = [];
        foreach ($this->selectedFilenames AS $filename) {
            set_time_limit(100);
            try {
                if (file_exists($path . $filename)) {
                    $file = new ArchiveFile();
                    $file->original_filename = ltrim($filename, "/");

                    $formTemplate = \Yii::$app->templates->getFormTemplate('files');
                    $filterDataModels = $formTemplate->filterDataModels;
                    $filterDataModelsColumns = array_map(function ($value) {
                        return $value['ref'];
                    }, $filterDataModels);

                    if ($regex) {
                        $regexValues = [];
                        if (preg_match($regex, basename($filename), $matches)) {
                            foreach ($regexParts as $key => $index) {
                                if (isset($matches[$index])) {
                                    $regexValues[$key] = $matches[$index];
                                }
                            }
                        }

                        if (isset($regexValues["type"])) {
                            $fileType = $regexValues["type"];
                            if (in_array($fileType, $fileTypes))
                                $this->fileType = $fileType;
                            else
                                throw new \Exception("FileType '" . $fileType . "' does not exist.");
                        }

                        if (isset($regexValues["number"])) {
                            $this->number = $regexValues["number"];
                        }
                    }


                    if ($regex) {
                        foreach ($filterDataModels as $modelName => $modelData) {
                            if (isset($regexValues[$modelName])) {
                                $val = $regexValues[$modelName];

                                $modelClass = '\\app\\models\\' . $modelData["model"];
                                $query = $modelClass::find()->andWhere([$modelClass::NAME_ATTRIBUTE => $val]);
                                if (isset($modelData["require"])) {
                                    $parentModel = $modelData["require"]["value"];
                                    if ($this->assignIds[$parentModel]) {
                                        $query->andWhere([$modelData["require"]["as"] => $this->assignIds[$parentModel]]);
                                    } else
                                        throw new \Exception("Parent id for " . $modelClass::tableName() . "." . $modelClass::NAME_ATTRIBUTE . "='" . $val . "' ist not set.");
                                }
                                $model = $query->one();
                                if ($model) {
                                    $this->assignIds[$modelName] = $model->{$modelData["value"]};
                                } else
                                    throw new \Exception("Record for " . $modelClass::tableName() . "." . $modelClass::NAME_ATTRIBUTE . "='" . $val . "' could not be found.");

                            }
                        }
                    }

                    foreach ($filterDataModelsColumns as $k => $column) {
                        if (isset($this->assignIds[$k])) {
                            $file->{$column} = $this->assignIds[$k];
                        }
                    }


                    $file->number = $this->number;
                    $file->remarks = $this->remarks;
                    if (!empty($this->fileDate)) {
                        $file->upload_date = $this->fileDate;
                    }
                    $file->analyst = $this->analyst;

                    $result = $file->assignFileType($this->fileType);
                    if ($result === true) {
                        $assigned++;
                    } else {
                        // Validation error?
                        if (!$file->validate()) {
                            foreach ($file->getFirstErrors() as $field => $message) {
                                $errorMessages[] = ["field" => strtr($field, ["type" => "fileType"]), "message" => $message];
                            }
                        }
                    }
                }
            }
            catch (\Exception $e) {
                $errorMessages[] = ["field" => "File '" . basename($filename) . "'", "message" => $e->getMessage()];
            }
        }

        if (sizeof($errorMessages)) {
            \Yii::$app->response->statusCode = 422;
            if ($assigned) array_unshift($errorMessages, ["field" => "", "message" => $assigned . " files successfully assigned."]);
            return $errorMessages;
        }

        return $assigned;
    }

}
