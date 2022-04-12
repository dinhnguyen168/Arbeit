<?php

namespace app\models\base;

use Yii;
use app\models\CoreSection;
use app\models\CoreCore;
use app\models\ProjectHole;
use app\models\ProjectSite;
use app\models\ProjectExpedition;
use app\models\ProjectProgram;
use app\models\ArchiveFile;

/**
 * This is the generated model base class for table "geology_structure".
 * DO NOT EDIT THIS CLASS MANUALLY!
 *
 * @property int $id ID
 * @property int|null $section_id Section
 * @property float|null $top Top/location on section [cm]
 * @property float|null $extent Extent
 * @property string|null $contact_type Contact Type
 * @property string|null $fault_type Fault Type
 * @property string|null $foliation_type Foliation Type
 * @property string|null $lineation_type Lineation Type
 * @property string|null $other Other
 * @property string|null $deformation_style Deformation style
 * @property string|null $sedimentary_structures Sedimentary structures
 * @property string|null $comment_sedimentary_structures Additional informationon sedimentary structures
 * @property string|null $comment Remarks
 * @property int|null $dipdirection Dipdirection
 * @property int|null $dip Dip
 * @property int|null $curator Curator
 *
 * @property CoreSection $section
 * @property CoreSection $parent
 * @property CoreCore $core
 * @property ProjectHole $hole
 * @property ProjectSite $site
 * @property ProjectExpedition $expedition
 * @property ProjectProgram $program
 */
abstract class BaseGeologyStructure extends \app\models\core\Base
{
    /* [i.e columnName => [displayColumn, relationName],[..]] */
    const MANY_TO_MANY_COLUMNS = [];
    /* [[i.e columnName => displayColumn],[..]] */
    const ONE_TO_MANY_COLUMNS = [];

    const MODULE = 'Geology';
    const SHORT_NAME = 'Structure';

    const NAME_ATTRIBUTE = 'id';
    const PARENT_CLASSNAME = 'CoreSection';
    const ANCESTORS = ['section'=>'CoreSection', 'core'=>'CoreCore', 'hole'=>'ProjectHole', 'site'=>'ProjectSite', 'expedition'=>'ProjectExpedition', 'program'=>'ProjectProgram'];
    const DEFAULT_VALUES = [ 'contact_type'=>'inapplicable',  'fault_type'=>'inapplicable',  'foliation_type'=>'inapplicable',  'lineation_type'=>'inapplicable',  'deformation_style'=>'inapplicable', ];

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();
        $this->contact_type = 'inapplicable';
        $this->fault_type = 'inapplicable';
        $this->foliation_type = 'inapplicable';
        $this->lineation_type = 'inapplicable';
        $this->deformation_style = 'inapplicable';
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
        return 'geology_structure';
    }
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [['sedimentary_structures'],'\app\components\validators\MultipleValuesStringValidator'],
            [['section_id', 'dipdirection', 'dip', 'curator'], 'integer'],
            [['top', 'extent'], 'number'],
            [['contact_type', 'fault_type', 'foliation_type', 'lineation_type', 'deformation_style'], 'string', 'max' => 100],
            [['other'], 'string', 'max' => 255],
            [['sedimentary_structures', 'comment_sedimentary_structures', 'comment'], 'string', 'max' => 500],
            [['section_id'], 'exist', 'skipOnError' => true, 'targetClass' => CoreSection::className(), 'targetAttribute' => ['section_id' => 'id']],
            [['section_id'],'required'],
            [['top'], 'compare', 'operator' => '>=', 'compareValue' => '0', 'type' => 'number'],
            [['extent'], 'compare', 'operator' => '>=', 'compareValue' => '0', 'type' => 'number'],
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
            'top' => Yii::t('app', 'Top/location on section [cm]'),
            'extent' => Yii::t('app', 'Extent'),
            'contact_type' => Yii::t('app', 'Contact Type'),
            'fault_type' => Yii::t('app', 'Fault Type'),
            'foliation_type' => Yii::t('app', 'Foliation Type'),
            'lineation_type' => Yii::t('app', 'Lineation Type'),
            'other' => Yii::t('app', 'Other'),
            'deformation_style' => Yii::t('app', 'Deformation style'),
            'sedimentary_structures' => Yii::t('app', 'Sedimentary structures'),
            'comment_sedimentary_structures' => Yii::t('app', 'Additional informationon sedimentary structures'),
            'comment' => Yii::t('app', 'Remarks'),
            'dipdirection' => Yii::t('app', 'Dipdirection'),
            'dip' => Yii::t('app', 'Dip'),
            'curator' => Yii::t('app', 'Curator'),
        ];
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
            'sedimentary_structures' => function($model) {
                if (!empty($model->sedimentary_structures)) {
                    return explode(';', $model->sedimentary_structures);
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
                        'id' => $model->id
                    ]                ];
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

