<?php

namespace app\models\base;

use Yii;
use app\models\ProjectExpedition;
use app\models\ProjectProgram;
use app\models\ArchiveFile;

/**
 * This is the generated model base class for table "curation_sample_request".
 * DO NOT EDIT THIS CLASS MANUALLY!
 *
 * @property int $id ID
 * @property int|null $expedition_id Expedition
 * @property string|null $request_combined_id Request Combined ID
 * @property int|null $request_no Request Number
 * @property string|null $request_part Request Part
 * @property string|null $project_phase Project Phase
 * @property string|null $scientist_1 Scientist 1
 * @property string|null $scientist_2 Scientist 2
 * @property string|null $scientist_3 Scientist 3
 * @property string|null $purpose Purpose / Usage
 * @property int|null $destructive Destructive Analysis?
 * @property string|null $sample_material Sample Material Requested
 * @property int|null $split_fraction_requested Requested Fraction of Split [%]
 * @property int|null $number_samples Number of Samples
 * @property float|null $sample_size Size of Sample
 * @property string|null $sample_size_unit Unit of Sample Size
 * @property string|null $curator Curator
 * @property string|null $date_submission Date of Submission
 * @property string|null $date_approval Date of Approval
 * @property string|null $date_completion Request Completed
 * @property string|null $approved_by Approved By
 * @property string|null $comment Additional Information
 * @property string|null $comment_administration Additional Information
 * @property string|null $hole_combined_id Hole Combined ID
 * @property string|null $combined_id System Combined ID
 * @property string|null $request_combined_name_id Request Combined Name Id
 *
 * @property ProjectExpedition $expedition
 * @property ProjectExpedition $parent
 * @property ProjectProgram $program
 */
abstract class BaseCurationSampleRequest extends \app\models\core\Base
{
    /* [i.e columnName => [displayColumn, relationName],[..]] */
    const MANY_TO_MANY_COLUMNS = [];
    /* [[i.e columnName => displayColumn],[..]] */
    const ONE_TO_MANY_COLUMNS = [];

    const MODULE = 'Curation';
    const SHORT_NAME = 'SampleRequest';

    const NAME_ATTRIBUTE = 'id';
    const PARENT_CLASSNAME = 'ProjectExpedition';
    const ANCESTORS = ['expedition'=>'ProjectExpedition', 'program'=>'ProjectProgram'];
    const DEFAULT_VALUES = [];


