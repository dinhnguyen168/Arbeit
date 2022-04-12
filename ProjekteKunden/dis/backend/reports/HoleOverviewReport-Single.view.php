<?php
/**
 *
 * Parameters for the view:
 * @var $header Header for every page
 * @var $core
 * @var $coreLength
 * @var $maxLength
 * @var $sectionImageUrls
 * @var $scaleFactor
 **/
?>
<div class="report hole-overview-single-core-report">
  <div class="ruler">
    <ol id="ruler">
        <?php $stepHeight = $maxLength / 10; ?>
        <?php for ($scale=0; $scale < $maxLength; $scale += 10): ?>
          <li style="height: <?= $scaleFactor ?>"></li>
        <?php endfor; ?>
      <li></li>
    </ol>
  </div>

  <div class="section" data-length="<?= $coreLength ?>">
    <div class="head"><span>Core <?= $core->core . " " . $core->core_type ?></span></div>
      <?php foreach ($core->coreSections as $section): ?>
          <?php $length = $section->section_length * 100; ?>
        <img src="<?= $sectionImageUrls[$section->id] ?>" style="height:calc(<?= $length / 10.0 ?> * <?= $scaleFactor ?>)">
      <?php endforeach; ?>
  </div>
</div>
