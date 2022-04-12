<?php

namespace app\models\base;

use Yii;
use app\models\ArchiveFile;
use app\models\CoreCore;
use app\models\CurationCorebox;
use app\models\CurationCuttings;
use app\models\DrillingDailyDrillersReport;
use app\models\DrillingDrillerReport;
use app\models\GeophysicsGeopyhsics;
use app\models\ProjectSite;
use app\models\ProjectExpedition;
use app\models\ProjectProgram;

/**
 * This is the generated model base class for table "project_hole".
 * DO NOT EDIT THIS CLASS MANUALLY!
 *
 * @property int $id SKEY
 * @property int|null $site_id Site ID
 * @property string|null $combined_id Combined ID
 * @property string|null $hole Hole Code/Identfier
 * @property string|null $hole_name Borehole Name
 * @property string|null $igsn IGSN
 * @property string|null $drilling_method Drilling method
 * @property float|null $depth_water Water Depth [m]
 * @property float|null $drillers_reference_height Height of Driller's Reference Point [m]
 * @property float|null $latitude Decimal Latitude [WGS84]
 * @property float|null $longitude Decimal Longitude [WGS 84]
 * @property float|null $coordinate_x Coordinate X
 * @property float|null $coordinate_y Coordinate Y
 * @property float|null $elevation Elevation [m]
 * @property string|null $spatial_reference_system Spatial reference system (SRS)
 * @property string|null $direction Compass Direction of Inclination
 * @property string|null $inclination Borehole inclination [degree]
 * @property string|null $platform_name Platform Name
 * @property string|null $platform_operator Platform Operator
 * @property string|null $platform_type Platform type
 * @property string|null $platform_description Description of Drilling platform
 * @property string|null $methods_in_hole Measurements in Borehole
 * @property string|null $gear Gear/Equipment
 * @property string|null $start_date Start date
 * @property string|null $end_date End date
 * @property string|null $moratorium_start Start Date of Moratorium 
 * @property string|null $moratorium_end End Date of Moratorium
 * @property string|null $comment Additional information
 * @property string|null $comment_identifier Additional information
 * @property string|null $comment_drillers_reference Description of drillers' reference
 * @property string|null $comment_elevation Description of Permanent Depth Reference
 * @property string|null $comment_spatial_reference Additional information
 * @property string|null $comment_repository Additional information
 * @property string|null $platform_manager Platform Manager
 * @property string|null $repository_name Name of Repositories
 *
 * @property ArchiveFile[] $archiveFiles
 * @property CoreCore[] $coreCores
 * @property CurationCorebox[] $curationCoreboxes
 * @property CurationCuttings[] $curationCuttings
 * @property DrillingDailyDrillersReport[] $drillingDailyDrillersReports
 * @property DrillingDrillerReport[] $drillingDrillerReports
 * @property GeophysicsGeopyhsics[] $geophysicsGeopyhsics
 * @property ProjectSite $site
 * @property ProjectSite $parent
 * @property ProjectExpedition $expedition
 * @property ProjectProgram $program
 */
abstract class BaseProjectHole extends \app\models\core\Base
{
    /* [i.e columnName => [displayColumn, relationName],[..]] */
    const MANY_TO_MANY_COLUMNS = [];
    /* [[i.e columnName => displayColumn],[..]] */
    const ONE_TO_MANY_COLUMNS = [];

    const MODULE = 'Project';
    const SHORT_NAME = 'Hole';

