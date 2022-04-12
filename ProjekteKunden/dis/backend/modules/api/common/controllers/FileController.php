<?php
/**
 * Created by PhpStorm.
 * User: alali
 * Date: 19.03.2019
 * Time: 16:46
 */

namespace app\modules\api\common\controllers;


use Yii;
use app\modules\api\common\models\FilesUploadedFormModel;
use app\modules\api\common\models\FilesUploadNewFormModel;
use app\models\ArchiveFile;
use yii\data\ArrayDataProvider;
use yii\rest\Controller;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\AccessControl;


class FileController extends Controller
{
    public $serializer = [
        'class' => 'yii\rest\Serializer',
        'collectionEnvelope' => 'items',
    ];


    public function behaviors()
    {
        //TODO: Correct access validation
        //For now allow all
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
                            'actions' => ['index', 'meta-data', 'update-select-values'],
                            'roles' => ['viewer']
                        ],
                        [
                            'allow' => true,
                            'actions' => ['assign', 'delete', 'upload', 'unassign'],
                            'roles' => ['operator']
                        ],
                    ],
                ],
            ]);
    }



    public function actionIndex ()
    {
        $requestParams = Yii::$app->getRequest()->getBodyParams();
        if (empty($requestParams)) {
            $requestParams = Yii::$app->getRequest()->getQueryParams();
        }
        $uploadPath = '/';
        if (isset($requestParams['path']) && !str_contains($requestParams['path'], '..')) {
            $uploadPath = $requestParams['path'];
            unset($requestParams['path']);
        }

        return Yii::createObject([
            'class' => ArrayDataProvider::class,
            'allModels' => $this->getUploadedFiles($uploadPath),
            'pagination' => [
                'params' => $requestParams,
                'defaultPageSize' => 5,
                'pageSizeLimit' => [-1, 999999999999]

            ],
            'sort' => [
                'defaultOrder' => ['modified' => SORT_DESC, 'name' => SORT_DESC],
                'attributes' => ['mime', 'modified', 'name', 'size'],
                'params' => $requestParams,
            ],
        ]);
    }

    public function actionUpdateSelectValues($name, $value) {
        $model = new FilesUploadedFormModel();
        $model->load(["assignIds" => [$name => $value]], '');
        return $model->getSelectListValues($name, $value);
    }

    public function actionAssign () {
        $uploadedFormModel = new FilesUploadedFormModel();
        $uploadedFormModel->load(Yii::$app->getRequest()->getBodyParams(), '');

        if ($uploadedFormModel->validate()) {
            return $uploadedFormModel->assignFiles();
        } else {
            return $uploadedFormModel;
        }
    }

    public function actionDelete () {
        $uploadedFormModel = new FilesUploadedFormModel();
        $uploadedFormModel->load(Yii::$app->getRequest()->getBodyParams(), '');
        if ($uploadedFormModel->validate()) {
            $uploadedFormModel->deleteFiles();
            Yii::$app->getResponse()->setStatusCode(204);
        }
        else
            return $uploadedFormModel;
    }

    public function actionUpload () {
        $uploadNewFormModel = new FilesUploadNewFormModel();
        $post = Yii::$app->getRequest()->post();
        if (isset($post['FilesUploadNewFormModel'])) {
            $uploadNewFormModel->files = \yii\web\UploadedFile::getInstances($uploadNewFormModel, 'files');
            if ($uploadNewFormModel->files && $uploadNewFormModel->load($post) && $uploadNewFormModel->validate()) {
                $aFiles = $uploadNewFormModel->saveUploadedFiles();
                $file = $aFiles[0];
                return [
                    'name' => basename($file),
                    'size' => filesize($file),
                    'modified' => date('Y-m-d H:i:s', filemtime($file)),
                    'mime' => mime_content_type($file)
                ];
            }
        }
        return null;
    }

    public function actionMetaData () {
        $requestParams = Yii::$app->getRequest()->getBodyParams();
        if (empty($requestParams)) {
            $requestParams = Yii::$app->getRequest()->getQueryParams();
        }
        $filename = str_replace("..", "", $requestParams["filename"]);

        $file = ArchiveFile::getUploadPath()  . $filename;
        if (!file_exists($file)) {
            throw new NotFoundHttpException('File was not found');
        }
        $data = FilesUploadNewFormModel::getFileMetaData($file);
        $data['thumbnail'] = FilesUploadNewFormModel::getThumbNail($file);
        $data['filename'] = $requestParams["filename"];
        return $data;
    }

    public function actionUnassign ($id) {
        $file = ArchiveFile::findOne([
            "id" => $id
        ]);
        if (!$file) {
            throw new NotFoundHttpException('File was not found');
        }
        if (!$file->unAssign()) {
            throw new ServerErrorHttpException('Failed to un-assign unknown reason.');
        }

        return $file;
    }

    protected function getUploadedFiles($uploadPath = "/") {
        $files = [];
        clearstatcache();
        $formatter = \Yii::$app->formatter;
        $path = rtrim(ArchiveFile::getUploadPath() . '/' . trim($uploadPath, '/'), '/') . '/';

        if ($uploadPath !== "/") {
            $file = ['name' => '..', 'fullName' => $uploadPath . '..', 'size' => 0, 'modified' => date('Y-m-d H:i:s'), 'mime' => '', 'size_h' => ''];
            $files[] = $file;
        }

        foreach (glob($path . "{,.}*", GLOB_BRACE) AS $cFile) {
            $file = ['name' => basename($cFile), 'fullName' => $uploadPath . basename($cFile), 'size' => 0, 'modified' => date('Y-m-d H:i:s', filemtime($cFile)), 'mime' => '', 'size_h' => ''];
            if (!is_dir($cFile)) {
                $size = filesize($cFile);
                $file['size'] = $size;
                $file['size_h'] = $formatter->asShortSize($size, 1);
                $file['mime'] = mime_content_type($cFile);
                $files[] = $file;
            }
            else if (preg_match("/^[^\\.]/", basename($cFile))) {
                $file['modified'] = date('Y-m-d H:i:s');
                $files[] = $file;
            }
        }
        return $files;
    }


}
