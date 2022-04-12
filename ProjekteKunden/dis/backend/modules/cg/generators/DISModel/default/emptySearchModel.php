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
/* @var $rules string[] list of validation rules */
/* @var $relations array list of relations (name => relation declaration) */
/* @var $specializationsSourceCode Additional class properties added by file in directory "specialisationsSourceCode" */

echo "<?php\n";
?>

namespace <?= $generator->ns ?>;

use Yii;

/**
 * This is the search model class for model "<?= $className ?>".
 * Add your customizations in this class.
*/
class <?= $searchClassName ?> extends <?= '\\' . $generator->baseNs . '\\' . $generator->baseClassPrefix . $searchClassName . "\n" ?>
{

<?= $specializationsSourceCode ?>
}
