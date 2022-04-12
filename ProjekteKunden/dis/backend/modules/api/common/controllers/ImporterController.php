<?php
/**
 * Created by PhpStorm.
 * User: alali
 * Date: 19.03.2019
 * Time: 16:46
 */

namespace app\modules\api\common\controllers;


use Yii;
use yii\rest\Controller;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\AccessControl;
use yii\helpers\Inflector;


class ImporterController extends Controller
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
                            'roles' => ['operator']
                        ],
                    ],
                ],
            ]);
    }


    public function actionIndex() {
        $importers = [];
        $importersPath = \Yii::getAlias("@app/importers") . "/";
        foreach (glob($importersPath . "*Importer.php") AS $importFile) {
            if (preg_match ("/\\/([^\\/]+)Importer.php$/", $importFile, $matches)) {
                $importerName = $matches[1];
                $className = "\\app\\importers\\" . $importerName . "Importer";
                $importers[] = [
                    "class" => $importerName,
                    "title" => constant($className . "::TITLE"),
                    "extensionRegExp" => constant($className . "::FILE_EXTENSION_REGEXP"),
                    "modelNameRequired" => constant($className . "::MODEL_NAME_PARAMETER_REQUIRED")
                ];
            }
        }
        return $importers;
    }

}