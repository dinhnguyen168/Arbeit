<?php

namespace app\models\base;

use Yii;
use app\models\ArchiveFile;
use app\models\CurationSectionSplit;
use app\models\CoreSection;
use app\models\CoreCore;
use app\models\ProjectHole;
use app\models\ProjectSite;
use app\models\ProjectExpedition;
use app\models\ProjectProgram;

/**
 * This is the generated model base class for table "curation_sample".
 * DO NOT EDIT THIS CLASS MANUALLY!
 *
 * @property int $id ID
 * @property int|null $section_split_id SectionSplit
 * @property string|null $sample_request_id Sample Request ID
 * @property string|null $sample_combined_id Sample Combined ID
 * @property string|null $igsn IGSN
 * @property string|null $sample_date Sample Date
 * @property float|null $top Sample Top [cm]
 * @property float|null $sample_length Sample Length [cm]
 * @property int|null $split_fraction_taken Fraction of Section Split [%]
 * @property float|null $sample_size Sample Size
 * @property string|null $sample_size_unit Unit of Sample Size
 * @property string|null $sample_material Sample Material
 * @property string|null $curator Curator
 * @property string|null $comment Additional Information
 * @property string|null $purpose Purpose/Usage
 * @property string|null $combined_id System Combined ID
 *
 * @property ArchiveFile[] $archiveFiles
 * @property CurationSectionSplit $sectionSplit
 * @property CurationSectionSplit $parent
 * @property CoreSection $section
 * @property CoreCore $core
 * @property ProjectHole $hole
 * @property ProjectSite $site
 * @property ProjectExpedition $expedition
 * @property ProjectProgram $program
 */
abstract class BaseCurationSample extends \app\models\core\Base
{
    /* [i.e columnName => [displayColumn, relationName],[..]] */
    const MANY_TO_MANY_COLUMNS = [];
    /* [[i.e columnName => displayColumn],[..]] */
    const ONE_TO_MANY_COLUMNS = [];

    const MODULE = 'Curation';
    const SHORT_NAME = 'Sample';

