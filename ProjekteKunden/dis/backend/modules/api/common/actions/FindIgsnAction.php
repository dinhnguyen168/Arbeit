<?php


namespace app\modules\api\common\actions;


use app\components\templates\ModelTemplate;
use yii\base\Action;

class FindIgsnAction extends Action
{
    /**
     * @param $igsn
     * @return array
     */
    public function run($igsn)
    {
        /*
         * 1. find models with igsn column
         * 2. build sql query
         * 3. get found data model forms
         */
        $modelsWithIgsn = $this->getModelsWithIgsn();
        $sqls = [];
        foreach ($modelsWithIgsn as $template) {
            $modelFullName = $template->fullName;
            $table = $template->table;
            $sql = "SELECT $table.id AS id, '$modelFullName' AS data_model, ";
            if (isset($template->columns["combined_id"]))
                $sql .= "combined_id";
            else if (isset($template->columns[strtolower($template->name)]))
                $sql .= strtolower($template->name);
            else if (isset($template->columns["id"]))
                $sql .= "''";
            $sqls[] = $sql . " AS info FROM $table WHERE igsn LIKE '$igsn'";
        }
        $command = \Yii::$app->db->createCommand(join(' UNION ', $sqls));
        $records = $command->queryAll();
        foreach ($records as $index => $record) {
            $modelClass = 'app\\models\\' . $record['data_model'];
            $model = $modelClass::findOne($record['id']);
            $filtersDefinitions = $modelClass::getFormFilters();
            $filter = [];
            foreach ($filtersDefinitions as $key => $filtersDefinition) {
                $filter[$key] = $model->$key->id;
            }
            $records[$index]['filter'] = $filter;
        }
        return $records;
    }

    /**
     * @return ModelTemplate[]
     */
    protected function getModelsWithIgsn ()
    {
        $models = [];
        foreach (\Yii::$app->templates->modelTemplates as $fullName => $modelTemplate) {
            if ($modelTemplate->hasColumn('igsn')) {
                $models[] = $modelTemplate;
            }
        }
        return $models;
    }
}
