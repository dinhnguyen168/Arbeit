<?php
/**
 *
 * Parameters for the view:
 * @var $header
 * @var $gearNames
 * @var $gear
 **/
?>
<?= $header ?>
<div class="report expedition-events-overview-report">
  <table class="report" >
    <thead>
    <tr>
      <th class="blue">Expedition</th>
      <th class="blue">Sites</th>
      <th class="blue">Events</th>

        <?php foreach ($gearNames as $gearName): ?>
          <th class="blue"><?= $gearName ?></th>
        <?php endforeach; ?>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($gear as $expeditionNo => $expeditionGear): ?>
        <tr>
          <td><?= $expeditionNo ?></td>
          <td><?= $expeditionGear["sites"] ?></td>
          <td><?= $expeditionGear["holes"] ?></td>

            <?php foreach ($gearNames as $gearName): ?>
              <td><?= $expeditionGear[$gearName] ?></td>
            <?php endforeach; ?>
        </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
