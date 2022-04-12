<?php

namespace app\models\base;

use Yii;
use app\models\ArchiveFile;
use app\models\CoreCoreCoreSection;
use app\models\CoreCore;
use app\models\CurationSectionSplit;
use app\models\GeologyStructure;
use app\models\ProjectHole;
use app\models\ProjectSite;
use app\models\ProjectExpedition;
use app\models\ProjectProgram;

/**
 * This is the generated model base class for table "core_section".
 * DO NOT EDIT THIS CLASS MANUALLY!
 *
 * @property int $id Id
 * @property int|null $core_id Core ID
 * @property int|null $section Section Number
 * @property string|null $combined_id Combined ID
 * @property float|null $section_length Section Length [m]
 * @property float|null $curated_length Curated Length [m]
 * @property int|null $core_catcher Core Catcher
 * @property string|null $section_condition Section Condition
 * @property float|null $mcd_offset MCD Offset [m]
 * @property string|null $curator Curator
 * @property string|null $comment Additional Information
 * @property string|null $comment_depth Additional Information
 *
 * @property ArchiveFile[] $archiveFiles
 * @property CoreCoreCoreSection[] $coreCoreCoreSections
 * @property CoreCore $core
 * @property CurationSectionSplit[] $curationSectionSplits
 * @property GeologyStructure[] $geologyStructures
 * @property CoreCore $parent
 * @property ProjectHole $hole
 * @property ProjectSite $site
 * @property ProjectExpedition $expedition
 * @property ProjectProgram $program
 */
abstract class BaseCoreSection extends \app\models\core\Base
{
    /* [i.e columnName => [displayColumn, relationName],[..]] */
    const MANY_TO_MANY_COLUMNS = [];
    /* [[i.e columnName => displayColumn],[..]] */
    const ONE_TO_MANY_COLUMNS = [];

    const MODULE = 'Core';
    const SHORT_NAME = 'Section';

