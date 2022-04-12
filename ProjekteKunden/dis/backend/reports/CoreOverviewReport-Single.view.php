<?php
/**
 *
 * Parameters for the view:
 * @var $header Header for every page
 * @var $maxLength
 * @var $scaleFactor
 * @var $section
 * @var $sectionImageUrl
 **/
?>
<div class="report core-overview-single-section-report">
  <div class="ruler">
    <ol id="ruler">
        <?php $stepHeight = $maxLength / 10; ?>
        <?php for ($scale=0; $scale < $maxLength; $scale += 10): ?>
          <li style="height: <?= $scaleFactor ?>"></li>
        <?php endfor; ?>
      <li></li>
    </ol>
  </div>

    <?php $length = $section->section_length * 100; ?>
  <div class="section" data-length="<?= $section->section_length ?>">
    <div class="head"><span>Sect: <?= $section->section ?></span></div>
    <img src="<?= $sectionImageUrl ?>" style="height:calc(<?= $length / 10.0 ?> * <?= $scaleFactor ?>)">
  </div>
</div>
