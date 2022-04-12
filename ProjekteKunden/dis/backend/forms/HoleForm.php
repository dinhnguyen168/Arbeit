<?php

namespace app\forms;

use Yii;

/**
* @inheritdoc
*/
class HoleForm extends \app\models\ProjectHole
{

    const FORM_NAME = 'hole';

    public function scenarios() {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_DEFAULT] = ['hole', 'combined_id', 'hole_name', 'elevation', 'comment_elevation', 'drillers_reference_height', 'comment_drillers_reference', 'depth_water', 'start_date', 'end_date', 'comment', 'longitude', 'latitude', 'coordinate_x', 'coordinate_y', 'spatial_reference_system', 'comment_spatial_reference', 'drilling_method', 'direction', 'inclination', 'igsn', 'comment_identifier', 'methods_in_hole', 'gear', 'platform_type', 'platform_operator', 'platform_name', 'platform_manager', 'platform_description', 'repository_name', 'comment_repository', 'moratorium_start', 'moratorium_end', 'site_id'];
        return $scenarios;
    }

    /**
    * {@inheritdoc}
    */
    public function attributeLabels()
    {
      return [
        'hole' => Yii::t('app', 'Hole Identifier'),
            'combined_id' => Yii::t('app', 'Combined ID '),
            'hole_name' => Yii::t('app', 'Name of Hole '),
            'elevation' => Yii::t('app', 'Elevation of Permanent Depth Reference [m]'),
            'comment_elevation' => Yii::t('app', 'Description of Permanent Depth Reference'),
            'drillers_reference_height' => Yii::t('app', 'Height of Driller\'s Reference Point [m]'),
            'comment_drillers_reference' => Yii::t('app', 'Description of Driller\'s Depth Reference Point'),
            'depth_water' => Yii::t('app', 'Water Depth [m]'),
            'start_date' => Yii::t('app', 'Start Date'),
            'end_date' => Yii::t('app', 'End Date'),
            'comment' => Yii::t('app', 'Additional Information'),
            'longitude' => Yii::t('app', 'Decimal Longitude [WGS84]'),
            'latitude' => Yii::t('app', 'Decimal Latitude [WGS84]'),
            'coordinate_x' => Yii::t('app', 'Coordinate X'),
            'coordinate_y' => Yii::t('app', 'Coordinate Y'),
            'spatial_reference_system' => Yii::t('app', 'Spatial Reference System (SRS)'),
            'comment_spatial_reference' => Yii::t('app', 'Additional Information SRS'),
            'drilling_method' => Yii::t('app', 'Applied Drilling Method'),
            'depth_drilled' => Yii::t('app', 'Total Drilled Depth Below Surface [mbs]'),
            'total_cored_length' => Yii::t('app', 'Total Cored Length [m]'),
            'total_core_recovery' => Yii::t('app', 'Total Core Recovery [%]'),
            'direction' => Yii::t('app', 'Compass Direction of Inclination'),
            'inclination' => Yii::t('app', 'Inclination [°]'),
            'igsn' => Yii::t('app', 'IGSN'),
            'comment_identifier' => Yii::t('app', 'Additional Information'),
            'methods_in_hole' => Yii::t('app', 'Measurements and Tests in Borehole'),
            'gear' => Yii::t('app', 'Equipment  - other than Drilling'),
            'platform_type' => Yii::t('app', 'Platform Type'),
            'platform_operator' => Yii::t('app', 'Platform Operator'),
            'platform_name' => Yii::t('app', 'Name of Platform'),
            'platform_manager' => Yii::t('app', 'Platform Manager'),
            'platform_description' => Yii::t('app', 'Description of Platform'),
            'repository_name' => Yii::t('app', 'Name of Repository / Repositories'),
            'comment_repository' => Yii::t('app', 'Additional Information'),
            'moratorium_start' => Yii::t('app', 'Start Date of Moratorium'),
            'moratorium_end' => Yii::t('app', 'End Date of Moratorium '),
          ];
    }

}