    const NAME_ATTRIBUTE = 'section';
    const PARENT_CLASSNAME = 'CoreCore';
    const ANCESTORS = ['core'=>'CoreCore', 'hole'=>'ProjectHole', 'site'=>'ProjectSite', 'expedition'=>'ProjectExpedition', 'program'=>'ProjectProgram'];
    const DEFAULT_VALUES = [ 'mcd_offset'=>0, ];

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();
        $this->mcd_offset = 0;
    }

    public static function getFormFilters() {
        return [
            "expedition" => ["model" => "ProjectExpedition", "value" => "id", "text" => "exp_acronym", "ref" => "expedition_id"],
            "site" => ["model" => "ProjectSite", "value" => "id", "text" => "site", "ref" => "site_id", "require" => ["value" => "expedition", "as" => "expedition_id"]],
            "hole" => ["model" => "ProjectHole", "value" => "id", "text" => "hole", "ref" => "hole_id", "require" => ["value" => "site", "as" => "site_id"]],
            "core" => ["model" => "CoreCore", "value" => "id", "text" => "core", "ref" => "core_id", "require" => ["value" => "hole", "as" => "hole_id"]]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'core_section';
    }
    /**
    * {@inheritdoc}
    */
    public function behaviors()
    {
        return array_merge(parent::behaviors(), [
            ['class' => 'app\behaviors\template\SiblingsLimitFromParentBehavior', 'parentRefColumn' => 'core_id', 'parentSourceColumn' => 'section_count'],
            ['class' => 'app\behaviors\template\UniqueCombinationAutoIncrementBehavior', 'searchFields' => ['core_id'], 'fieldToFill' => 'section', 'useAlphabet' => false],
            ['class' => 'app\behaviors\template\SplittableSectionBehavior', 'splitsModel' => 'CurationSectionSplit'],
            ['class' => 'app\behaviors\template\DefaultFromOtherColumnBehavior', 'sourceColumn' => 'section_length', 'targetColumn' => 'curated_length'],
        ]);
    }
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [['core_id', 'section', 'core_catcher'], 'integer'],
            [['section_length', 'curated_length', 'mcd_offset'], 'number'],
            [['combined_id'], 'string', 'max' => 20],
            [['section_condition', 'curator', 'comment', 'comment_depth'], 'string', 'max' => 255],
            [['core_id', 'section'], 'unique', 'targetAttribute' => ['core_id', 'section']],
            [['core_id'], 'exist', 'skipOnError' => true, 'targetClass' => CoreCore::className(), 'targetAttribute' => ['core_id' => 'id']],
            [['core_id'],'required'],
            [['core_catcher'],'boolean'],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'Id'),
            'core_id' => Yii::t('app', 'Core ID'),
            'section' => Yii::t('app', 'Section Number'),
            'combined_id' => Yii::t('app', 'Combined ID'),
            'section_length' => Yii::t('app', 'Section Length [m]'),
            'curated_length' => Yii::t('app', 'Curated Length [m]'),
            'core_catcher' => Yii::t('app', 'Core Catcher'),
            'section_condition' => Yii::t('app', 'Section Condition'),
            'mcd_offset' => Yii::t('app', 'MCD Offset [m]'),
            'curator' => Yii::t('app', 'Curator'),
            'comment' => Yii::t('app', 'Additional Information'),
            'comment_depth' => Yii::t('app', 'Additional Information'),
            'top_depth' => Yii::t('app', 'Section Top Depth [mbs] or [mbsf]'),
            'bottom_depth' => Yii::t('app', 'Section Bottom Depth [mbs] or [mbsf]'),
            'mcd_top_depth' => Yii::t('app', 'MCD Top Depth [mcd]'),
            'mcd_bottom_depth' => Yii::t('app', 'MCD Bottom Depth [mcd]'),
            'section_split_exist' => Yii::t('app', 'Section Split Exist'),
        ];
    }


    public function getTop_depth()
    {
        return $this->getSectionTop();
    }
    public function getBottom_depth()
    {
        $model = $this;
        return round($model->top_depth + $model->section_length,2)
;
    }
    public function getMcd_top_depth()
    {
        $model = $this;
        return round($this->getSectionTop() + $model->mcd_offset,2)
;
    }
    public function getMcd_bottom_depth()
    {
        $model = $this;
        return round($model->top_depth + $model->section_length + $model->mcd_offset, 2)  
;
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
        return $this->hasMany(ArchiveFile::className(), ['section_id' => 'id']);
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCoreCoreCoreSections()
    {
        return $this->hasMany(CoreCoreCoreSection::className(), ['core_section_id' => 'id']);
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCore()
    {
        return $this->hasOne(CoreCore::className(), ['id' => 'core_id']);
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCurationSectionSplits()
    {
        return $this->hasMany(CurationSectionSplit::className(), ['section_id' => 'id']);
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGeologyStructures()
    {
        return $this->hasMany(GeologyStructure::className(), ['section_id' => 'id']);
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getParent()
    {
        return $this->getCore();
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getHole()
    {
        return $this->parent->parent;
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSite()
    {
        return $this->parent->parent->parent;
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getExpedition()
    {
        return $this->parent->parent->parent->parent;
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProgram()
    {
        return $this->parent->parent->parent->parent->parent;
    }

    public function fields()
    {
        $fields = [
            'core_id' => function($model) { return $model->injectUuid($this->core); },
            'hole_id' => function($model) { return $model->injectUuid($this->hole); },
            'site_id' => function($model) { return $model->injectUuid($this->site); },
            'expedition_id' => function($model) { return $model->injectUuid($this->expedition); },
            'program_id' => function($model) { return $model->injectUuid($this->program); },
            'top_depth' => function($model) { return $this->getSectionTop(); },
            'bottom_depth' => function($model) { return round($model->top_depth + $model->section_length,2)
; },
            'mcd_top_depth' => function($model) { return round($this->getSectionTop() + $model->mcd_offset,2)
; },
            'mcd_bottom_depth' => function($model) { return round($model->top_depth + $model->section_length + $model->mcd_offset, 2)  
; },
            'section_split_exist' => function($model) { return $this->getSplitStatus(); },
        ];
        if(\Yii::$app->controller->action->id !== "harvest") {
            $fields["archive_files"] = function ($model) {
                return [
                    'filter' => [
                        'expedition' => $model->expedition->id,
                        'site' => $model->site->id,
                        'hole' => $model->hole->id,
                        'core' => $model->core->id,
                        'section' => $model->id
                    ],
                    'files' => ArchiveFile::find()->andWhere([
                        'section_id' => $model->id
                    ])->all()
                ];
            };
        }
        return array_merge(parent::fields(), $fields);
    }

    public function load($data, $formName = null)
    {
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
     * This method is inserted into the generated class file "backend/models/base/BaseCoreSection".
     *
     * Has this section been splitted?
     * @return string "yes" | "no"
     */
    protected function getSplitStatus () {
        $status = "error";
        if (class_exists("\\app\\models\\CurationSectionSplit")) {
            $bExists = \app\models\CurationSectionSplit::find()
                ->andWhere(["!=", "type", "WR"])
                ->andWhere(["section_id" => $this->id])
                ->exists();
            $status = $bExists ? "yes" : "no";
        }
        return $status;
    }
    protected function getSectionTop()
    {
        $sectionsAboveKeys = array_keys(array_filter(array_column($this->parent->coreSections, 'section'), function ($x) { return $x < $this->section ; }));
        $arrayAllSections = array_column($this->core->coreSections, 'section_length');
        $arraySectionsAbove = array();
        foreach ($sectionsAboveKeys as $arrayKey) {
            array_push($arraySectionsAbove, $arrayAllSections[$arrayKey]);
        };
        $topDepth = array_sum($arraySectionsAbove) + $this->parent->drillers_top_depth;
        return $topDepth;
    }
}

