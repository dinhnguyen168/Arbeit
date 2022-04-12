<?php
/**
 * Created by PhpStorm.
 * User: alali
 * Date: 16.10.2018
 * Time: 12:24
 */

namespace app\modules\api\common\actions;

use app\components\helpers\DbHelper;
use app\rbac\LimitedAccessRule;
use Yii;
use yii\base\Model;
use yii\db\ActiveRecord;
use yii\helpers\Inflector;
use yii\web\BadRequestHttpException;
use function GuzzleHttp\json_decode;

/**
 * action to return the available filter lists of a filter components in a form
 * Class FilterListsAction
 * @package app\modules\api\common\actions
 */
class FilterListsAction extends \yii\rest\Action
{
    /**
     * @return array
     * @throws \yii\base\InvalidConfigException
     * @throws BadRequestHttpException
     */
    public function run()
    {
        if ($this->checkAccess) {
            call_user_func($this->checkAccess, $this->id);
        }

        $queryParams = \Yii::$app->request->queryParams;
        $dataModels = $queryParams['models'];

        $filterDataModels = call_user_func([$this->modelClass, 'getFormFilters']);
        $filterLists = [];

        $pdo = \Yii::$app->db->getMasterPdo();
        DbHelper::handlePdoPreparedStatements();
        foreach ($dataModels as $dataModel) {
            $dataModel = json_decode($dataModel);
            $filterDataModel = $filterDataModels[$dataModel->model];
            /* @var $modelClass ActiveRecord */
            $modelClass = '\\' . Yii::$app->params['modelsClassesNs'] . '\\' . $filterDataModel['model'];
            if (class_exists($modelClass)) {
                $requir = '';
                $whereRequireValue = '';
                if($dataModel->require != null && (isset($dataModel->require->value) && $dataModel->require->value != null)) {
                    $requir = ' ,' . $dataModel->require->as . ' AS ' .$dataModel->require->as . '';
                    $dependentModelvalue = $dataModel->require->value;
                    $whereRequireValue = ' WHERE '. $dataModel->require->as . ' = ' . $dependentModelvalue. '';
                }
                $sqlCommand = \Yii::$app->db->createCommand('SELECT '.$filterDataModel['value']. ' AS value, '.$filterDataModel['text']. ' AS text'. $requir .' FROM '.lcfirst(Inflector::camel2id($filterDataModel['model'], "_")) . $whereRequireValue. '');
                $filterLists[$dataModel->model] = $sqlCommand->queryAll();
            }
        }

        return $filterLists;
    }
}