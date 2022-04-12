<?php
/**
 * Created by PhpStorm.
 * User: alali
 * Date: 24.09.2018
 * Time: 10:17
 */

namespace app\controllers;

use yii\filters\AccessControl;
use yii\web\NotFoundHttpException;
use Yii;
use app\models\ArchiveFile;

/**
 * Class FilesController
 * @package app\controllers
 */
class FilesController extends \yii\web\Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return array_merge(
            parent::behaviors(),
            [
                'access' => [
                    'class' => AccessControl::class,
                    'rules' => [
                        [
                            'allow' => true,
                            'roles' => ['@']
                        ]
                    ]
                ]
            ]
        );
    }

    /**
     * Show the preview version of an archive file
     * @param $id file pk
     * @return \yii\console\Response|\yii\web\Response the preview file
     * @throws NotFoundHttpException
     */
    public function actionView($id) {
        $archiveFile = ArchiveFile::find()->andWhere(["id" => $id])->one();
        if ($archiveFile == null) {
            throw new NotFoundHttpException("File " . $id . " not found.");
        }

        $file = $archiveFile->getPreviewFile();
        if (!$file) {
            throw new NotFoundHttpException("Preview file " . $id . " not found.");
        }

        //TODO: check access rights??
        Yii::$app->response->headers->set('Content-Disposition', 'inline');
        $result = Yii::$app->response->sendFile($file, basename($file));
        return $result;
    }

    /**
     * Download the original version of an archive file.
     * @param $id file pk
     * @return \yii\console\Response|\yii\web\Response the original file as an attachment
     * @throws NotFoundHttpException
     */
    public function actionViewOriginal($id) {
        $archiveFile = ArchiveFile::find()->andWhere(["id" => $id])->one();
        if ($archiveFile == null) {
            throw new NotFoundHttpException("File " . $id . " not found.");
        }

        $file = $archiveFile->getOriginalFile();
        if (!$file) {
            throw new NotFoundHttpException("Original file " . $id . " not found.");
        }

        //TODO: check access rights??
        Yii::$app->response->headers->set('Content-Disposition', 'attachment; filename="' . $archiveFile->filename . '"');
        $result = Yii::$app->response->sendFile($file, basename($file));
        return $result;
    }

    /**
     * Show the converted version of an archive file, if that exists.
     * @param $id file pk
     * @return \yii\console\Response|\yii\web\Response the original file as an attachment
     * @throws NotFoundHttpException
     */
    public function actionViewConverted($id) {
        $archiveFile = ArchiveFile::find()->andWhere(["id" => $id])->one();
        if ($archiveFile == null) {
            throw new NotFoundHttpException("File " . $id . " not found.");
        }

        $file = $archiveFile->getConvertedFile();
        if (!$file) {
            throw new NotFoundHttpException("Converted file " . $id . " not found.");
        }

        //TODO: check access rights??
        Yii::$app->response->headers->set('Content-Disposition', 'inline');
        $result = Yii::$app->response->sendFile($file, basename($file));
        return $result;
    }

}
