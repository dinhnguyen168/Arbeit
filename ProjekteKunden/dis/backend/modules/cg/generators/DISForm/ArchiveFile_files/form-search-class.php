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
}