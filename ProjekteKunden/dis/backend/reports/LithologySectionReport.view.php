<?php
/**
 *
 * Parameters for the view:
 * @var $report
 * @var $sectionSplits
 * @var $ancestorValues
 **/

$tableColumns = [
    "litho_unit" => "Unit",
    "top_depth" => "Top of Unit [cm]",
    "unit_length" => "Unit length [cm]",
    "mcd_top_depth_unit" => "Top Depth [m]",
    "rock_class" => "Unit class",
    "rock_type" => "Unit type",
    "color" => "Color"
];

$headerValues = [
'Site' => $ancestorValues['site'][0],
'Hole' => $ancestorValues['hole'][0],
'Core' => isset($ancestorValues['core']) ? $ancestorValues['core'][0] : '',
'Date' => date('d-M-Y'),
'Comment' => "image length = curated length"
];
$header = $report->renderDisHeader($headerValues, "Lithology/Section Report");

?>
<?php if (sizeof ($sectionSplits) == 0): ?>
<div class="report-wrapper">
    <?= $header ?>
</div>
<?php endif; ?>


<?php foreach ($sectionSplits as $sectionSplit): ?>
    <?php
    $section = $sectionSplit->section;
    $maxLength = ceil($section->curated_length * 100 / 10) * 10;

    $extraHeaderValues = [
        'Section Num' => $section->section,
        'Split' => $sectionSplit->type,
        'Section length' => ($section->section_length * 100) . " cm",
        'Curated length' => ($section->curated_length * 100) . " cm",
        'Top depth' => $section->mcd_top_depth . " m",
        'Bottom depth' => ($section->mcd_top_depth + $section->section_length) . " m"
    ];
    if (isset($sectionSplit->corebox_id)) {
        $extraHeaderValues["Box"] = $sectionSplit->corebox ? $sectionSplit->corebox->corebox : "";
    }

    $maxLength = ceil($section->curated_length * 100 / 10) * 10;
    $sectionImageUrl = $report->getSectionImageUrl ($section);
    $extraHeader = $report->renderDisExtraHeader ($extraHeaderValues, "extra");

    $sectionLength = $section->curated_length * 100; // cm

    $lithologies = $sectionSplit->geologyLithologies;
    // sort lithologies by top_depth ASC
    usort($lithologies, function($a, $b) {
      return ($a->top_depth < $b->top_depth ? -1 : 1);
    });

    $colorBlocks = [];
    $sumLithologyLengths = 0;
    foreach ($lithologies as $n => $lithology) {
      $top = round($lithology->top_depth * 100.0 / $maxLength, 1); // %
      $length = round ($lithology->unit_length * 100.0 / $maxLength, 1); // %
      $color = "hsl(" . (($n+1) * (360 / sizeof($lithologies)) % 360) . ",100%,50%)";
      $colorBlocks[] = ['top' => $top, 'length' => $length, 'color' => $color];
      $sumLithologyLengths += $lithology->unit_length;
    }

    ?>
  <div class="report-wrapper">
    <?= $header ?>
    <div class="report lithology-report">
      <div class="lithology-colors">
        <ol id="ruler">
            <?php $stepHeight = $maxLength; ?>
            <?php for ($scale=0; $scale <= $maxLength; $scale += 1): ?>
              <li <?= ($scale % 10 == 0 ? 'class="deci" ' : '') ?> style="height: calc(100% / <?= $stepHeight ?>)"></li>
            <?php endfor; ?>
        </ol>
        <img src="<?= $sectionImageUrl ?>">
          <div class="colorBlocks">
          <?php foreach($colorBlocks as $colorBlock): ?>
            <div class="colorBlock" style="top:<?=$colorBlock['top'] ?>%; height:<?=$colorBlock['length'] ?>%">
              <svg width="100%" height="100%">
                <rect width="100%" height="100%" style="fill:<?= $colorBlock['color'] ?>"></rect>
              </svg>
            </div>
          <?php endforeach; ?>
          </div>
      </div>

      <div class="lithology-data">
        <?= $extraHeader ?>
        <table class="report">
          <thead>
          <tr>
              <th></th>
              <?php foreach ($tableColumns as $column => $label): ?>
                <th><?= $label ?></th>
              <?php endforeach; ?>
          </tr>
          </thead>
          <tbody>
          <?php foreach($lithologies as $n => $lithology): ?>
          <?php
            $color = "hsl(" . (($n+1) * (360 / sizeof($lithologies)) % 360) . ",100%,50%)";
          ?>
          <tr>
              <td>
                <svg class="color-marker">
                  <rect width="100%" height="100%" style="fill:<?= $color ?>" />
                </svg>
              </td>
              <?php foreach ($tableColumns as $column => $label): ?>
                <td><?= $lithology->{$column} ?>
                </td>
              <?php endforeach; ?>
          </tr>
          <tr>
            <td colspan="<?= sizeof($tableColumns) + 1 ?>"><?= $lithology->description ?></td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>

        <?php if ($sumLithologyLengths > ($section->curated_length * 100)): ?>
          <div class="alert alert-warning" role="alert">
            The sum of the lithology units exceeds the curated length of the section!
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

<?php endforeach; ?>