    public static function getFormFilters() {
        return [
            "expedition" => ["model" => "ProjectExpedition", "value" => "id", "text" => "exp_acronym", "ref" => "expedition_id"]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'curation_sample_request';
    }
    /**
    * {@inheritdoc}
    */
    public function behaviors()
    {
        return array_merge(parent::behaviors(), [
            ['class' => 'app\behaviors\template\UniqueCombinationAutoIncrementBehavior', 'searchFields' => ['expedition_id'], 'fieldToFill' => 'request_no', 'useAlphabet' => false],
        ]);
    }
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [['hole_combined_id', 'purpose'],'\app\components\validators\MultipleValuesStringValidator'],
            [['expedition_id', 'request_no', 'destructive', 'split_fraction_requested', 'number_samples'], 'integer'],
            [['sample_size'], 'number'],
            [['date_submission', 'date_approval', 'date_completion'], 'safe'],
            [['request_combined_id', 'project_phase', 'scientist_1', 'scientist_2', 'scientist_3', 'purpose', 'sample_material', 'curator', 'approved_by', 'comment', 'comment_administration', 'hole_combined_id', 'combined_id', 'request_combined_name_id'], 'string', 'max' => 255],
            [['request_part'], 'string', 'max' => 10],
            [['sample_size_unit'], 'string', 'max' => 50],
            [['expedition_id'], 'exist', 'skipOnError' => true, 'targetClass' => ProjectExpedition::className(), 'targetAttribute' => ['expedition_id' => 'id']],
            [['expedition_id'],'required'],
            [['destructive'],'boolean'],
            [['date_submission', 'date_approval', 'date_completion'],'\app\components\validators\DateValidator'],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'expedition_id' => Yii::t('app', 'Expedition'),
            'request_combined_id' => Yii::t('app', 'Request Combined ID'),
            'request_no' => Yii::t('app', 'Request Number'),
            'request_part' => Yii::t('app', 'Request Part'),
            'project_phase' => Yii::t('app', 'Project Phase'),
            'scientist_1' => Yii::t('app', 'Scientist 1'),
            'scientist_2' => Yii::t('app', 'Scientist 2'),
            'scientist_3' => Yii::t('app', 'Scientist 3'),
            'purpose' => Yii::t('app', 'Purpose / Usage'),
            'destructive' => Yii::t('app', 'Destructive Analysis?'),
            'sample_material' => Yii::t('app', 'Sample Material Requested'),
            'split_fraction_requested' => Yii::t('app', 'Requested Fraction of Split [%]'),
            'number_samples' => Yii::t('app', 'Number of Samples'),
            'sample_size' => Yii::t('app', 'Size of Sample'),
            'sample_size_unit' => Yii::t('app', 'Unit of Sample Size'),
            'curator' => Yii::t('app', 'Curator'),
            'date_submission' => Yii::t('app', 'Date of Submission'),
            'date_approval' => Yii::t('app', 'Date of Approval'),
            'date_completion' => Yii::t('app', 'Request Completed'),
            'approved_by' => Yii::t('app', 'Approved By'),
            'comment' => Yii::t('app', 'Additional Information'),
            'comment_administration' => Yii::t('app', 'Additional Information'),
            'hole_combined_id' => Yii::t('app', 'Hole Combined ID'),
            'combined_id' => Yii::t('app', 'System Combined ID'),
            'request_combined_name_id' => Yii::t('app', 'Request Combined Name Id'),
            'exp_acronym' => Yii::t('app', 'Expedition Acronym'),
        ];
    }


    public function getExp_acronym()
    {
        return ($this->parent ? $this->parent->exp_acronym : null);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getExpedition()
    {
        return $this->hasOne(ProjectExpedition::className(), ['id' => 'expedition_id']);
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getParent()
    {
        return $this->getExpedition();
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProgram()
    {
        return $this->parent->parent;
    }

    public function fields()
    {
        $fields = [
            'expedition_id' => function($model) { return $model->injectUuid($this->expedition); },
            'program_id' => function($model) { return $model->injectUuid($this->program); },
            'exp_acronym' => function($model) { return ($this->parent ? $this->parent->exp_acronym : null); },
            'hole_combined_id' => function($model) {
                if (!empty($model->hole_combined_id)) {
                    return explode(';', $model->hole_combined_id);
                } else {
                    return [];
                }
            },
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
                        'id' => $model->id
                    ],
                    'files' => ArchiveFile::find()->andWhere([
                        'sample_request_id' => $model->id
                    ])->all()
                ];
            };
        }
        return array_merge(parent::fields(), $fields);
    }

    public function load($data, $formName = null)
    {
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
        if ($combinedIdField == 'request_combined_id')
            $combinedId = $this->getParentCombinedId($combinedIdField) .
                          '_SR-' . $this->request_no . (is_null($this->request_part) ? '' : '-' . $this->request_part);
        else
            $combinedId = parent::calculateCombinedId ($combinedIdField);
        return $combinedId;
    }

    /**
     * @inheritDoc
     * ParentCombinedId for "request_combined_id" is based on expedition
     */
    public function getParentCombinedId ($combinedIdField = "combined_id") {
        $parentCombinedId = "";
        if ($combinedIdField == 'request_combined_id') {
            $parent = $this->expedition;
            if ($parent) {
                if ($parent->hasAttribute('expedition'))
                    $parentCombinedId = "" . $parent->expedition;
                else {
                    $attribute = $parent::NAME_ATTRIBUTE;
                    $parentCombinedId = "" . $parent->{$attribute};
                }
            }
        }
        else
            $parentCombinedId = parent::getParentCombinedId($combinedIdField);
        return $parentCombinedId;
    }
}

