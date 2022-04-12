<?php
/**
 *
 * Parameters for the view:
 * @var $report
 * @var $holes
 * @var $ancestorValues
 *
 **/
?>
<?php foreach ($holes as $i => $hole): ?>
    <?php
    $chunkSize = 20;
    $holeCores = $hole->coreCores;

    for ($chunkOffset = 0; $chunkOffset < sizeof($holeCores); $chunkOffset+= $chunkSize) {
        $maxLength = 0;

        $minBox = 99999;
        $maxBox = 0;

        $minCore = 99999;
        $maxCore = 0;

        $topDepth = 99999;
        $bottomDepth = 0;

        $cores = [];
        $sectionImageUrls = [];
        for ($index = $chunkOffset; $index < min (sizeof($holeCores), $chunkOffset + $chunkSize); $index++) {
            $core = $holeCores[$index];
            $cores[] = $core;

            $minCore = min($minCore, $core->core);
            $maxCore = max($maxCore, $core->core);

            $topDepth = min($topDepth, $core->core_top_depth);
            $bottomDepth = max($bottomDepth, $core->core_bottom_depth);

            $coreLength = 0;
            foreach ($core->coreSections as $section) {
                $coreLength += $section->curated_length;
                $sectionImageUrls[$section->id] = $report->getSectionImageUrl($section);
                if (isset($core->box)) {
                    $minBox = min($minBox, $section->box);
                    $maxBox = max($maxBox, $section->box);
                }
            }
            $maxLength = max($maxLength, $coreLength * 100);
        }

        $maxLength = ceil($maxLength/10)*10;
        $headerValues = [
            'Site' => $ancestorValues['site'][0],
            'Hole' => $hole->hole,
            'Cores' => $minCore . " - " . $maxCore,
            'Top depth' => $topDepth . " m",
            'Bottom depth' => $bottomDepth . " m",
			'Comment' => "image length = curated length"
        ];
        if ($maxBox > 0) {
            if ($maxBox != $minBox)
                $headerValues["Boxes"] = $minBox . " - " . $maxBox;
            else
                $headerValues["Box"] = $minBox;
        }

        $maxLength = $maxLength;
        $header = $report->renderDisHeader($headerValues, "Hole Overview");

    ?>
    <div class="report-wrapper">
      <?= $header ?>

      <div class="report hole-overview-report">
        <div class="ruler">
            <ol id="ruler">
            <?php $stepHeight = $maxLength / 10; ?>
            <?php for ($scale=0; $scale < $maxLength; $scale += 10): ?>
                <li style="height: calc(100% / <?= $stepHeight ?>)"></li>
            <?php endfor; ?>
            <li></li>
          </ol>
        </div>

        <?php $numCores = $chunkSize; ?>
        <?php foreach($cores as $core): ?>
            <div class="section" data-length="<?= $core->drilled_length ?>" style="max-width:7em; width: calc((100% / <?= $numCores ?>) - (4em / <?= $numCores ?>) - 0.25em)" onclick="zoomCore(<?= $core->id ?>)">
              <div class="head"><span><?= $core->core ?></span></div>
              <?php foreach ($core->coreSections as $section): ?>
                <?php $percentHeight = $section->curated_length * 10000.0 / $maxLength; ?>
                <?php if (preg_match("/\\.svg$/", $sectionImageUrls[$section->id])): ?>
                    <img class="no-img" src="<?= $sectionImageUrls[$section->id] ?>" style="height: calc(<?= $percentHeight ?>%)">
                <?php else: ?>
                    <img src="<?= $sectionImageUrls[$section->id] ?>" style="height: calc(<?= $percentHeight ?>%)">
                <?php endif; ?>
              <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
      </div>
      <div id="footer" class="footer"><span class="d-print-none">Click on a core to zoom.</span></div>
    </div>
    <?php
    }
   ?>
<?php endforeach; ?>
