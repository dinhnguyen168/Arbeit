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
/* @var $manyToManyColumns array of many-To-Many columns */
/* @var $oneToManyColumns array of one-To-Many columns */
/* @var $pseudoColumns array of pseudo columns definitions */
/* @var $defaultValues array of default values */
/* @var $defaultValuesWithFunctions array of default values that contain function calls */
/* @var $module String module name */
/* @var $templateName String name */
/* @var $shortName String Short name of the class */
/* @var $parentClassName String Name of parent class */
/* @var $specializationsSourceCode string|string[] Additional class properties added by file in directory "specialisationsSourceCode" */
/* @var $ancestorFormFilters */
/* @var $archiveFileFiltersNames */
/* @var $nameAttribute Attribute that is unique withing the same parent and used to build the combined_id */
/* @var $ancestorModels Array of models: the parent hierarchy of models */
/* @var $connectionModelsData Array of connection model*/
/* @var $uses array models names */
/* @var $filesColumn null|string Name of the related column in ArchiveFiles or null*/


use yii\helpers\Inflector;

echo "<?php\n";
?>

namespace <?= $generator->baseNs ?>;

use Yii;
<?php foreach ($uses as $use): ?>
use <?= $generator->ns . "\\" . $use . ";\n" ?>
<?php endforeach; ?>
<?php if(count($archiveFileFiltersNames) > 0 && !in_array('ArchiveFile', $uses) ): ?>
use app\models\ArchiveFile;
<?php endif ?>

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
 * @property <?= $relation[1] . ($relation[2] ? '[]' : '') . ' $' . lcfirst($name) . "\n" ?>
<?php endforeach; ?>
<?php endif; ?>
 */
abstract class <?= $generator->baseClassPrefix . $className ?> extends <?= '\\' . ltrim($generator->baseClass, '\\') . "\n" ?>
{
<?php $manyToManyColumnsNames = []; ?>
<?php foreach ($manyToManyColumns as $key => $value): ?>
    public $<?= $key ?>;
<?php $manyToManyColumnsNames[] = $key;?>
<?php endforeach; ?>
<?php $oneToManyColumnsNames = []; ?>
<?php foreach ($oneToManyColumns as $key => $value): ?>
<?php $oneToManyColumnsNames[] = $key;?>
<?php endforeach; ?>
    /* [i.e columnName => [displayColumn, relationName],[..]] */
    const MANY_TO_MANY_COLUMNS = [<?php foreach ($manyToManyColumns as $name => $value):?>'<?= $name ?>' => ['<?= $value["displayColumn"]?>', '<?= $value["relationName"]?>'],<?php endforeach;?>];
    /* [[i.e columnName => displayColumn],[..]] */
    const ONE_TO_MANY_COLUMNS = [<?php foreach ($oneToManyColumns as $name => $value):?>'<?= $name ?>' => ['<?= $value["displayColumn"]?>', '<?= $value["relationName"]?>'],<?php endforeach;?>];

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
<?php if(sizeof($manyToManyColumnsNames)):?>
<?php $rules[] = "[[". "'". implode("','", $manyToManyColumnsNames) ."'"."], 'safe']" ?>
<?php endif;?>
<?php if(sizeof($oneToManyColumnsNames)):?>
    <?php $rules[] = "[[". "'". implode("','", $oneToManyColumnsNames) ."'"."], 'safe']" ?>
<?php endif;?>
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
        $fields = [
<?php foreach ($ancestorModels as $key => $ancestorModel): ?>
<?php if ($className !== $ancestorModel->fullName): ?>
            <?= $generator->generateString(lcfirst($ancestorModel->name) . "_id") ?> => function($model) { return $model->injectUuid($this-><?= lcfirst($ancestorModel->name) ?>); },
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
<?php foreach ($manyToManyColumns as $manyToManyColumn): ?>
            <?= $generator->generateString($manyToManyColumn['attribute']) ?> => function($model) {
                return $model->injectUuid($this-><?= $manyToManyColumn['relationName'] ?>);
            },
<?php endforeach; ?>
<?php foreach ($oneToManyColumns as $oneToManyColumn): ?>
            <?= $generator->generateString($oneToManyColumn['attribute']) ?> => function($model) {
                return $model->injectUuid($this-><?= $oneToManyColumn['relationName'] ?>);
            },
<?php endforeach; ?>
        ];
<?php if(count($archiveFileFiltersNames) > 0): ?>
        if(\Yii::$app->controller->action->id !== "harvest") {
            $fields["archive_files"] = function ($model) {
                return [
                    'filter' => [
<?php foreach ($archiveFileFiltersNames as $archiveFileFiltersName): ?>
                        '<?=lcfirst($archiveFileFiltersName)?>' => $model-><?=lcfirst($archiveFileFiltersName)?>->id,
<?php endforeach;?>
                        '<?=$nameAttribute?>' => $model->id
                    ]<?php if ($filesColumn): ?>,
                    'files' => ArchiveFile::find()->andWhere([
                        '<?=$filesColumn?>' => $model->id
                    ])->all()
<?php endif; ?>                ];
            };
        }
<?php endif; ?>
        return array_merge(parent::fields(), $fields);
    }

    public function load($data, $formName = null)
    {
<?php foreach ($ancestorModels as $key => $ancestorModel): ?>
<?php if ($className !== $ancestorModel->fullName): ?>
        if(isset($data[<?= $generator->generateString(lcfirst($ancestorModel->name) . "_id") ?>]) && is_array($data[<?= $generator->generateString(lcfirst($ancestorModel->name) . "_id") ?>])) {
            $data[<?= $generator->generateString(lcfirst($ancestorModel->name) . "_id") ?>] = $data[<?= $generator->generateString(lcfirst($ancestorModel->name) . "_id") ?>]['id'];
        }
<?php endif; ?>
<?php endforeach; ?>
<?php foreach ($manyToManyColumns as $manyToManyColumn): ?>
        if(isset($data[<?= $generator->generateString($manyToManyColumn['attribute']) ?>])) {
            $ids = [];
            foreach($data[<?= $generator->generateString($manyToManyColumn['attribute']) ?>] as $item) {
                if(is_array($item)) {
                    $ids[] = $item['id'];
                } else {
                    $ids[] = $item;
                }
            }
            $data[<?= $generator->generateString($manyToManyColumn['attribute']) ?>] = $ids;
        }
<?php endforeach; ?>
<?php foreach ($oneToManyColumns as $oneToManyColumn): ?>
        if(isset($data[<?= $generator->generateString($oneToManyColumn['attribute']) ?>]) && is_array($data[<?= $generator->generateString($oneToManyColumn['attribute']) ?>])) {
            $data[<?= $generator->generateString($oneToManyColumn['attribute']) ?>] = $data[<?= $generator->generateString($oneToManyColumn['attribute']) ?>]['id'];
        }
<?php endforeach; ?>
        return parent::load($data, $formName);
    }

