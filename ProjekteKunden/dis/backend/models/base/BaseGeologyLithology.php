<?php

namespace app\models\base;

use Yii;
use app\models\CurationSectionSplit;
use app\models\CoreSection;
use app\models\CoreCore;
use app\models\ProjectHole;
use app\models\ProjectSite;
use app\models\ProjectExpedition;
use app\models\ProjectProgram;
use app\models\ArchiveFile;

/**
 * This is the generated model base class for table "geology_lithology".
 * DO NOT EDIT THIS CLASS MANUALLY!
 *
 * @property int $id ID
 * @property string|null $curator Curator
 * @property int|null $section_split_id SectionSplit
 * @property string|null $combined_id System Combined ID
 * @property string|null $litho_unit Lithological Unit
 * @property float|null $top_depth Unit Top Depth [cm]
 * @property float|null $unit_length Unit Length [cm]
 * @property float|null $bottom_depth Unit Bottom Depth [cm]
 * @property string|null $rock_class Rock Class
 * @property string|null $rock_type Simple Type of Lithology
 * @property string|null $color Color
 * @property string|null $composition Mineral Composition Overview
 * @property string|null $description Description
 *
 * @property CurationSectionSplit $sectionSplit
 * @property CurationSectionSplit $parent
 * @property CoreSection $section
 * @property CoreCore $core
 * @property ProjectHole $hole
 * @property ProjectSite $site
 * @property ProjectExpedition $expedition
 * @property ProjectProgram $program
 */
abstract class BaseGeologyLithology extends \app\models\core\Base
{
    /* [i.e columnName => [displayColumn, relationName],[..]] */
    const MANY_TO_MANY_COLUMNS = [];
    /* [[i.e columnName => displayColumn],[..]] */
    const ONE_TO_MANY_COLUMNS = [];

    const MODULE = 'Geology';
    const SHORT_NAME = 'Lithology';

    const NAME_ATTRIBUTE = 'id';
    const PARENT_CLASSNAME = 'CurationSectionSplit';
    const ANCESTORS = ['sectionSplit'=>'CurationSectionSplit', 'section'=>'CoreSection', 'core'=>'CoreCore', 'hole'=>'ProjectHole', 'site'=>'ProjectSite', 'expedition'=>'ProjectExpedition', 'program'=>'ProjectProgram'];
    const DEFAULT_VALUES = [ 'top_depth'=>0, ];

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();
        $this->top_depth = 0;
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
        return 'geology_lithology';
    }
    /**
    * {@inheritdoc}
    */
    public function behaviors()
    {
        return array_merge(parent::behaviors(), [
            ['class' => 'app\behaviors\template\DefaultFromSiblingBehavior', 'parentRefColumn' => 'section_split_id', 'sourceColumn' => 'bottom_depth', 'destinationColumn' => 'top_depth'],
        ]);
    }
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [['curator', 'rock_type', 'color', 'composition'],'\app\components\validators\MultipleValuesStringValidator'],
            [['section_split_id'], 'integer'],
            [['top_depth', 'unit_length', 'bottom_depth'], 'number'],
            [['curator', 'combined_id', 'litho_unit', 'rock_class', 'rock_type', 'color', 'composition', 'description'], 'string', 'max' => 255],
            [['section_split_id'], 'exist', 'skipOnError' => true, 'targetClass' => CurationSectionSplit::className(), 'targetAttribute' => ['section_split_id' => 'id']],
            [['section_split_id'],'required'],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'curator' => Yii::t('app', 'Curator'),
            'section_split_id' => Yii::t('app', 'SectionSplit'),
            'combined_id' => Yii::t('app', 'System Combined ID'),
            'litho_unit' => Yii::t('app', 'Lithological Unit'),
            'top_depth' => Yii::t('app', 'Unit Top Depth [cm]'),
            'unit_length' => Yii::t('app', 'Unit Length [cm]'),
            'bottom_depth' => Yii::t('app', 'Unit Bottom Depth [cm]'),
            'rock_class' => Yii::t('app', 'Rock Class'),
            'rock_type' => Yii::t('app', 'Simple Type of Lithology'),
            'color' => Yii::t('app', 'Color'),
            'composition' => Yii::t('app', 'Mineral Composition Overview'),
            'description' => Yii::t('app', 'Description'),
            'split_combined_id' => Yii::t('app', 'Split Combined Id'),
            'section_length' => Yii::t('app', 'Section Length'),
            'mcd_bottom_depth_unit' => Yii::t('app', 'MCD Bottom Depth Unit [m]'),
            'mcd_top_depth_unit' => Yii::t('app', 'MCD Top Depth Unit [m]'),
        ];
    }


    public function getSplit_combined_id()
    {
        return ($this->parent ? $this->parent->combined_id : null);
    }
    public function getSection_length()
    {
        return ($this->parent && $this->parent->parent ? $this->parent->parent->section_length : null);
    }
    public function getMcd_bottom_depth_unit()
    {
        $model = $this;
        return $model->parent->parent->mcd_top_depth + ($model->top_depth/100) + ($model->unit_length/100);
    }
    public function getMcd_top_depth_unit()
    {
        $model = $this;
        return $model->parent->parent->mcd_top_depth + ($model->top_depth / 100)
;
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
            'split_combined_id' => function($model) { return ($this->parent ? $this->parent->combined_id : null); },
            'section_length' => function($model) { return ($this->parent && $this->parent->parent ? $this->parent->parent->section_length : null); },
            'mcd_bottom_depth_unit' => function($model) { return $model->parent->parent->mcd_top_depth + ($model->top_depth/100) + ($model->unit_length/100); },
            'mcd_top_depth_unit' => function($model) { return $model->parent->parent->mcd_top_depth + ($model->top_depth / 100)
; },
            'curator' => function($model) {
                if (!empty($model->curator)) {
                    return explode(';', $model->curator);
                } else {
                    return [];
                }
            },
            'rock_type' => function($model) {
                if (!empty($model->rock_type)) {
                    return explode(';', $model->rock_type);
                } else {
                    return [];
                }
            },
            'color' => function($model) {
                if (!empty($model->color)) {
                    return explode(';', $model->color);
                } else {
                    return [];
                }
            },
            'composition' => function($model) {
                if (!empty($model->composition)) {
                    return explode(';', $model->composition);
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
                    ]                ];
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


}

