<?php

namespace app\models\base;

use Yii;
use app\models\ArchiveFile;
use app\models\CurationSample;
use app\models\CurationCorebox;
use app\models\CoreSection;
use app\models\CurationSectionSplit;
use app\models\GeologyLithology;
use app\models\CoreCore;
use app\models\ProjectHole;
use app\models\ProjectSite;
use app\models\ProjectExpedition;
use app\models\ProjectProgram;

/**
 * This is the generated model base class for table "curation_section_split".
 * DO NOT EDIT THIS CLASS MANUALLY!
 *
 * @property int $id ID
 * @property int|null $section_id Section
 * @property string|null $type Split Type
 * @property int|null $origin_split_id Origin Split ID
 * @property string|null $combined_id Combined ID
 * @property string|null $igsn IGSN
 * @property int|null $still_exists Still Exists
 * @property int|null $sampleable Sampling Allowed
 * @property int|null $percent Percent of Wholeround [%]
 * @property string|null $curator Curator
 * @property int|null $corebox_id Core Box Database ID
 * @property int|null $corebox_slot Slot in Core Box
 * @property string|null $corebox_position Position in Corebox
 * @property string|null $comment Additional Information
 * @property string|null $comment_storage Additional Information
 * @property string|null $comment_identifier Additional Information
 * @property int|null $measurement_exists Measurement Exists
 * @property float|null $weight Weight
 * @property string|null $crate_name Crate Name
 * @property string|null $storage_combined_id Storage Combined Id
 * @property string|null $corebox_name Core Box
 *
 * @property ArchiveFile[] $archiveFiles
 * @property CurationSample[] $curationSamples
 * @property CurationCorebox $corebox
 * @property CoreSection $section
 * @property CurationSectionSplit $originSplit
 * @property CurationSectionSplit[] $curationSectionSplits
 * @property GeologyLithology[] $geologyLithologies
 * @property CoreSection $parent
 * @property CoreCore $core
 * @property ProjectHole $hole
 * @property ProjectSite $site
 * @property ProjectExpedition $expedition
 * @property ProjectProgram $program
 */
abstract class BaseCurationSectionSplit extends \app\models\core\Base
{
    /* [i.e columnName => [displayColumn, relationName],[..]] */
    const MANY_TO_MANY_COLUMNS = [];
    /* [[i.e columnName => displayColumn],[..]] */
    const ONE_TO_MANY_COLUMNS = [];

    const MODULE = 'Curation';
    const SHORT_NAME = 'SectionSplit';

