<?php

namespace app\models\base;

use Yii;
use app\models\ArchiveFile;
use app\models\CoreCore;
use app\models\ProjectHole;
use app\models\ProjectExpedition;
use app\models\ProjectProgram;

/**
 * This is the generated model base class for table "project_site".
 * DO NOT EDIT THIS CLASS MANUALLY!
 *
 * @property int $id #
 * @property int|null $expedition_id Expedition ID
 * @property string|null $combined_id Combined ID
 * @property string|null $site Site
 * @property string|null $site_name Name of site
 * @property string|null $site_name_alt Alternative Name/Code of Site
 * @property string|null $type_drilling_location Type of Drilling Location
 * @property string|null $description Description of site
 * @property string|null $country Country of Drill site
 * @property string|null $state State
 * @property string|null $county County of Drill Site
 * @property string|null $city City Nearby Drill Site
 * @property string|null $lithology Lithology
 * @property string|null $geological_age Geological Age
 * @property string|null $comment Additional Information
 *
 * @property ArchiveFile[] $archiveFiles
 * @property CoreCore[] $coreCores
 * @property ProjectHole[] $projectHoles
 * @property ProjectExpedition $expedition
 * @property ProjectExpedition $parent
 * @property ProjectProgram $program
 */
abstract class BaseProjectSite extends \app\models\core\Base
{
    /* [i.e columnName => [displayColumn, relationName],[..]] */
    const MANY_TO_MANY_COLUMNS = [];
    /* [[i.e columnName => displayColumn],[..]] */
    const ONE_TO_MANY_COLUMNS = [];

    const MODULE = 'Project';
    const SHORT_NAME = 'Site';

    const NAME_ATTRIBUTE = 'site';
    const PARENT_CLASSNAME = 'ProjectExpedition';
    const ANCESTORS = ['expedition'=>'ProjectExpedition', 'program'=>'ProjectProgram'];
    const DEFAULT_VALUES = [];


    public static function getFormFilters() {
        return [
            "expedition" => ["model" => "ProjectExpedition", "value" => "id", "text" => "exp_acronym", "ref" => "expedition_id"]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'project_site';
    }
    /**
    * {@inheritdoc}
    */
    public function behaviors()
    {
        return array_merge(parent::behaviors(), [
            ['class' => 'app\behaviors\template\UniqueCombinationAutoIncrementBehavior', 'searchFields' => ['expedition_id'], 'fieldToFill' => 'site', 'useAlphabet' => false],
        ]);
    }
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [['lithology', 'geological_age'],'\app\components\validators\MultipleValuesStringValidator'],
            [['expedition_id'], 'integer'],
            [['combined_id', 'site_name', 'site_name_alt', 'type_drilling_location', 'description', 'country', 'state', 'county', 'city', 'lithology', 'geological_age', 'comment'], 'string', 'max' => 255],
            [['site'], 'string', 'max' => 20],
            [['expedition_id', 'site'], 'unique', 'targetAttribute' => ['expedition_id', 'site']],
            [['expedition_id'], 'exist', 'skipOnError' => true, 'targetClass' => ProjectExpedition::className(), 'targetAttribute' => ['expedition_id' => 'id']],
            [['expedition_id'],'required'],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', '#'),
            'expedition_id' => Yii::t('app', 'Expedition ID'),
            'combined_id' => Yii::t('app', 'Combined ID'),
            'site' => Yii::t('app', 'Site'),
            'site_name' => Yii::t('app', 'Name of site'),
            'site_name_alt' => Yii::t('app', 'Alternative Name/Code of Site'),
            'type_drilling_location' => Yii::t('app', 'Type of Drilling Location'),
            'description' => Yii::t('app', 'Description of site'),
            'country' => Yii::t('app', 'Country of Drill site'),
            'state' => Yii::t('app', 'State'),
            'county' => Yii::t('app', 'County of Drill Site'),
            'city' => Yii::t('app', 'City Nearby Drill Site'),
            'lithology' => Yii::t('app', 'Lithology'),
            'geological_age' => Yii::t('app', 'Geological Age'),
            'comment' => Yii::t('app', 'Additional Information'),
        ];
    }



    /**
     * @return \yii\db\ActiveQuery
     */
    public function getArchiveFiles()
    {
        return $this->hasMany(ArchiveFile::className(), ['site_id' => 'id']);
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCoreCores()
    {
        return $this->hasMany(CoreCore::className(), ['site_id' => 'id']);
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProjectHoles()
    {
        return $this->hasMany(ProjectHole::className(), ['site_id' => 'id']);
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getExpedition()
    {
        return $this->hasOne(ProjectExpedition::className(), ['id' => 'expedition_id']);
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getParent()
    {
        return $this->getExpedition();
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProgram()
    {
        return $this->parent->parent;
    }

    public function fields()
    {
        $fields = [
            'expedition_id' => function($model) { return $model->injectUuid($this->expedition); },
            'program_id' => function($model) { return $model->injectUuid($this->program); },
            'lithology' => function($model) {
                if (!empty($model->lithology)) {
                    return explode(';', $model->lithology);
                } else {
                    return [];
                }
            },
            'geological_age' => function($model) {
                if (!empty($model->geological_age)) {
                    return explode(';', $model->geological_age);
                } else {
                    return [];
                }
            },
        ];
        if(\Yii::$app->controller->action->id !== "harvest") {
            $fields["archive_files"] = function ($model) {
                return [
                    'filter' => [
                        'expedition' => $model->expedition->id,
                        'site' => $model->id
                    ],
                    'files' => ArchiveFile::find()->andWhere([
                        'site_id' => $model->id
                    ])->all()
                ];
            };
        }
        return array_merge(parent::fields(), $fields);
    }

    public function load($data, $formName = null)
    {
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
    public function beforeDelete()
    {
        return parent::beforeDelete(); // TODO: Change the autogenerated stub
    }


}

