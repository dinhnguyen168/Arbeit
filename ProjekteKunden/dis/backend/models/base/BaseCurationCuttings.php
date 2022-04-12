<?php

namespace app\models\base;

use Yii;
use app\models\ProjectHole;
use app\models\ProjectSite;
use app\models\ProjectExpedition;
use app\models\ProjectProgram;
use app\models\ArchiveFile;

/**
 * This is the generated model base class for table "curation_cuttings".
 * DO NOT EDIT THIS CLASS MANUALLY!
 *
 * @property int $id ID
 * @property int|null $hole_id Hole
 * @property string|null $cuttings_combined_id Cuttings Combined ID
 * @property string|null $igsn IGSN
 * @property string|null $curator Curator
 * @property string|null $sampling_datetime Sampling Datetime
 * @property float|null $top_depth Top Depth [mbs]
 * @property float|null $bottom_depth Bottom Depth [mbs]
 * @property float|null $average_depth Average Depth [mbs]
 * @property float|null $drillers_sieve Drillers Sieve [mm]
 * @property string|null $comment_drillers Additional Information
 * @property int|null $sample_weight Sample Weight [g]
 * @property int|null $ratio_rock_clasts Ratio of Rock Clasts in Mud Sample[%]
 * @property float|null $max_diameter_rock_clasts Max Diameter of Rock Clasts [mm]
 * @property string|null $sorting Sorting
 * @property string|null $comment Additional Information
 * @property int|null $fossiles Macro Fossiles
 * @property string|null $comment_fossiles Additional Information
 * @property string|null $minerals Major Mineral Composition
 * @property string|null $inferred_lithology Inferred Lithology
 * @property string|null $color_munsell Color Munsell
 * @property string|null $shape_clasts Shape of Clasts
 * @property string|null $combined_id System Combined ID
 * @property string|null $petrology Petrology of Clasts/Cuttings
 *
 * @property ProjectHole $hole
 * @property ProjectHole $parent
 * @property ProjectSite $site
 * @property ProjectExpedition $expedition
 * @property ProjectProgram $program
 */
abstract class BaseCurationCuttings extends \app\models\core\Base
{
    /* [i.e columnName => [displayColumn, relationName],[..]] */
    const MANY_TO_MANY_COLUMNS = [];
    /* [[i.e columnName => displayColumn],[..]] */
    const ONE_TO_MANY_COLUMNS = [];

    const MODULE = 'Curation';
    const SHORT_NAME = 'Cuttings';

    const NAME_ATTRIBUTE = 'id';
    const PARENT_CLASSNAME = 'ProjectHole';
    const ANCESTORS = ['hole'=>'ProjectHole', 'site'=>'ProjectSite', 'expedition'=>'ProjectExpedition', 'program'=>'ProjectProgram'];
    const DEFAULT_VALUES = [ 'igsn'=>'U', ];

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
        return 'curation_cuttings';
    }
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [['sorting', 'petrology', 'minerals', 'inferred_lithology', 'color_munsell', 'shape_clasts'],'\app\components\validators\MultipleValuesStringValidator'],
            [['hole_id', 'sample_weight', 'ratio_rock_clasts', 'fossiles'], 'integer'],
            [['sampling_datetime'], 'safe'],
            [['top_depth', 'bottom_depth', 'average_depth', 'drillers_sieve', 'max_diameter_rock_clasts'], 'number'],
            [['cuttings_combined_id', 'igsn', 'curator', 'comment_drillers', 'sorting', 'comment', 'comment_fossiles', 'minerals', 'inferred_lithology', 'color_munsell', 'shape_clasts', 'combined_id', 'petrology'], 'string', 'max' => 255],
            [['hole_id'], 'exist', 'skipOnError' => true, 'targetClass' => ProjectHole::className(), 'targetAttribute' => ['hole_id' => 'id']],
            [['hole_id'],'required'],
            [['fossiles'],'boolean'],
            [['sampling_datetime'],'\app\components\validators\DateTimeValidator'],
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
            'cuttings_combined_id' => Yii::t('app', 'Cuttings Combined ID'),
            'igsn' => Yii::t('app', 'IGSN'),
            'curator' => Yii::t('app', 'Curator'),
            'sampling_datetime' => Yii::t('app', 'Sampling Datetime'),
            'top_depth' => Yii::t('app', 'Top Depth [mbs]'),
            'bottom_depth' => Yii::t('app', 'Bottom Depth [mbs]'),
            'average_depth' => Yii::t('app', 'Average Depth [mbs]'),
            'drillers_sieve' => Yii::t('app', 'Drillers Sieve [mm]'),
            'comment_drillers' => Yii::t('app', 'Additional Information'),
            'sample_weight' => Yii::t('app', 'Sample Weight [g]'),
            'ratio_rock_clasts' => Yii::t('app', 'Ratio of Rock Clasts in Mud Sample[%]'),
            'max_diameter_rock_clasts' => Yii::t('app', 'Max Diameter of Rock Clasts [mm]'),
            'sorting' => Yii::t('app', 'Sorting'),
            'comment' => Yii::t('app', 'Additional Information'),
            'fossiles' => Yii::t('app', 'Macro Fossiles'),
            'comment_fossiles' => Yii::t('app', 'Additional Information'),
            'minerals' => Yii::t('app', 'Major Mineral Composition'),
            'inferred_lithology' => Yii::t('app', 'Inferred Lithology'),
            'color_munsell' => Yii::t('app', 'Color Munsell'),
            'shape_clasts' => Yii::t('app', 'Shape of Clasts'),
            'combined_id' => Yii::t('app', 'System Combined ID'),
            'petrology' => Yii::t('app', 'Petrology of Clasts/Cuttings'),
        ];
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
            'sorting' => function($model) {
                if (!empty($model->sorting)) {
                    return explode(';', $model->sorting);
                } else {
                    return [];
                }
            },
            'petrology' => function($model) {
                if (!empty($model->petrology)) {
                    return explode(';', $model->petrology);
                } else {
                    return [];
                }
            },
            'minerals' => function($model) {
                if (!empty($model->minerals)) {
                    return explode(';', $model->minerals);
                } else {
                    return [];
                }
            },
            'inferred_lithology' => function($model) {
                if (!empty($model->inferred_lithology)) {
                    return explode(';', $model->inferred_lithology);
                } else {
                    return [];
                }
            },
            'color_munsell' => function($model) {
                if (!empty($model->color_munsell)) {
                    return explode(';', $model->color_munsell);
                } else {
                    return [];
                }
            },
            'shape_clasts' => function($model) {
                if (!empty($model->shape_clasts)) {
                    return explode(';', $model->shape_clasts);
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
                        'id' => $model->id
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

        $this->cuttings_combined_id = $this->parent->combined_id . '_CUT_' . $this->top_depth . '-' . $this->bottom_depth;
        return true;
    }


    /**
    * {@inheritdoc}
    */
    public function beforeDelete()
    {
        return parent::beforeDelete(); // TODO: Change the autogenerated stub
    }


}

