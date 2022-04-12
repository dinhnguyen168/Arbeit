<?php
/**
 *
 * Parameters for the view:
 * @var $header
 * @var $model
 * @var $oneToManyValues
 * @var $manyToManyValues
 * @var $columns
 **/
?>
<?= $header ?>
<div class="report details-report">
  <table class="report" >
    <thead>
    <tr>
      <th class="blue">Column</th>
      <th class="blue">Value</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($columns as $column => $label): ?>
        <?php
        if (in_array($column, array_keys($oneToManyValues))) {
            $val = $oneToManyValues[$column][0];
        } else if (in_array($column, array_keys($manyToManyValues))) {
            $val = $manyToManyValues[$column][0];
        } else {
            $val = $model->{$column};
            if (is_array($val)) {
                $val = implode("<br/>", $val);
            }
        }
        ?>
      <tr>
        <td><?= $label ?></td>
        <td><?= $val ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
