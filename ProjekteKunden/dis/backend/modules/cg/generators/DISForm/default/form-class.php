<?php

/* @var $generator yii\gii\generators\model\Generator */
/* @var $name string Name of the form */
/* @var $namespace string Namespace of class */
/* @var $className string class name */
/* @var $parentClassName Name of parent class */
/* @var $formFields string[] Array of field names */
/* @var $fieldsGroups array[] */
/* @var $labels string[] list of attribute labels (name => label) */

echo "<?php\n";
?>

namespace <?= $namespace ?>;

use Yii;

/**
* @inheritdoc
*/
class <?= $className ?> extends <?= $parentClassName . "\n" ?>
{

    const FORM_NAME = <?= $generator->generateString($name) . ";\n"; ?>

    public function scenarios() {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_DEFAULT] = ['<?= implode("', '", $formFields)?>'];
        return $scenarios;
    }

    /**
    * {@inheritdoc}
    */
    public function attributeLabels()
    {
      return [
<?php foreach ($fieldsGroups as $fieldGroup): ?>
<?php foreach ($fieldGroup as $field): ?>
        <?= $generator->generateString($field["name"]) . " => Yii::t('app', " . $generator->generateString($field["label"]) . "),\n" ?>
    <?php endforeach; ?>
<?php endforeach; ?>
      ];
    }

}
