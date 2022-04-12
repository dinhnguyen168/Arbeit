<?php

namespace app\models\base;

use Yii;
use app\models\ArchiveFile;
use app\models\ProjectHole;
use app\models\ProjectSite;
use app\models\CoreCoreCoreSection;
use app\models\CoreSection;
use app\models\ProjectExpedition;
use app\models\ProjectProgram;

/**
 * This is the generated model base class for table "core_core".
 * DO NOT EDIT THIS CLASS MANUALLY!
 *
 * @property int $id SKEY
 * @property int|null $hole_id Hole Id
 * @property string|null $combined_id Combined Id
 * @property int|null $core Core Number
 * @property string|null $igsn IGSN
 * @property string|null $core_ondeck Core on Deck (CoD)
 * @property float|null $drillers_top_depth Drillers Top Core Depth [mbrf]
 * @property float|null $drilled_length Drilled Length [m]
 * @property string|null $bit_size Bit Size
 * @property float|null $barrel_length Barrel Length [m]
 * @property string|null $fluid_type Drilling Fluid Type
 * @property float|null $core_recovery Core Recovery [m]
 * @property string|null $core_loss_reason Core Loss Reason
 * @property string|null $core_type Core Type
 * @property int|null $core_oriented Core Oriented?
 * @property float|null $core_diameter Core Diameter [mm]
 * @property string|null $continuity Continuity Between Cores
 * @property int|null $section_count Number of Core Sections 
 * @property string|null $rqd Rock Quality Designation (RQD)
 * @property string|null $curator Curator
 * @property string|null $comment Additional Information
 * @property string|null $comment_identifier Additional Information 
 * @property string|null $comment_depth Addtional Information
 * @property string|null $comment_drilling Additional Information
 * @property int|null $site_id
 * @property int|null $test123
 *
 * @property ArchiveFile[] $archiveFiles
 * @property ProjectHole $hole
 * @property ProjectSite $site
 * @property CoreCoreCoreSection[] $coreCoreCoreSections
 * @property CoreSection[] $coreSections
 * @property ProjectHole $parent
 * @property ProjectExpedition $expedition
 * @property ProjectProgram $program
 */
abstract class BaseCoreCore extends \app\models\core\Base
{
    /* [i.e columnName => [displayColumn, relationName],[..]] */
    const MANY_TO_MANY_COLUMNS = [];
    /* [[i.e columnName => displayColumn],[..]] */
    const ONE_TO_MANY_COLUMNS = [];

    const MODULE = 'Core';
    const SHORT_NAME = 'Core';

