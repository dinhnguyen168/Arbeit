<?php
/**
 *
 * Parameters for the view:
 * @var $header Header for every page
 * @var $columns
 * @var $oneToManyValues
 * @var $manyToManyValues
 * @var $models
 **/
?>
<?= $header ?>
<div class="core-section-report report">
  <table class="report" >
    <thead>
    <tr>
        <?php foreach ($columns as $column): ?>
          <th class="blue"><?= $column ?></th>
        <?php endforeach; ?>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($models as $model): ?>
      <tr>
        <?php foreach ($columns as $name => $label): ?>
            <?php
                if (in_array($column, array_keys($oneToManyValues))) {
                    $val = $oneToManyValues[$column][0];
                } else if (in_array($column, array_keys($manyToManyValues))) {
                    $val = $manyToManyValues[$column][0];
                } else {
                    $val = isset($model->{$name}) ? $model->{$name} : "";
                    if (is_array($val)) {
                        $val = implode("<br/>", $val);
                    }
                }
            ?>
          <td><?= $val ?></td>
        <?php endforeach; ?>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