    const NAME_ATTRIBUTE = 'hole';
    const PARENT_CLASSNAME = 'ProjectSite';
    const ANCESTORS = ['site'=>'ProjectSite', 'expedition'=>'ProjectExpedition', 'program'=>'ProjectProgram'];
    const DEFAULT_VALUES = [ 'igsn'=>'H',  'depth_water'=>0, ];

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();
        $this->depth_water = 0;
    }

    public static function getFormFilters() {
        return [
            "expedition" => ["model" => "ProjectExpedition", "value" => "id", "text" => "exp_acronym", "ref" => "expedition_id"],
            "site" => ["model" => "ProjectSite", "value" => "id", "text" => "site", "ref" => "site_id", "require" => ["value" => "expedition", "as" => "expedition_id"]]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'project_hole';
    }
    /**
    * {@inheritdoc}
    */
    public function behaviors()
    {
        return array_merge(parent::behaviors(), [
            ['class' => 'app\behaviors\template\UniqueCombinationAutoIncrementBehavior', 'searchFields' => ['site_id'], 'fieldToFill' => 'hole', 'useAlphabet' => true],
        ]);
    }
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [['drilling_method', 'methods_in_hole', 'gear', 'repository_name'],'\app\components\validators\MultipleValuesStringValidator'],
            [['site_id'], 'integer'],
            [['depth_water', 'drillers_reference_height', 'latitude', 'longitude', 'coordinate_x', 'coordinate_y', 'elevation'], 'number'],
            [['start_date', 'end_date', 'moratorium_start', 'moratorium_end'], 'safe'],
            [['combined_id', 'hole_name', 'drilling_method', 'spatial_reference_system', 'direction', 'inclination', 'platform_name', 'platform_operator', 'platform_type', 'platform_description', 'methods_in_hole', 'gear', 'comment', 'comment_identifier', 'comment_elevation', 'comment_spatial_reference', 'comment_repository', 'platform_manager', 'repository_name'], 'string', 'max' => 255],
            [['hole'], 'string', 'max' => 64],
            [['igsn'], 'string', 'max' => 32],
            [['comment_drillers_reference'], 'string', 'max' => 1000],
            [['site_id', 'hole'], 'unique', 'targetAttribute' => ['site_id', 'hole']],
            [['site_id'], 'exist', 'skipOnError' => true, 'targetClass' => ProjectSite::className(), 'targetAttribute' => ['site_id' => 'id']],
            [['site_id', 'start_date'],'required'],
            [['start_date', 'end_date', 'moratorium_start', 'moratorium_end'],'\app\components\validators\DateValidator'],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'SKEY'),
            'site_id' => Yii::t('app', 'Site ID'),
            'combined_id' => Yii::t('app', 'Combined ID'),
            'hole' => Yii::t('app', 'Hole Code/Identfier'),
            'hole_name' => Yii::t('app', 'Borehole Name'),
            'igsn' => Yii::t('app', 'IGSN'),
            'drilling_method' => Yii::t('app', 'Drilling method'),
            'depth_water' => Yii::t('app', 'Water Depth [m]'),
            'drillers_reference_height' => Yii::t('app', 'Height of Driller\'s Reference Point [m]'),
            'latitude' => Yii::t('app', 'Decimal Latitude [WGS84]'),
            'longitude' => Yii::t('app', 'Decimal Longitude [WGS 84]'),
            'coordinate_x' => Yii::t('app', 'Coordinate X'),
            'coordinate_y' => Yii::t('app', 'Coordinate Y'),
            'elevation' => Yii::t('app', 'Elevation [m]'),
            'spatial_reference_system' => Yii::t('app', 'Spatial reference system (SRS)'),
            'direction' => Yii::t('app', 'Compass Direction of Inclination'),
            'inclination' => Yii::t('app', 'Borehole inclination [degree]'),
            'platform_name' => Yii::t('app', 'Platform Name'),
            'platform_operator' => Yii::t('app', 'Platform Operator'),
            'platform_type' => Yii::t('app', 'Platform type'),
            'platform_description' => Yii::t('app', 'Description of Drilling platform'),
            'methods_in_hole' => Yii::t('app', 'Measurements in Borehole'),
            'gear' => Yii::t('app', 'Gear/Equipment'),
            'start_date' => Yii::t('app', 'Start date'),
            'end_date' => Yii::t('app', 'End date'),
            'moratorium_start' => Yii::t('app', 'Start Date of Moratorium '),
            'moratorium_end' => Yii::t('app', 'End Date of Moratorium'),
            'comment' => Yii::t('app', 'Additional information'),
            'comment_identifier' => Yii::t('app', 'Additional information'),
            'comment_drillers_reference' => Yii::t('app', 'Description of drillers\' reference'),
            'comment_elevation' => Yii::t('app', 'Description of Permanent Depth Reference'),
            'comment_spatial_reference' => Yii::t('app', 'Additional information'),
            'comment_repository' => Yii::t('app', 'Additional information'),
            'platform_manager' => Yii::t('app', 'Platform Manager'),
            'repository_name' => Yii::t('app', 'Name of Repositories'),
            'depth_drilled' => Yii::t('app', 'Total drilled depth [mbs/mbsf]'),
            'total_cored_length' => Yii::t('app', 'Total Cored Length [m]'),
            'total_core_recovery' => Yii::t('app', 'Total core recovery [%]'),
        ];
    }


    public function getDepth_drilled()
    {
        return empty($this->coreCores) ? '0' : max(array_column($this->coreCores, 'drillers_bottom_depth'));
    }
    public function getTotal_cored_length()
    {
        return empty($this->coreCores) ? '0' : round(array_sum(array_column($this->coreCores, 'drilled_length')), 2);
    }
    public function getTotal_core_recovery()
    {
        return round((is_null(array_sum(array_column($this->coreCores, 'drilled_length')))  || array_sum(array_column($this->coreCores, 'drilled_length')) == 0) ? '0' : array_sum(array_column($this->coreCores, 'core_recovery')) / array_sum(array_column($this->coreCores, 'drilled_length')) * 100, 2);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getArchiveFiles()
    {
        return $this->hasMany(ArchiveFile::className(), ['hole_id' => 'id']);
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCoreCores()
    {
        return $this->hasMany(CoreCore::className(), ['hole_id' => 'id']);
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCurationCoreboxes()
    {
        return $this->hasMany(CurationCorebox::className(), ['hole_id' => 'id']);
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCurationCuttings()
    {
        return $this->hasMany(CurationCuttings::className(), ['hole_id' => 'id']);
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDrillingDailyDrillersReports()
    {
        return $this->hasMany(DrillingDailyDrillersReport::className(), ['hole_id' => 'id']);
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDrillingDrillerReports()
    {
        return $this->hasMany(DrillingDrillerReport::className(), ['hole_id' => 'id']);
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGeophysicsGeopyhsics()
    {
        return $this->hasMany(GeophysicsGeopyhsics::className(), ['hole_id' => 'id']);
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSite()
    {
        return $this->hasOne(ProjectSite::className(), ['id' => 'site_id']);
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getParent()
    {
        return $this->getSite();
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getExpedition()
    {
        return $this->parent->parent;
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProgram()
    {
        return $this->parent->parent->parent;
    }

    public function fields()
    {
        $fields = [
            'site_id' => function($model) { return $model->injectUuid($this->site); },
            'expedition_id' => function($model) { return $model->injectUuid($this->expedition); },
            'program_id' => function($model) { return $model->injectUuid($this->program); },
            'depth_drilled' => function($model) { return empty($this->coreCores) ? '0' : max(array_column($this->coreCores, 'drillers_bottom_depth')); },
            'total_cored_length' => function($model) { return empty($this->coreCores) ? '0' : round(array_sum(array_column($this->coreCores, 'drilled_length')), 2); },
            'total_core_recovery' => function($model) { return round((is_null(array_sum(array_column($this->coreCores, 'drilled_length')))  || array_sum(array_column($this->coreCores, 'drilled_length')) == 0) ? '0' : array_sum(array_column($this->coreCores, 'core_recovery')) / array_sum(array_column($this->coreCores, 'drilled_length')) * 100, 2); },
            'drilling_method' => function($model) {
                if (!empty($model->drilling_method)) {
                    return explode(';', $model->drilling_method);
                } else {
                    return [];
                }
            },
            'methods_in_hole' => function($model) {
                if (!empty($model->methods_in_hole)) {
                    return explode(';', $model->methods_in_hole);
                } else {
                    return [];
                }
            },
            'gear' => function($model) {
                if (!empty($model->gear)) {
                    return explode(';', $model->gear);
                } else {
                    return [];
                }
            },
            'repository_name' => function($model) {
                if (!empty($model->repository_name)) {
                    return explode(';', $model->repository_name);
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
                        'hole' => $model->id
                    ],
                    'files' => ArchiveFile::find()->andWhere([
                        'hole_id' => $model->id
                    ])->all()
                ];
            };
        }
        return array_merge(parent::fields(), $fields);
    }

    public function load($data, $formName = null)
    {
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