    const NAME_ATTRIBUTE = 'id';
    const PARENT_CLASSNAME = 'CurationSectionSplit';
    const ANCESTORS = ['sectionSplit'=>'CurationSectionSplit', 'section'=>'CoreSection', 'core'=>'CoreCore', 'hole'=>'ProjectHole', 'site'=>'ProjectSite', 'expedition'=>'ProjectExpedition', 'program'=>'ProjectProgram'];
    const DEFAULT_VALUES = [ 'igsn'=>'X', ];

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();
    }

    public static function getFormFilters() {
        return [
            "expedition" => ["model" => "ProjectExpedition", "value" => "id", "text" => "exp_acronym", "ref" => "expedition_id"],
            "site" => ["model" => "ProjectSite", "value" => "id", "text" => "site", "ref" => "site_id", "require" => ["value" => "expedition", "as" => "expedition_id"]],
            "hole" => ["model" => "ProjectHole", "value" => "id", "text" => "hole", "ref" => "hole_id", "require" => ["value" => "site", "as" => "site_id"]],
            "core" => ["model" => "CoreCore", "value" => "id", "text" => "core", "ref" => "core_id", "require" => ["value" => "hole", "as" => "hole_id"]],
            "section" => ["model" => "CoreSection", "value" => "id", "text" => "section", "ref" => "section_id", "require" => ["value" => "core", "as" => "core_id"]],
            "sectionSplit" => ["model" => "CurationSectionSplit", "value" => "id", "text" => "type", "ref" => "section_split_id", "require" => ["value" => "section", "as" => "section_id"]]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'curation_sample';
    }
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [['purpose'],'\app\components\validators\MultipleValuesStringValidator'],
            [['section_split_id', 'split_fraction_taken'], 'integer'],
            [['sample_date'], 'safe'],
            [['top', 'sample_length', 'sample_size'], 'number'],
            [['sample_request_id', 'sample_combined_id', 'sample_material', 'curator', 'comment', 'purpose', 'combined_id'], 'string', 'max' => 255],
            [['igsn'], 'string', 'max' => 32],
            [['sample_size_unit'], 'string', 'max' => 50],
            [['section_split_id'], 'exist', 'skipOnError' => true, 'targetClass' => CurationSectionSplit::className(), 'targetAttribute' => ['section_split_id' => 'id']],
            [['section_split_id'],'required'],
            [['sample_date'],'\app\components\validators\DateValidator'],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'section_split_id' => Yii::t('app', 'SectionSplit'),
            'sample_request_id' => Yii::t('app', 'Sample Request ID'),
            'sample_combined_id' => Yii::t('app', 'Sample Combined ID'),
            'igsn' => Yii::t('app', 'IGSN'),
            'sample_date' => Yii::t('app', 'Sample Date'),
            'top' => Yii::t('app', 'Sample Top [cm]'),
            'sample_length' => Yii::t('app', 'Sample Length [cm]'),
            'split_fraction_taken' => Yii::t('app', 'Fraction of Section Split [%]'),
            'sample_size' => Yii::t('app', 'Sample Size'),
            'sample_size_unit' => Yii::t('app', 'Unit of Sample Size'),
            'sample_material' => Yii::t('app', 'Sample Material'),
            'curator' => Yii::t('app', 'Curator'),
            'comment' => Yii::t('app', 'Additional Information'),
            'purpose' => Yii::t('app', 'Purpose/Usage'),
            'combined_id' => Yii::t('app', 'System Combined ID'),
            'bottom' => Yii::t('app', 'Sample Bottom [cm]'),
            'mcd_top_depth' => Yii::t('app', 'MCD Sample Top Depth [mcd]'),
            'mcd_bottom_depth' => Yii::t('app', 'MCD Sample Bottom Depth [mcd]'),
            'csfb_top_depth' => Yii::t('app', 'CSF_B Sample Top Depth [m]'),
            'csfb_bottom_depth' => Yii::t('app', 'CSF_B Sample Bottom Depth [m]'),
        ];
    }


    public function getBottom()
    {
        return is_null($this->id) ? '' : round($this->top + $this->sample_length, 2);
    }
    public function getMcd_top_depth()
    {
        return round($this->parent->mcd_top_depth + $this->top / 100, 2);
    }
    public function getMcd_bottom_depth()
    {
        return round($this->parent->mcd_top_depth + ($this->top + $this->sample_length) / 100, 2);
    }
    public function getCsfb_top_depth()
    {
        return round($this->parent->mcd_top_depth + (($this->top/100) * $this->parent->parent->section_length/$this->parent->parent->curated_length),2);
    }
    public function getCsfb_bottom_depth()
    {
        return round ($this->parent->mcd_top_depth + (($this->top+$this->sample_length)/100) * $this->parent->parent->section_length/$this->parent->parent->curated_length, 2);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getArchiveFiles()
    {
        return $this->hasMany(ArchiveFile::className(), ['sample_id' => 'id']);
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSectionSplit()
    {
        return $this->hasOne(CurationSectionSplit::className(), ['id' => 'section_split_id']);
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getParent()
    {
        return $this->getSectionSplit();
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSection()
    {
        return $this->parent->parent;
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCore()
    {
        return $this->parent->parent->parent;
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getHole()
    {
        return $this->parent->parent->parent->parent;
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSite()
    {
        return $this->parent->parent->parent->parent->parent;
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getExpedition()
    {
        return $this->parent->parent->parent->parent->parent->parent;
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProgram()
    {
        return $this->parent->parent->parent->parent->parent->parent->parent;
    }

    public function fields()
    {
        $fields = [
            'sectionSplit_id' => function($model) { return $model->injectUuid($this->sectionSplit); },
            'section_id' => function($model) { return $model->injectUuid($this->section); },
            'core_id' => function($model) { return $model->injectUuid($this->core); },
            'hole_id' => function($model) { return $model->injectUuid($this->hole); },
            'site_id' => function($model) { return $model->injectUuid($this->site); },
            'expedition_id' => function($model) { return $model->injectUuid($this->expedition); },
            'program_id' => function($model) { return $model->injectUuid($this->program); },
            'bottom' => function($model) { return is_null($this->id) ? '' : round($this->top + $this->sample_length, 2); },
            'mcd_top_depth' => function($model) { return round($this->parent->mcd_top_depth + $this->top / 100, 2); },
            'mcd_bottom_depth' => function($model) { return round($this->parent->mcd_top_depth + ($this->top + $this->sample_length) / 100, 2); },
            'csfb_top_depth' => function($model) { return round($this->parent->mcd_top_depth + (($this->top/100) * $this->parent->parent->section_length/$this->parent->parent->curated_length),2); },
            'csfb_bottom_depth' => function($model) { return round ($this->parent->mcd_top_depth + (($this->top+$this->sample_length)/100) * $this->parent->parent->section_length/$this->parent->parent->curated_length, 2); },
            'purpose' => function($model) {
                if (!empty($model->purpose)) {
                    return explode(';', $model->purpose);
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
                        'site' => $model->site->id,
                        'hole' => $model->hole->id,
                        'core' => $model->core->id,
                        'section' => $model->section->id,
                        'sectionSplit' => $model->sectionSplit->id,
                        'id' => $model->id
                    ],
                    'files' => ArchiveFile::find()->andWhere([
                        'sample_id' => $model->id
                    ])->all()
                ];
            };
        }
        return array_merge(parent::fields(), $fields);
    }

    public function load($data, $formName = null)
    {
        if(isset($data['sectionSplit_id']) && is_array($data['sectionSplit_id'])) {
            $data['sectionSplit_id'] = $data['sectionSplit_id']['id'];
        }
        if(isset($data['section_id']) && is_array($data['section_id'])) {
            $data['section_id'] = $data['section_id']['id'];
        }
        if(isset($data['core_id']) && is_array($data['core_id'])) {
            $data['core_id'] = $data['core_id']['id'];
        }
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
    public function beforeDelete()
    {
        return parent::beforeDelete(); // TODO: Change the autogenerated stub
    }

    /**
     * @inheritDoc
     * The combined_id is built differently
     */
    public function calculateCombinedId ($combinedIdField = "combined_id") {
        if ($combinedIdField == 'sample_combined_id')
            $combinedId = $this->getParentCombinedId('combined_id') . ':' . $this->top . '-' . ($this->top + $this->sample_length);
        else
            $combinedId = parent::calculateCombinedId ($combinedIdField);
        return $combinedId;
    }
}

