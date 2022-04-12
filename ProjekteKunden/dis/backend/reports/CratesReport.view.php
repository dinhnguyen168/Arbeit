<?php
/**
 *
 * Parameters for the view:
 * @var $header
 * @var $crates
 **/
?>
<?= $header ?>
<div class="report create-crates-report">
  <table class="table table-condensed table-hover">
    <thead>
    <tr>
      <th>Crate name</th>
      <th>Section splits</th>
      <th>Weight (kg)</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($crates as $crate): ?>
      <tr>
        <td><?= $crate["name"] ?></td>
        <td><?= $crate["split"] ?></td>
        <td><?= $crate["weight"] ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