    const NAME_ATTRIBUTE = 'core';
    const PARENT_CLASSNAME = 'ProjectHole';
    const ANCESTORS = ['hole'=>'ProjectHole', 'site'=>'ProjectSite', 'expedition'=>'ProjectExpedition', 'program'=>'ProjectProgram'];
    const DEFAULT_VALUES = [ 'igsn'=>'C', ];

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
            "hole" => ["model" => "ProjectHole", "value" => "id", "text" => "hole", "ref" => "hole_id", "require" => ["value" => "site", "as" => "site_id"]]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'core_core';
    }
    /**
    * {@inheritdoc}
    */
    public function behaviors()
    {
        return array_merge(parent::behaviors(), [
            ['class' => 'app\behaviors\template\UniqueCombinationAutoIncrementBehavior', 'searchFields' => ['hole_id'], 'fieldToFill' => 'core', 'useAlphabet' => false],
            ['class' => 'app\behaviors\template\DefaultFromSiblingBehavior', 'parentRefColumn' => 'hole_id', 'sourceColumn' => 'drillers_bottom_depth', 'destinationColumn' => 'drillers_top_depth'],
            ['class' => 'app\behaviors\template\LeadingZerosBehavior', 'column' => 'core', 'length' => '3'],
        ]);
    }
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [['hole_id', 'core', 'core_oriented', 'section_count', 'site_id', 'test123'], 'integer'],
            [['core_ondeck'], 'safe'],
            [['drillers_top_depth', 'drilled_length', 'barrel_length', 'core_recovery', 'core_diameter'], 'number'],
            [['combined_id', 'bit_size', 'fluid_type', 'core_loss_reason', 'core_type', 'continuity', 'rqd', 'curator', 'comment', 'comment_identifier', 'comment_depth'], 'string', 'max' => 255],
            [['igsn'], 'string', 'max' => 32],
            [['comment_drilling'], 'string', 'max' => 500],
            [['hole_id', 'core'], 'unique', 'targetAttribute' => ['hole_id', 'core']],
            [['hole_id'], 'exist', 'skipOnError' => true, 'targetClass' => ProjectHole::className(), 'targetAttribute' => ['hole_id' => 'id']],
            [['site_id'], 'exist', 'skipOnError' => true, 'targetClass' => ProjectSite::className(), 'targetAttribute' => ['site_id' => 'id']],
            [['hole_id', 'core_ondeck', 'drilled_length'],'required'],
            [['core_oriented'],'boolean'],
            [['core_ondeck'],'\app\components\validators\DateTimeValidator'],
            [['id'], 'compare', 'operator' => '>=', 'compareValue' => '0', 'type' => 'number', 'message' => 'required key field, please enter / select a valid value'],
            [['drillers_top_depth'], 'compare', 'operator' => '>=', 'compareValue' => '0.0', 'type' => 'number'],
            [['drilled_length'], 'compare', 'operator' => '>=', 'compareValue' => '0.0', 'type' => 'number', 'message' => 'real value >=0.0'],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'SKEY'),
            'hole_id' => Yii::t('app', 'Hole Id'),
            'combined_id' => Yii::t('app', 'Combined Id'),
            'core' => Yii::t('app', 'Core Number'),
            'igsn' => Yii::t('app', 'IGSN'),
            'core_ondeck' => Yii::t('app', 'Core on Deck (CoD)'),
            'drillers_top_depth' => Yii::t('app', 'Drillers Top Core Depth [mbrf]'),
            'drilled_length' => Yii::t('app', 'Drilled Length [m]'),
            'bit_size' => Yii::t('app', 'Bit Size'),
            'barrel_length' => Yii::t('app', 'Barrel Length [m]'),
            'fluid_type' => Yii::t('app', 'Drilling Fluid Type'),
            'core_recovery' => Yii::t('app', 'Core Recovery [m]'),
            'core_loss_reason' => Yii::t('app', 'Core Loss Reason'),
            'core_type' => Yii::t('app', 'Core Type'),
            'core_oriented' => Yii::t('app', 'Core Oriented?'),
            'core_diameter' => Yii::t('app', 'Core Diameter [mm]'),
            'continuity' => Yii::t('app', 'Continuity Between Cores'),
            'section_count' => Yii::t('app', 'Number of Core Sections '),
            'rqd' => Yii::t('app', 'Rock Quality Designation (RQD)'),
            'curator' => Yii::t('app', 'Curator'),
            'comment' => Yii::t('app', 'Additional Information'),
            'comment_identifier' => Yii::t('app', 'Additional Information '),
            'comment_depth' => Yii::t('app', 'Addtional Information'),
            'comment_drilling' => Yii::t('app', 'Additional Information'),
            'site_id' => Yii::t('app', 'Site ID'),
            'test123' => Yii::t('app', 'Test123'),
            'drillers_bottom_depth' => Yii::t('app', 'Drillers Bottom Core Depth [mbrf]'),
            'core_top_depth' => Yii::t('app', 'Top Core Depth Below Surface [mbs]'),
            'core_bottom_depth' => Yii::t('app', 'Bottom Core Depth Below Surface [mbs]'),
            'core_recovery_pc' => Yii::t('app', 'Core Recovery [%]'),
            'section_split_exist' => Yii::t('app', 'Section Split Exist'),
        ];
    }


    public function getDrillers_bottom_depth()
    {
        return $this->id ? round($this->drillers_top_depth + $this->drilled_length, 2) : '';
    }
    public function getCore_top_depth()
    {
        return $this->id ? round($this->drillers_top_depth - $this->parent->drillers_reference_height - $this->parent->depth_water, 2) : '';
    }
    public function getCore_bottom_depth()
    {
        return $this->id ? round($this->drillers_top_depth - $this->parent->drillers_reference_height - $this->parent->depth_water + $this->drilled_length, 2) : '';
    }
    public function getCore_recovery_pc()
    {
        return $this->id ? ($this->drilled_length == 0 ? '0' : round($this->core_recovery / $this->drilled_length *100, 2)) : '';
    }
    public function getSection_split_exist()
    {
        return $this->getSplitStatus();
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getArchiveFiles()
    {
        return $this->hasMany(ArchiveFile::className(), ['core_id' => 'id']);
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
    public function getSite()
    {
        return $this->parent->parent;
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCoreCoreCoreSections()
    {
        return $this->hasMany(CoreCoreCoreSection::className(), ['core_core_id' => 'id']);
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCoreSections()
    {
        return $this->hasMany(CoreSection::className(), ['core_id' => 'id']);
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
            'drillers_bottom_depth' => function($model) { return $this->id ? round($this->drillers_top_depth + $this->drilled_length, 2) : ''; },
            'core_top_depth' => function($model) { return $this->id ? round($this->drillers_top_depth - $this->parent->drillers_reference_height - $this->parent->depth_water, 2) : ''; },
            'core_bottom_depth' => function($model) { return $this->id ? round($this->drillers_top_depth - $this->parent->drillers_reference_height - $this->parent->depth_water + $this->drilled_length, 2) : ''; },
            'core_recovery_pc' => function($model) { return $this->id ? ($this->drilled_length == 0 ? '0' : round($this->core_recovery / $this->drilled_length *100, 2)) : ''; },
            'section_split_exist' => function($model) { return $this->getSplitStatus(); },
        ];
        if(\Yii::$app->controller->action->id !== "harvest") {
            $fields["archive_files"] = function ($model) {
                return [
                    'filter' => [
                        'expedition' => $model->expedition->id,
                        'site' => $model->site->id,
                        'hole' => $model->hole->id,
                        'core' => $model->id
                    ],
                    'files' => ArchiveFile::find()->andWhere([
                        'core_id' => $model->id
                    ])->all()
                ];
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
    public function beforeDelete()
    {
        return parent::beforeDelete(); // TODO: Change the autogenerated stub
    }

    /**
     * This method is inserted into the generated class file "backend/models/base/BaseCoreCore".
     *
     * Get split status of all section splits in this core
     * @return string "none" | "partly" | "all"
     */
    protected function getSplitStatus () {
        $status = "error";
        if (class_exists("\\app\\models\\CurationSectionSplit") && class_exists("\\app\\models\\CoreSection")) {
            $nCntSections = \app\models\CoreSection::find()
                ->andWhere(["core_id" => $this->id])
                ->count();
            $nCntSplits = \app\models\CurationSectionSplit::find()
                ->innerJoinWith("section")
                ->andWhere(["type" => "A"])
                ->andWhere(["core_id" => $this->id])
                ->count();
            $status = ($nCntSplits == 0 ? "none" : ($nCntSplits == $nCntSections ? "all" : "partly"));
        }
        return $status;
    }

    /**
     * @inheritDoc
     * The core number shall be extended to 3 digits in the combined_id
     */
    public function calculateCombinedId ($combinedIdField = "combined_id") {
        $combinedId = $this->getParentCombinedId($combinedIdField);
        $attribute = $this::NAME_ATTRIBUTE;
        if ($attribute > "") {
            $value = $this->owner->{$attribute};
            if ($attribute == "core") $value = str_pad (strval($value), 3, "0", STR_PAD_LEFT);
            $combinedId .= ($combinedId > "" ? '_' : "") . $value;
        }
        return $combinedId;
    }
}

