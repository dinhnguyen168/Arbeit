<?php
/**
 *
 * Parameters for the view:
 * @var $report
 * @var $sections
 * @var $ancestorValues
 **/

$tableColumns = [
    "__SCALE" => [], // Do not change this label
    "__IMAGE" => ["style" => "width:20%"], // Do not change this label
    "Color" => ["style" => "width:10%"],
    "Grain Size" => ["style" => "width:10%"],
    // "Lithology " => ["class" => "vertical", "style" => "width:10%"],
    "Texture" => ["style" => "width:20%"],
    "Litho Type" => ["style" => "width:10%"],
    //"Accessories" => ["class" => "vertical"],
    "Lithology" => ["style" => "width:30%"],
];

?>
<?php foreach ($sections as $section): ?>
    <?php
    $headerValues = [
        'Site' => $ancestorValues['site'][0],
        'Hole' => $ancestorValues['hole'][0],
        'Core' => $ancestorValues['core'][0],
        'Section' => $section->section,
        'Top depth' => $section->top_depth . " m",
        'Bottom depth' => $section->bottom_depth . " m"
    ];

    if (isset($section->box)) {
        $headerValues["Box"] = $section->box;
    }
    $extraHeaderRow = <<<'EOD'
      <div class="report-row">
          <div class="report-col single-line curator">
              <span class="report-label left">Curator:</span>
          </div>
          <div class="report-col single-line date">
              <span class="report-label left">Date:</span>
          </div>
      </div>
EOD;
    $maxLength = ceil($section->curated_length * 100 / 10) * 10;
    $sectionImageUrl = $report->getSectionImageUrl ($section);
    $header = $report->renderDisHeader($headerValues, "Visual core description", null, null, $extraHeaderRow)
    ?>
  <div class="report-wrapper">

      <?= $header ?>
      <?php $percentHeight = $section->curated_length * 10000.0 / $maxLength; ?>

    <div class="report visual-core-description-report">
      <table class="visual-core-description">
        <thead>
        <tr>
            <?php foreach ($tableColumns as $label => &$opt): ?>
                <?php
                $attributes = "";
                if ($label == "__SCALE") {
                    if (!isset($opt["class"]))
                        $opt["class"] = "scale-unit";
                    else
                        $opt["class"] .= " scale-unit";
                }
                foreach ($opt as $name => $value) {
                    $attributes .= $name . '="' . $value . '" ';
                }
                $attributes = trim($attributes);
                ?>
                <?php $label = ($label == "__SCALE" ? "cm" : ($label == "__IMAGE" ? "image length = curated length" : $label)) ?>
              <th <?= $attributes?>><div><?= $label ?></div></th>
            <?php endforeach; ?>
        </tr>
        </thead>
        <tbody>
        <tr>
            <?php foreach ($tableColumns as $label => $options): ?>
              <td <?= $label == "__SCALE" ? 'class="scale"' : '' ?>>
                  <?php if ($label == "__SCALE"): ?>
                    <ol id="ruler">
                        <?php $stepHeight = $maxLength; ?>
                        <?php for ($scale=0; $scale <= $maxLength; $scale += 1): ?>
                          <li <?= ($scale % 10 == 0 ? 'class="deci" ' : '') ?> style="height: calc(100% / <?= $stepHeight ?>)"></li>
                        <?php endfor; ?>
                    </ol>
                  <?php elseif ($label == "__IMAGE"): ?>
                    <div class="section" data-length="<?= $section->curated_length ?>" >
                      <img src="<?= $sectionImageUrl ?>" style="height: <?= $percentHeight ?>%"></a>
                    </div>
                  <?php else: ?>
                    &nbsp;
                  <?php endif; ?>
              </td>
            <?php endforeach; ?>
        </tr>
        </tbody>
      </table>
    </div>
  </div>

<?php endforeach; ?>