<?php foreach ($manyToManyColumns as $manyToManyColumn): ?>
    public function get<?= ucfirst(Inflector::id2camel($manyToManyColumn['attribute'], '_')) ?>() {
<?php if($manyToManyColumn['oppositionRelation']): ?>
        class_exists("app\models\base\Base<?=ucfirst(Inflector::id2camel($manyToManyColumn['relatedTable'], '_'))?>");
<?php endif; ?>
        return parent::getRelatedIds (Base<?=$manyToManyColumn['oppositionRelation'] ? ucfirst(Inflector::id2camel($manyToManyColumn['relatedTable'], '_')) : $className?><?=$manyToManyColumn['oppositionRelation'] ? $className : ucfirst(Inflector::id2camel($manyToManyColumn['relatedTable'], '_'))?>::className(), ['<?=$tableName?>_id' => 'id'], '<?= $manyToManyColumn['oppositionRelation'] ? lcfirst(Inflector::id2camel($tableName, '_')) :lcfirst(Inflector::id2camel($manyToManyColumn['relatedTable'], '_'))?>');
    }

    public function set<?= ucfirst(Inflector::id2camel($manyToManyColumn['attribute'], '_')) ?>($values) {
<?php if($manyToManyColumn['oppositionRelation']): ?>
        class_exists("app\models\base\Base<?=ucfirst(Inflector::id2camel($manyToManyColumn['relatedTable'], '_'))?>");
<?php endif; ?>
        return parent::setRelatedIds (Base<?=$manyToManyColumn['oppositionRelation'] ? ucfirst(Inflector::id2camel($manyToManyColumn['relatedTable'], '_')) : $className?><?=$manyToManyColumn['oppositionRelation'] ? $className : ucfirst(Inflector::id2camel($manyToManyColumn['relatedTable'], '_'))?>::className(), ['<?=$tableName?>_id' => 'id'], '<?= $manyToManyColumn['relatedTable']?>_id', $values);
    }
<?php endforeach; ?>

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

<?php if(sizeof($manyToManyColumns)): ?>
    /**
    * {@inheritdoc}
    */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
<?php foreach ($manyToManyColumns as $manyToManyColumn): ?>
        $this->set<?=ucfirst(Inflector::id2camel($manyToManyColumn['attribute'], '_')) ?>($this-><?=$manyToManyColumn['attribute']?>);
<?php endforeach; ?>
    }
<?php endif?>

    /**
    * {@inheritdoc}
    */
    public function beforeDelete()
    {
<?php foreach ($manyToManyColumns as $manyToManyColumn): ?>
        $this->set<?= ucfirst(Inflector::id2camel($manyToManyColumn['attribute'], '_')) ?>([]);
<?php endforeach; ?>
        return parent::beforeDelete(); // TODO: Change the autogenerated stub
    }

<?= $specializationsSourceCode ?>

}

<?php foreach ($connectionModelsData as $connectionModelData): ?>
    <?= $connectionModelData->content ?>
<?php endforeach; ?>
