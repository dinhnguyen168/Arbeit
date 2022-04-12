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
<div class="report expedition-events-by-site-report">
  <table class="report" >
    <thead>
    <tr>
      <th class="blue">Expedition</th>
      <th class="blue">Site</th>
      <th class="blue">Events</th>

        <?php foreach ($gearNames as $gearName): ?>
          <th class="blue"><?= $gearName ?></th>
        <?php endforeach; ?>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($gear as $expeditionNo => $expeditionGear): ?>
      <?php foreach ($expeditionGear as $siteNo => $siteData): ?>
        <tr>
          <td><?= $expeditionNo ?></td>
          <td><?= $siteNo ?></td>
          <td><?= $siteData["holes"] ?></td>

            <?php foreach ($gearNames as $gearName): ?>
              <td><?= $siteData[$gearName] ?></td>
            <?php endforeach; ?>
        </tr>
      <?php endforeach; ?>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