    const NAME_ATTRIBUTE = 'type';
    const PARENT_CLASSNAME = 'CoreSection';
    const ANCESTORS = ['section'=>'CoreSection', 'core'=>'CoreCore', 'hole'=>'ProjectHole', 'site'=>'ProjectSite', 'expedition'=>'ProjectExpedition', 'program'=>'ProjectProgram'];
    const DEFAULT_VALUES = [ 'igsn'=>'S', ];

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
            "section" => ["model" => "CoreSection", "value" => "id", "text" => "section", "ref" => "section_id", "require" => ["value" => "core", "as" => "core_id"]]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'curation_section_split';
    }
    /**
    * {@inheritdoc}
    */
    public function behaviors()
    {
        return array_merge(parent::behaviors(), [
            ['class' => 'app\behaviors\template\LeadingZerosBehavior', 'column' => 'corebox_name', 'length' => '3'],
            ['class' => 'app\behaviors\template\ValidateSplitOriginTypeBehavior'],
            ['class' => 'app\behaviors\template\CreateUpdateCoreboxBehavior', 'coreboxNameColumn' => 'corebox_name', 'coreboxIdColumn' => 'corebox_id'],
        ]);
    }
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [['section_id', 'origin_split_id', 'still_exists', 'sampleable', 'percent', 'corebox_id', 'corebox_slot', 'measurement_exists'], 'integer'],
            [['weight'], 'number'],
            [['type'], 'string', 'max' => 4],
            [['combined_id', 'curator', 'comment', 'comment_storage', 'comment_identifier', 'crate_name', 'storage_combined_id', 'corebox_name'], 'string', 'max' => 255],
            [['igsn'], 'string', 'max' => 32],
            [['corebox_position'], 'string', 'max' => 2],
            [['section_id', 'type'], 'unique', 'targetAttribute' => ['section_id', 'type']],
            [['corebox_id'], 'exist', 'skipOnError' => true, 'targetClass' => CurationCorebox::className(), 'targetAttribute' => ['corebox_id' => 'id']],
            [['section_id'], 'exist', 'skipOnError' => true, 'targetClass' => CoreSection::className(), 'targetAttribute' => ['section_id' => 'id']],
            [['origin_split_id'], 'exist', 'skipOnError' => true, 'targetClass' => CurationSectionSplit::className(), 'targetAttribute' => ['origin_split_id' => 'id']],
            [['section_id', 'type', 'percent'],'required'],
            [['still_exists', 'sampleable', 'measurement_exists'],'boolean'],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'section_id' => Yii::t('app', 'Section'),
            'type' => Yii::t('app', 'Split Type'),
            'origin_split_id' => Yii::t('app', 'Origin Split ID'),
            'combined_id' => Yii::t('app', 'Combined ID'),
            'igsn' => Yii::t('app', 'IGSN'),
            'still_exists' => Yii::t('app', 'Still Exists'),
            'sampleable' => Yii::t('app', 'Sampling Allowed'),
            'percent' => Yii::t('app', 'Percent of Wholeround [%]'),
            'curator' => Yii::t('app', 'Curator'),
            'corebox_id' => Yii::t('app', 'Core Box Database ID'),
            'corebox_slot' => Yii::t('app', 'Slot in Core Box'),
            'corebox_position' => Yii::t('app', 'Position in Corebox'),
            'comment' => Yii::t('app', 'Additional Information'),
            'comment_storage' => Yii::t('app', 'Additional Information'),
            'comment_identifier' => Yii::t('app', 'Additional Information'),
            'measurement_exists' => Yii::t('app', 'Measurement Exists'),
            'weight' => Yii::t('app', 'Weight'),
            'crate_name' => Yii::t('app', 'Crate Name'),
            'storage_combined_id' => Yii::t('app', 'Storage Combined Id'),
            'corebox_name' => Yii::t('app', 'Core Box'),
            'origin_split_combined_id' => Yii::t('app', 'Origin Split Combined ID'),
            'origin_split_type' => Yii::t('app', 'Origin Split Type'),
            'curated_length' => Yii::t('app', 'Curated Length [cm]'),
            'mcd_top_depth' => Yii::t('app', 'MCD Top Depth [mcd]'),
            'mcd_bottom_depth' => Yii::t('app', 'MCD Bottom Depth [mcd]'),
        ];
    }


    public function getOrigin_split_combined_id()
    {
        return 
is_object($this->originSplit)  ? $this->originSplit->combined_id   : "";
    }
    public function getOrigin_split_type()
    {
        return $this->originSplit ? $this->originSplit->type : '';
    }
    public function getCurated_length()
    {
        $model = $this;
        return $model->parent->curated_length * 100;
    }
    public function getMcd_top_depth()
    {
        return ($this->parent ? $this->parent->mcd_top_depth : null);
    }
    public function getMcd_bottom_depth()
    {
        $model = $this;
        return $model->parent->mcd_top_depth + $model->parent->section_length;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getArchiveFiles()
    {
        return $this->hasMany(ArchiveFile::className(), ['section_split_id' => 'id']);
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCurationSamples()
    {
        return $this->hasMany(CurationSample::className(), ['section_split_id' => 'id']);
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCorebox()
    {
        return $this->hasOne(CurationCorebox::className(), ['id' => 'corebox_id']);
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSection()
    {
        return $this->hasOne(CoreSection::className(), ['id' => 'section_id']);
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOriginSplit()
    {
        return $this->hasOne(CurationSectionSplit::className(), ['id' => 'origin_split_id']);
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCurationSectionSplits()
    {
        return $this->hasMany(CurationSectionSplit::className(), ['origin_split_id' => 'id']);
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGeologyLithologies()
    {
        return $this->hasMany(GeologyLithology::className(), ['section_split_id' => 'id']);
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getParent()
    {
        return $this->getSection();
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCore()
    {
        return $this->parent->parent;
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getHole()
    {
        return $this->parent->parent->parent;
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSite()
    {
        return $this->parent->parent->parent->parent;
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getExpedition()
    {
        return $this->parent->parent->parent->parent->parent;
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProgram()
    {
        return $this->parent->parent->parent->parent->parent->parent;
    }

    public function fields()
    {
        $fields = [
            'section_id' => function($model) { return $model->injectUuid($this->section); },
            'core_id' => function($model) { return $model->injectUuid($this->core); },
            'hole_id' => function($model) { return $model->injectUuid($this->hole); },
            'site_id' => function($model) { return $model->injectUuid($this->site); },
            'expedition_id' => function($model) { return $model->injectUuid($this->expedition); },
            'program_id' => function($model) { return $model->injectUuid($this->program); },
            'origin_split_combined_id' => function($model) { return 
is_object($this->originSplit)  ? $this->originSplit->combined_id   : ""; },
            'origin_split_type' => function($model) { return $this->originSplit ? $this->originSplit->type : ''; },
            'curated_length' => function($model) { return $model->parent->curated_length * 100; },
            'mcd_top_depth' => function($model) { return ($this->parent ? $this->parent->mcd_top_depth : null); },
            'mcd_bottom_depth' => function($model) { return $model->parent->mcd_top_depth + $model->parent->section_length; },
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
                        'type' => $model->id
                    ],
                    'files' => ArchiveFile::find()->andWhere([
                        'section_split_id' => $model->id
                    ])->all()
                ];
            };
        }
        return array_merge(parent::fields(), $fields);
    }

    public function load($data, $formName = null)
    {
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


}

