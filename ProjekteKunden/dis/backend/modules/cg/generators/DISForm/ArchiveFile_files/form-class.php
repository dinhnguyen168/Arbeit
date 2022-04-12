<?php
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
}