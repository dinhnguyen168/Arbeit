<?php
/**
*
* Parameters for the view:
* @var $class
* @var $attributes
**/
?>
<header class="reports report extra <?= $class ?>">
  <div class="report-header-row">
    <div id="report-infos">
      <div class="report-row">
          <?php foreach ($attributes as $label => $value): ?>
            <div class="report-col"><span class="report-label"><?= $label ?></span><span class="report-value"><?= $value ?></span></div>
          <?php endforeach; ?>
      </div>
    </div>
  </div>
</header>
