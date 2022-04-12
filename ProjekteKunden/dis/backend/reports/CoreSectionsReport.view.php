<?php
/**
 *
 * Parameters for the view:
 * @var $header
 * @var $cores
 **/
?>
<?= $header ?>
<div class="report core-section-report">
  <table class="report" >
    <thead>
    <tr>
      <th class="blue">Core</th>
      <th class="blue" style="width: 35mm;">On-Deck</th>
      <th class="blue">Core Top Depth [m]</th>
      <th class="blue">Core Bottom Depth [m]</th>
      <th class="blue">Length Cored [m]</th>
      <th class="blue">Length Recovered [m]</th>
      <th class="blue">Core Recovered [%]</th>

      <th class="red">Section Number</th>
      <th class="red">Section Length [m]</th>
      <th class="red">Curated Length [m]</th>
      <th class="red">Top Depth [m]</th>
      <th class="red">Bottom Depth [m]</th>
      <th class="red">Section Remarks</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($cores as $core): ?>
      <?php $sections = $core->coreSections; ?>
      <tr>
        <td><?= $core->core ?> - <?= $core->core_type ?></td>
        <td><?= $core->core_ondeck ?></td>
        <td><?= $core->core_top_depth ?></td>
        <td><?= $core->core_bottom_depth ?></td>
        <td><?= $core->drilled_length ?></td>
        <td><?= $core->core_recovery ?></td>
        <td><?= \Yii::$app->formatter->asDecimal($core->core_recovery_pc, 2) ?></td>
        <td colspan="6" style="color: #999;"><?= count($sections) ?> Section/s</td>
      </tr>
        <?php foreach ($sections as $section): ?>
        <tr class="sub">
          <td class="no-border" colspan="7">&nbsp;</td>
          <td><?= $section->section ?></td>
          <td><?= $section->section_length ?></td>
          <td><?= $section->curated_length ?></td>
          <td><?= $section->top_depth ?></td>
          <td><?= $section->bottom_depth ?></td>
          <td><?= $section->comment ?></td>
        </tr>
        <?php endforeach ?>
      <tr>
        <td class="no-border" colspan="13">&nbsp;</td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
