<?php
/**
 *
 * Parameters for the view:
 * @var $report
 * @var $cores
 * @var $ancestorValues
 **/
?>

<?php foreach ($cores as $core): ?>
<?php
$maxLength = 0;
$sectionImageUrls = [];
$nMinBox = 99999;
$nMaxBox = 0;
foreach ($core->coreSections as $oSection) {
    $maxLength = max($maxLength, $oSection->curated_length * 100);
    $sectionImageUrls[] = $report->getSectionImageUrl($oSection);
    if (isset($oSection->box)) {
        $nMinBox = min($nMinBox, $oSection->box);
        $nMaxBox = max($nMaxBox, $oSection->box);
    }
}
$maxLength = ceil($maxLength/10)*10;
$headerValues = [
    'Site' => $ancestorValues['site'][0],
    'Hole' => $ancestorValues['hole'][0],
    'Core' => $core->core . (isset($core->core_type) ? " " . $core->core_type : ""),
    'Top depth' => $core->core_top_depth . " m",
    'Bottom depth' => $core->core_bottom_depth . " m",
	'Comment' => "image length = curated length"
];
if ($nMaxBox > 0) {
    if ($nMaxBox != $nMinBox)
        $headerValues["Boxes"] = $nMinBox . " - " . $nMaxBox;
    else
        $headerValues["Box"] = $nMinBox;
}
$header = $report->renderDisHeader($headerValues, "Core Overview");

?>
<div class="report-wrapper">

  <?= $header ?>

  <div class="report core-overview-report">
    <div class="ruler">
        <ol id="ruler">
        <?php $stepHeight = $maxLength / 10; ?>
        <?php for ($scale=0; $scale < $maxLength; $scale += 10): ?>
            <li style="height: calc(100% / <?= $stepHeight ?>)"></li>
        <?php endfor; ?>
        <li></li>
      </ol>
    </div>

    <?php $numSections = sizeof($core->coreSections); ?>
    <?php foreach($core->coreSections as $i => $oSection): ?>
        <?php $percentHeight = $oSection->curated_length * 10000.0 / $maxLength; ?>
        <div class="section" data-length="<?= $oSection->curated_length ?>" style="max-width:7em; width: calc((100% / <?= $numSections ?>) - (4em / <?= $numSections ?>) - 0.25em)">
          <div class="head"><span>Sect: <?= $oSection->section ?></span></div>
            <?php if (preg_match("/\\.svg$/", $sectionImageUrls[$i])): ?>
                <img src="<?= $sectionImageUrls[$i] ?>" style="height: calc(<?= $percentHeight ?>%)">
            <?php else: ?>
                <a href="?section_id=<?= $oSection->id ?>" target="Zoom"><img src="<?= $sectionImageUrls[$i] ?>" style="height: calc(<?= $percentHeight ?>%)"></a>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
  </div>
  <div id="footer" class="footer"><span class="d-print-none">Click on a section to zoom.</span></div>
</div>
<?php endforeach; ?>
