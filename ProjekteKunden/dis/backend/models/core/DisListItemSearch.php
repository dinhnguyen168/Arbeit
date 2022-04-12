<?php

namespace app\models\core;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * This is the search  model class for "DisListItem".
 */
class DisListItemSearch extends DisListItem
{
    use \app\models\core\SearchModelTrait;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'list_id', 'display', 'remark', 'uri', 'sort'],'safe'],
        ];
    }

    protected function addQueryColumns($query) {
        $this->addQueryColumn($query, 'id', 'number');
        $this->addQueryColumn($query, 'list_id', 'number');
        $this->addQueryColumn($query, 'display', 'string');
        $this->addQueryColumn($query, 'remark', 'string');
        $this->addQueryColumn($query, 'uri', 'string');
        $this->addQueryColumn($query, 'sort', 'number');
    }

    protected function addQuerySearchAttributes($query) {
    }

}
