<?php
/**
 * This is the template for generating the model class of a specified table.
 */

/* @var $this yii\web\View */
/* @var $generator yii\gii\generators\model\Generator */
/* @var $tableName string full table name */
/* @var $className string class name */
/* @var $queryClassName string query class name */
/* @var $tableSchema yii\db\TableSchema */
/* @var $properties array list of properties (property => [type, name. comment]) */
/* @var $labels string[] list of attribute labels (name => label) */
/* @var $behaviors string[] list of behaviors */
/* @var $rules string[] list of validation rules */
/* @var $relations array list of relations (name => relation declaration) */
/* @var $multiValueStringColumns array of multi value string columns names */
/* @var $pseudoColumns array of pseudo columns definitions */
/* @var $defaultValues array of default values */
/* @var $defaultValuesWithFunctions array of default values that contain function calls */
/* @var $module Module name */
/* @var $shortName Short name of the class */
/* @var $parentClassName Name of parent class */
/* @var $specializationsSourceCode Additional class properties added by file in directory "specialisationsSourceCode" */
/* @var $ancestorFormFilters */
/* @var $nameAttribute Attribute that is unique withing the same parent and used to build the combined_id */
/* @var $ancestorModels Array of models: the parent hierarchy of models */
/* @var $relatedTable string name of related table */

?>

/**
 * This is the generated model base class for table "<?= $generator->generateTableName($tableName) ?>".
 * DO NOT EDIT THIS CLASS MANUALLY!
 *
<?php foreach ($properties as $property => $data): ?>
 * @property <?= "{$data['type']} \${$property}"  . ($data['comment'] ? ' ' . strtr($data['comment'], ["\n" => ' ']) : '') . "\n" ?>
<?php endforeach; ?>
<?php if (!empty($relations)): ?>
 *
<?php foreach ($relations as $name => $relation): ?>
 * @property \<?= $generator->ns . "\\" . $relation[1] . ($relation[2] ? '[]' : '') . ' $' . lcfirst($name) . "\n" ?>
<?php endforeach; ?>
<?php endif; ?>
 */
class <?= $generator->baseClassPrefix . $className ?> extends <?= '\\' . ltrim($generator->baseClass, '\\') . "\n" ?>
{
    const MODULE = <?= $generator->generateString($module) . ";\n"; ?>
    const SHORT_NAME = <?= $generator->generateString($shortName) . ";\n"; ?>

    const NAME_ATTRIBUTE = <?= $generator->generateString($nameAttribute) . ";\n"; ?>
    const PARENT_CLASSNAME = <?= $generator->generateString($parentClassName). ";\n"; ?>
    const ANCESTORS = [<?php for ($i=0; $i < sizeof(array_values($ancestorModels)); $i++): ?><?php $ancestorModel = array_values($ancestorModels)[$i] ?><?= $generator->generateString(lcfirst($ancestorModel->name)) . "=>" . $generator->generateString($ancestorModel->module . $ancestorModel->name); ?><?php if ($i < sizeof(array_values($ancestorModels))-1): ?>, <?php endif; ?><?php endfor; ?>];
    const DEFAULT_VALUES = [<?php foreach ($defaultValues as $name => $value): ?> '<?= $name ?>'=><?= $value ?>, <?php endforeach; ?>];

<?php if (sizeof($defaultValues) + sizeof($defaultValuesWithFunctions) > 0): ?>
    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();
<?php foreach ($defaultValues as $name => $value): ?>
<?php // Default-Value of column "igsn" contains the objectTag for the IgsnBehavior and must be ignored here! ?>
<?php if ($name != "igsn"): ?>
        $this-><?= $name ?> = <?= $value ?><?= ";\n" ?>
<?php endif; ?>
<?php endforeach; ?>
<?php foreach ($defaultValuesWithFunctions as $name => $value): ?>
        try {
            $this-><?= $name ?> = <?= $value ?><?= ";\n" ?>
        }
        catch (\Exception $e) {
            \Yii::error("<?= $generator->baseClassPrefix . $className ?>::init() Could not set default value of column '<?= $name ?>' to value: <?= $value ?>; " . $e->getMessage());
        }
<?php endforeach; ?>
    }
<?php endif; ?>

    public static function getFormFilters() {
<?php if (sizeof($ancestorFormFilters) == 0): ?>
        return [];
<?php else: ?>
        return [
            <?= implode(",\n            ", $ancestorFormFilters); ?><?= "\n" ?>
        ];
<?php endif; ?>
    }

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '<?= $generator->generateTableName($tableName) ?>';
    }
<?php if ($generator->db !== 'db'): ?>

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
    return Yii::$app->get('<?= $generator->db ?>');
    }
<?php endif; ?>
<?php if (is_array($behaviors) && count($behaviors) > 0): ?>
    /**
    * {@inheritdoc}
    */
    public function behaviors()
    {
        return array_merge(parent::behaviors(), [<?= empty($behaviors) ? '' : ("\n            " . implode(",\n            ", $behaviors) . ",\n        ") ?>]);
    }
<?php endif; ?>
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return array_merge(parent::rules(), [<?= empty($rules) ? '' : ("\n            " . implode(",\n            ", $rules) . ",\n        ") ?>]);
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
<?php foreach ($labels as $name => $label): ?>
            <?= "'$name' => Yii::t('app', " . $generator->generateString($label) . "),\n" ?>
<?php endforeach; ?>
        ];
    }


<?php foreach ($pseudoColumns as $key => $pseudoColumn): ?>
    public function get<?=ucwords($key)?>()
    {
<?php if (str_contains($pseudoColumn, '$model->')): ?>
        $model = $this;
<?php endif; ?>
        return <?= $pseudoColumn . ";\n"?>
    }
<?php endforeach; ?>

<?php foreach ($relations as $name => $relation): ?>
    /**
     * @return \yii\db\ActiveQuery
     */
    public function get<?= $name ?>()
    {
        <?= $relation[0] . "\n" ?>
    }
<?php endforeach; ?>

    public function fields()
    {
        return array_merge(parent::fields(), [
<?php foreach ($ancestorModels as $key => $ancestorModel): ?>
<?php if ($className !== $ancestorModel->fullName): ?>
        <?= $generator->generateString(lcfirst($ancestorModel->name) . "_id") ?> => function($model) { return $this-><?= lcfirst($ancestorModel->name) ?>->id; },
<?php endif; ?>
<?php endforeach; ?>
<?php foreach ($pseudoColumns as $key => $pseudoColumn): ?>
            <?= $generator->generateString($key) ?> => function($model) { return <?= $pseudoColumn ?>; },
<?php endforeach; ?>
<?php foreach ($multiValueStringColumns as $key => $columnName): ?>
            <?= $generator->generateString($columnName) ?> => function($model) {
                if (!empty($model-><?=$columnName?>)) {
                    return explode(';', $model-><?=$columnName?>);
                } else {
                    return [];
                }
            },
<?php endforeach; ?>
        ]);
    }
<?php if (sizeof($calculateProperties) > 0): ?>
    /**
     * {@inheritdoc}
     */
    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

    <?php foreach ($calculateProperties as $name => $calculated): ?>
    $this-><?=$name ?> = <?= $calculated ?>;<?= "\n";?>
    <?php endforeach; ?>
    return true;
    }
<?php endif; ?>
<?= $specializationsSourceCode ?>

    const GENERATED = <?= time() . ";\n"; ?>

}
