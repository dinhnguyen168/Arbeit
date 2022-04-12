<?php

namespace app\models\base;

use Yii;
use app\models\ArchiveFile;
use app\models\CurationSampleRequest;
use app\models\GeologyLithoUnits;
use app\models\ProjectProgram;
use app\models\ProjectSite;
use app\models\SampleRequest;

/**
 * This is the generated model base class for table "project_expedition".
 * DO NOT EDIT THIS CLASS MANUALLY!
 *
 * @property int $id Id
 * @property int|null $program_id Program ID
 * @property string|null $expedition Expedition Code
 * @property string|null $exp_name Expedition Name
 * @property string|null $exp_name_alt Alternative Name of Expedition
 * @property string|null $exp_acronym Expedition Acronym
 * @property string|null $chief_scientists List of Chief Scientists
 * @property string|null $contact Contact Person
 * @property string|null $start_date Start of Expedition
 * @property string|null $end_date End of Expedition
 * @property string|null $keywords Keywords
 * @property string|null $objectives Objectives of the Expedition
 * @property string|null $rock_classification Rock Classification
 * @property string|null $geological_age Geological Age
 * @property string|null $funding_agency Funding Agencies 
 * @property string|null $comment Additional Information
 * @property string|null $country Country
 *
 * @property ArchiveFile[] $archiveFiles
 * @property CurationSampleRequest[] $curationSampleRequests
 * @property GeologyLithoUnits[] $geologyLithoUnits
 * @property ProjectProgram $program
 * @property ProjectSite[] $projectSites
 * @property SampleRequest[] $sampleRequests
 * @property ProjectProgram $parent
 */
abstract class BaseProjectExpedition extends \app\models\core\Base
{
    /* [i.e columnName => [displayColumn, relationName],[..]] */
    const MANY_TO_MANY_COLUMNS = [];
    /* [[i.e columnName => displayColumn],[..]] */
    const ONE_TO_MANY_COLUMNS = [];

    const MODULE = 'Project';
    const SHORT_NAME = 'Expedition';

    const NAME_ATTRIBUTE = 'expedition';
    const PARENT_CLASSNAME = 'ProjectProgram';
    const ANCESTORS = ['program'=>'ProjectProgram'];
    const DEFAULT_VALUES = [];


    public static function getFormFilters() {
        return [
            "program" => ["model" => "ProjectProgram", "value" => "id", "text" => "program_name", "ref" => "program_id"]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'project_expedition';
    }
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [['chief_scientists', 'country', 'rock_classification', 'geological_age', 'funding_agency', 'keywords'],'\app\components\validators\MultipleValuesStringValidator'],
            [['program_id'], 'integer'],
            [['start_date', 'end_date'], 'safe'],
            [['objectives'], 'string'],
            [['expedition', 'exp_acronym'], 'string', 'max' => 20],
            [['exp_name', 'exp_name_alt', 'chief_scientists', 'contact', 'keywords', 'rock_classification', 'geological_age', 'funding_agency', 'comment', 'country'], 'string', 'max' => 255],
            [['program_id', 'expedition'], 'unique', 'targetAttribute' => ['program_id', 'expedition']],
            [['program_id'], 'exist', 'skipOnError' => true, 'targetClass' => ProjectProgram::className(), 'targetAttribute' => ['program_id' => 'id']],
            [['program_id', 'exp_name', 'chief_scientists', 'contact', 'start_date'],'required'],
            [['start_date', 'end_date'],'\app\components\validators\DateValidator'],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'Id'),
            'program_id' => Yii::t('app', 'Program ID'),
            'expedition' => Yii::t('app', 'Expedition Code'),
            'exp_name' => Yii::t('app', 'Expedition Name'),
            'exp_name_alt' => Yii::t('app', 'Alternative Name of Expedition'),
            'exp_acronym' => Yii::t('app', 'Expedition Acronym'),
            'chief_scientists' => Yii::t('app', 'List of Chief Scientists'),
            'contact' => Yii::t('app', 'Contact Person'),
            'start_date' => Yii::t('app', 'Start of Expedition'),
            'end_date' => Yii::t('app', 'End of Expedition'),
            'keywords' => Yii::t('app', 'Keywords'),
            'objectives' => Yii::t('app', 'Objectives of the Expedition'),
            'rock_classification' => Yii::t('app', 'Rock Classification'),
            'geological_age' => Yii::t('app', 'Geological Age'),
            'funding_agency' => Yii::t('app', 'Funding Agencies '),
            'comment' => Yii::t('app', 'Additional Information'),
            'country' => Yii::t('app', 'Country'),
        ];
    }



    /**
     * @return \yii\db\ActiveQuery
     */
    public function getArchiveFiles()
    {
        return $this->hasMany(ArchiveFile::className(), ['expedition_id' => 'id']);
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCurationSampleRequests()
    {
        return $this->hasMany(CurationSampleRequest::className(), ['expedition_id' => 'id']);
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGeologyLithoUnits()
    {
        return $this->hasMany(GeologyLithoUnits::className(), ['expedition_id' => 'id']);
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProgram()
    {
        return $this->hasOne(ProjectProgram::className(), ['id' => 'program_id']);
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProjectSites()
    {
        return $this->hasMany(ProjectSite::className(), ['expedition_id' => 'id']);
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSampleRequests()
    {
        return $this->hasMany(SampleRequest::className(), ['expedition_id' => 'id']);
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getParent()
    {
        return $this->getProgram();
    }

    public function fields()
    {
        $fields = [
            'program_id' => function($model) { return $model->injectUuid($this->program); },
            'chief_scientists' => function($model) {
                if (!empty($model->chief_scientists)) {
                    return explode(';', $model->chief_scientists);
                } else {
                    return [];
                }
            },
            'country' => function($model) {
                if (!empty($model->country)) {
                    return explode(';', $model->country);
                } else {
                    return [];
                }
            },
            'rock_classification' => function($model) {
                if (!empty($model->rock_classification)) {
                    return explode(';', $model->rock_classification);
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
            'funding_agency' => function($model) {
                if (!empty($model->funding_agency)) {
                    return explode(';', $model->funding_agency);
                } else {
                    return [];
                }
            },
            'keywords' => function($model) {
                if (!empty($model->keywords)) {
                    return explode(';', $model->keywords);
                } else {
                    return [];
                }
            },
        ];
        if(\Yii::$app->controller->action->id !== "harvest") {
            $fields["archive_files"] = function ($model) {
                return [
                    'filter' => [
                        'program' => $model->program->id,
                        'expedition' => $model->id
                    ],
                    'files' => ArchiveFile::find()->andWhere([
                        'expedition_id' => $model->id
                    ])->all()
                ];
            };
        }
        return array_merge(parent::fields(), $fields);
    }

    public function load($data, $formName = null)
    {
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

    public function getIconUrl() {
        $filename = "img/logos/" . $this->exp_acronym . ".png";
        if (!file_exists(\Yii::getAlias("@app/../web/") . $filename)) {
            $filename = "img/logos/default.png";
        }
        return \Yii::getAlias("@web/") . $filename;
    }
}

