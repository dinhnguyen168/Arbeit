<?php
/**
 *
 * Parameters for the view:
 * @var $report
 * @var $x_expeditions
 **/
?>
<?php foreach ($x_expeditions as $x_exp): ?>
    <?php
    $expedition = $x_exp["expedition"];
    $report->setExpedition($expedition);
    $ancestorValues = $report->getAncestorValues($expedition);

    $headerAttributes = [];
    foreach ($ancestorValues as $ancestorValue) $headerAttributes[$ancestorValue[1]] = $ancestorValue[0];
    $headerAttributes["Displayed / Archived Sites/Stations"] = sizeof($x_exp["displayed-sites"]) . " / " . $x_exp["count-sites"];

    $nDisplayedHoles = 0;
    $nTotalHoles = \app\models\ProjectHole::find()->joinWith("site")->where(['expedition_id' => $expedition->id])->count();
    foreach ($x_exp["displayed-sites"] as $exp_site) {
        $nDisplayedHoles += sizeof($exp_site["displayed-holes"]);
    }
    $headerAttributes["Displayed / Archived Events"] = $nDisplayedHoles . " / " . $nTotalHoles;
    $header = $report->renderDisHeader($headerAttributes, "Sites / Events");

    ?>
  <?= $header ?>
  <div class="report sites-event-report">
    <?php foreach ($x_exp["displayed-sites"] as $x_site): ?>
      <?php $site = $x_site["site"]; ?>
      <?php $hasShipSite = $site->hasAttribute("ship-site") ?>

      <table class="report site">
        <thead>
        <tr>
          <th class="blue" style="width:5%">Site</th>
            <?php if ($hasShipSite): ?>
              <th class="blue" style="width:5%">Ship-Site</th>
            <?php endif; ?>
          <th class="blue" style="width:<?= $hasShipSite ? '25%': '30%' ?>">Name / Location</th>
          <th class="blue">Remarks</th>
        </tr>
        </thead>
        <tbody>
        <tr>
          <td><?= $site->site ?></td>
            <?php if ($site->hasAttribute("ship-site")): ?>
              <td><?= $site->{"ship-site"} ?></td>
            <?php endif; ?>
          <td><?= $site->site_name ?></td>
          <td><?= $site->comment ?></td>
        </tr>
        </tbody>
      </table>
      <table class="report events">
        <thead>
        <tr>
          <th class="blue" style="width:5%">Hole</th>
          <th class="blue" style="width:30%">Comment</th>
          <th class="blue"></th>
          <th class="blue">Latitude (dec.)</th>
          <th class="blue">Longitude (dec.)</th>
          <th class="blue">Date / Time</th>
          <th class="blue">Hight of Driller's Reference above surface [m]</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($x_site["displayed-holes"] as $hole): ?>
          <tr>
            <td><?= $hole->hole ?></td>
            <td><?= $hole->comment ?></td>
          </tr>
          <tr class="sub">
            <td class="blank" colspan="2"></td>
            <td class="blue">Start</td>
            <td></td>
            <td></td>
            <td><?= $hole->start_date ?></td>
            <td></td>
          </tr>
          <tr class="sub">
            <td class="blank" colspan="2"></td>
            <td class="blue">Bottom</td>
            <td><?= $hole->latitude ?></td>
            <td><?= $hole->longitude ?></td>
            <td><?= $hole->end_date ?></td>
            <td><?= $hole->drillers_reference_height ?></td>
          </tr>
          <tr class="sub">
            <td class="blank" colspan="2"></td>
            <td class="blue">End</td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php endforeach; ?>
  </div>
  <div class="page-break"></div>
<?php endforeach; ?>
