<?php
/**
*
* Parameters for the view:
* @var $repository
* @var $expedition
* @var $program
* @var $reportName
* @var $attributes
* @var $extraHeaderRow
**/
?>
<header class="reports report">
    <div class="report-header-row">
        <div class="logos">
            <?php if ($repository): ?>
                <img class="logo" src="<?= $repository["url"] ?>" alt="<?= $repository["name"] ?>">
            <?php endif; ?>
            <?php if ($expedition): ?>
                <img id="logo" class="logo" src="<?= $expedition->getIconUrl() ?>" alt="<?= $expedition->exp_acronym ?> Logo">
            <?php endif; ?>
        </div>
        <div id="report-infos">
          <div class="report-row">
            <h3 class="report-title">
              <?php if ($expedition): ?>
                <span class="report-expedition"><?= $expedition->exp_name ?></span>
              <?php elseif ($program): ?>
                <span class="report-expedition"><?= $program->program_name ?></span>
              <?php endif; ?>
              <span class="report-name"><?= $reportName ?></span>
            </h3>
          </div>
          <div class="report-row">
            <?php if ($expedition): ?>
                <div class="report-col"><span class="report-label">Expedition</span><span class="report-value"><?= $expedition->expedition ?></span></div>
            <?php endif; ?>
            <?php foreach ($attributes as $label => $value): ?>
            <div class="report-col"><span class="report-label"><?= $label ?></span><span class="report-value"><?= $value ?></span></div>
            <?php endforeach; ?>
          </div>
        </div>
    </div>
    <?php if ($extraHeaderRow): ?>
      <div class="report-header-row">
        <?= $extraHeaderRow; ?>
      </div>
    <?php endif; ?>
</header>

