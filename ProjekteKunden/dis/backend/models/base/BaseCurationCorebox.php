<?php

namespace app\models\base;

use Yii;
use app\models\ProjectHole;
use app\models\CurationStorage;
use app\models\CurationSectionSplit;
use app\models\ProjectSite;
use app\models\ProjectExpedition;
use app\models\ProjectProgram;
use app\models\ArchiveFile;

/**
 * This is the generated model base class for table "curation_corebox".
 * DO NOT EDIT THIS CLASS MANUALLY!
 *
 * @property int $id ID
 * @property int|null $hole_id Hole
 * @property string|null $corebox Corebox
 * @property string|null $combined_id System Combined ID
 * @property string|null $comment Additional Information
 * @property int|null $storage_id Storage ID
 * @property string|null $corebox_combined_id Corebox Combined ID
 *
 * @property ProjectHole $hole
 * @property CurationStorage $storage
 * @property CurationSectionSplit[] $curationSectionSplits
 * @property ProjectHole $parent
 * @property ProjectSite $site
 * @property ProjectExpedition $expedition
 * @property ProjectProgram $program
 */
abstract class BaseCurationCorebox extends \app\models\core\Base
{
    /* [i.e columnName => [displayColumn, relationName],[..]] */
    const MANY_TO_MANY_COLUMNS = [];
    /* [[i.e columnName => displayColumn],[..]] */
    const ONE_TO_MANY_COLUMNS = [];

    const MODULE = 'Curation';
    const SHORT_NAME = 'Corebox';

    const NAME_ATTRIBUTE = 'corebox';
    const PARENT_CLASSNAME = 'ProjectHole';
    const ANCESTORS = ['hole'=>'ProjectHole', 'site'=>'ProjectSite', 'expedition'=>'ProjectExpedition', 'program'=>'ProjectProgram'];
    const DEFAULT_VALUES = [];


    public static function getFormFilters() {
        return [
            "expedition" => ["model" => "ProjectExpedition", "value" => "id", "text" => "exp_acronym", "ref" => "expedition_id"],
            "site" => ["model" => "ProjectSite", "value" => "id", "text" => "site", "ref" => "site_id", "require" => ["value" => "expedition", "as" => "expedition_id"]],
            "hole" => ["model" => "ProjectHole", "value" => "id", "text" => "hole", "ref" => "hole_id", "require" => ["value" => "site", "as" => "site_id"]]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'curation_corebox';
    }
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [['hole_id', 'storage_id'], 'integer'],
            [['corebox', 'combined_id', 'comment', 'corebox_combined_id'], 'string', 'max' => 255],
            [['hole_id'], 'exist', 'skipOnError' => true, 'targetClass' => ProjectHole::className(), 'targetAttribute' => ['hole_id' => 'id']],
            [['storage_id'], 'exist', 'skipOnError' => true, 'targetClass' => CurationStorage::className(), 'targetAttribute' => ['storage_id' => 'id']],
            [['hole_id', 'corebox'],'required'],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'hole_id' => Yii::t('app', 'Hole'),
            'corebox' => Yii::t('app', 'Corebox'),
            'combined_id' => Yii::t('app', 'System Combined ID'),
            'comment' => Yii::t('app', 'Additional Information'),
            'storage_id' => Yii::t('app', 'Storage ID'),
            'corebox_combined_id' => Yii::t('app', 'Corebox Combined ID'),
            'storage_combined_id' => Yii::t('app', 'Storage'),
            'contained_section_splits' => Yii::t('app', 'Section Splits in Corebox'),
        ];
    }


    public function getStorage_combined_id()
    {
        return ($this->storage ? $this->storage->combined_id : "");
    }
    public function getContained_section_splits()
    {
        return $this->getContainedSectionSplits();
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getHole()
    {
        return $this->hasOne(ProjectHole::className(), ['id' => 'hole_id']);
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStorage()
    {
        return $this->hasOne(CurationStorage::className(), ['id' => 'storage_id']);
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCurationSectionSplits()
    {
        return $this->hasMany(CurationSectionSplit::className(), ['corebox_id' => 'id']);
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getParent()
    {
        return $this->getHole();
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSite()
    {
        return $this->parent->parent;
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getExpedition()
    {
        return $this->parent->parent->parent;
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProgram()
    {
        return $this->parent->parent->parent->parent;
    }

    public function fields()
    {
        $fields = [
            'hole_id' => function($model) { return $model->injectUuid($this->hole); },
            'site_id' => function($model) { return $model->injectUuid($this->site); },
            'expedition_id' => function($model) { return $model->injectUuid($this->expedition); },
            'program_id' => function($model) { return $model->injectUuid($this->program); },
            'storage_combined_id' => function($model) { return ($this->storage ? $this->storage->combined_id : ""); },
            'contained_section_splits' => function($model) { return $this->getContainedSectionSplits(); },
        ];
        if(\Yii::$app->controller->action->id !== "harvest") {
            $fields["archive_files"] = function ($model) {
                return [
                    'filter' => [
                        'expedition' => $model->expedition->id,
                        'site' => $model->site->id,
                        'hole' => $model->hole->id,
                        'corebox' => $model->id
                    ]                ];
            };
        }
        return array_merge(parent::fields(), $fields);
    }

    public function load($data, $formName = null)
    {
        if(isset($data['hole_id']) && is_array($data['hole_id'])) {
            $data['hole_id'] = $data['hole_id']['id'];
        }
        if(isset($data['site_id']) && is_array($data['site_id'])) {
            $data['site_id'] = $data['site_id']['id'];
        }
        if(isset($data['expedition_id']) && is_array($data['expedition_id'])) {
            $data['expedition_id'] = $data['expedition_id']['id'];
        }
        if(isset($data['program_id']) && is_array($data['program_id'])) {
            $data['program_id'] = $data['program_id']['id'];
        }
        return parent::load($data, $formName);
    }


    /**
     * {@inheritdoc}
     */
    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        $this->corebox_combined_id = $this->parent->combined_id . '_CB' . $this->corebox;
        return true;
    }


    /**
    * {@inheritdoc}
    */
    public function beforeDelete()
    {
        return parent::beforeDelete(); // TODO: Change the autogenerated stub
    }

    /**
     * This method is inserted into the generated class file "backend/models/base/BaseCurationCorebox".
     *
     * Get the section splits contained in this corebox
     * @return string Multiline-String of the combined ids of the contained section splits
     */
    protected function getContainedSectionSplits () {
        $splits = [];
        if (class_exists("\\app\\models\\CurationSectionSplit")) {
            foreach (\app\models\CurationSectionSplit::find()->where(["corebox_id" => $this->id])->all() as $split) {
                $splits[] = $split->combined_id;
            }
        }
        return $splits;
    }
}

