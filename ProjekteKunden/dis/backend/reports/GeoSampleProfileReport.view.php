<?php
/**
 *
 * Parameters for the view:
 * @var $report
 * @var $sections
 * @var $prevModel
 * @var $nextModel
 * @var $ancestorValues
 *
 **/
?>
<?php

$aSampleRequests = [];

function getSampleRequest($id) {
  global $aSampleRequests;

  if (isset($aSampleRequests[$id]))
    return $aSampleRequests[$id];
  else {
      $sampleRequest = null;
      if ($id > 0) {
        $sampleRequest = \app\models\CurationSampleRequest::find()->where(['id' => $id])->one();
        $aSampleRequests[$id] = $sampleRequest;
      }
      return $sampleRequest;
  }
}


?>
<?php foreach ($sections as $i => $section): ?>
    <?php
    $splits = [];
    foreach ($section->curationSectionSplits as $split) {
        $splits[] = $split;
    }
    usort($splits, function($a, $b) {
        if ($a->origin_split_id == null) return -1;
        if ($b->origin_split_id == null) return 1;
        if ($a->type == $b->type) return 0;
        return ($a->type < $b->type ? -1 : 1);
    });

    $splitSamples = [];
    foreach ($splits as $split) {
        $splitSamples[$split->id] = ["type" => $split->type, "slots" => [], "parentIds" => []];
        $parentIds = [];
        $parent = $split;
        while ($parent->origin_split_id) {
          $parentIds[] = $parent->origin_split_id;
          $parent = $parent->originSplit;
        }
        $splitSamples[$split->id]["parentIds"] = $parentIds;

        $aSamples = $split->curationSamples;
        usort($aSamples, function($a, $b) {
            if ($a->split_fraction_taken == $b->split_fraction_taken)
                return 0;
            else
                return ($a->split_fraction_taken < $b->split_fraction_taken) ? -1 : 1;
        });

        $aSampleSlots = [];

        foreach ($split->curationSamples as $sample) {
            $left = 0;
            $width = $sample->split_fraction_taken ? $sample->split_fraction_taken : 100;
            foreach ($aSampleSlots as $aSampleSlot) {
                $oExistingSample = $aSampleSlot["sample"];
                if ($sample->bottom > $oExistingSample->top && $oExistingSample->bottom > $sample->top) {
                    if ($left + $width > $aSampleSlot["left"] && $aSampleSlot["left"]  + $aSampleSlot["width"] > $left) {
                        $left = $aSampleSlot["left"]  + $aSampleSlot["width"];
                    }
                }
            }
            $aSampleSlots[] = ["left" => $left, "width" => $width, "sample" => $sample];
        }
        $splitSamples[$split->id]["slots"] = $aSampleSlots;
    }

    $nMaxLength = ceil(max(0.001, $section->section_length) * 10) * 10;
    $ancestorValues = $report->getAncestorValues($section);
    $headerValues = [
        'Site' => $ancestorValues['site'][0],
        'Hole' => $ancestorValues['hole'][0],
        'Core' => $ancestorValues['core'][0],
        'Section' => $section->section,
        'Depth' => $section->top_depth . " - " . $section->bottom_depth . " m",
        'Length' => $section->section_length
    ];

    $imageUrl = $report->getSectionImageUrl($section);
    $header = $report->renderDisHeader($headerValues);
    $request_link = function($sample_request_id) {
        if ($sample_request_id > 0)
            return ;
        else
            return '';
    };

    ?>
<div class="report-wrapper">
    <?= $header ?>

  <div class="report geo-sample-profile-report">
    <div class="data">
      <div class="ruler">
        <ol id="ruler">
            <?php $stepHeight = $nMaxLength / 10; ?>
            <?php for ($scale=0; $scale < $nMaxLength; $scale += 10): ?>
              <li style="height: calc(100% / <?= $stepHeight ?>)"></li>
            <?php endfor; ?>
          <li></li>
        </ol>
      </div>

        <?php $percentHeight = $section->section_length * 10000.0 / $nMaxLength; ?>
        <?php $numSplits = sizeof($splitSamples) ?>
        <?php $allUnavailSplitSlots = [] ?>
      <div class="section" data-length="<?= $section->section_length ?>" style="max-width:calc(<?= $numSplits?> * 1em); background-image: url(<?= $imageUrl ?>) !important">
        <div class="head"><span>Scan</span></div>
      </div>
        <?php foreach ($splitSamples as $splitID => $aSplit): ?>
          <div class="split">
            <div class="head"><span><?= $aSplit["type"] ?></span></div>
              <?php
                $parentUnavailSlots = [];
                foreach ($allUnavailSplitSlots as $unavailSplitId => $unavailSlots) {
                  if (in_array($unavailSplitId, $aSplit["parentIds"])) {
                      $parentUnavailSlots = array_merge($parentUnavailSlots, $unavailSlots);
                  }
                }
                $aSlots = array_merge($parentUnavailSlots, $aSplit["slots"]);
              ?>
              <?php foreach ($aSlots as $aSampleSlot): ?>
                  <?php
                  $sample = $aSampleSlot["sample"];
                  $percent = ($sample->top * 100.0 / $nMaxLength);
                  $offsetY = "calc(1.5em + " . $percent . "% - (1.5em * " . $percent . " / 100))";
                  $length = $sample->bottom - $sample->top;
                  $percentLength = $length * 100.0 / $nMaxLength;
                  $lengthY = "calc(" . $percentLength . "% - (1.5em * " . $percentLength . " / 100))";

                  $left = round ($aSampleSlot["left"] / 100 * 3, 1); // em
                  $width = round ($aSampleSlot["width"] / 100 * 3, 1); // em
                  $class = 'sample sample' . $section->id . '_' . $sample->id;

                  if ($sample->sectionSplit->id != $splitID) {
                    $class = "sample unavail";
                  } else {
                    foreach ($parentUnavailSlots as $unavailSlot) {
                      $unavailSample = $unavailSlot["sample"];
                      if ($unavailSample->top < $sample->bottom && $unavailSample->bottom > $sample->top) {
                        $class .= " conflict";
                      }
                    }
                    if ($aSampleSlot["width"] == 100) {
                        if (!isset($allUnavailSplitSlots[$splitID])) $allUnavailSplitSlots[$splitID] = [];
                        $allUnavailSplitSlots[$splitID][] = ["left" => 0, "width" => 100, "sample" => $sample];
                    }
                  }
                  echo '<div class="' . $class . '"' .
                       ' data-top="' . $sample->top . '"' .
                       ' data-left="' . $left . '"' .
                       ' data-length="' . $length . '"' .
                       ' onclick="showSample(\'' . $section->id . '_' . $sample->id .'\')"' .
                       ' style="top:' . $offsetY . '; left:' . $left . 'em; height:' . $lengthY . ';width:' . $width . 'em"' .
                       '></div>';
                  ?>
              <?php endforeach; ?>
          </div>
        <?php endforeach; ?>

      <div class="infos">
        <table class="report">
          <thead>
          <tr>
            <th class="blue">Split</th>
            <th class="blue">Sample</th>
            <th class="blue">Type</th>
            <th class="blue">Top [cm]</th>
            <th class="blue">Bottom [cm]</th>
            <th class="blue">Fraction of Section Split [%]</th>
            <th class="blue">Top Depth [m]</th>
            <th class="blue">Sample Request</th>
            <th class="blue">IGSN</th>
            <th class="blue">Box</th>
            <th class="blue">Slot</th>
            <th class="blue">Position</th>
          </tr>
          </thead>
          <tbody>
          <?php foreach ($splitSamples as $splitID => $aSplit): ?>
              <?php foreach ($aSplit["slots"] as $aSampleSlot): ?>
                  <?php $sampleRequest = getSampleRequest($sample->sample_request_id); ?>
                  <?php $sample = $aSampleSlot["sample"]; ?>
              <tr class="sample<?= $section->id . "_" . $sample->id ?>" onclick="showSample('<?= $section->id . "_" . $sample->id ?>')">
                <td><?= $aSplit["type"] ?></td>
                <td><?= $sample->id ?></td>
                <td><?= $sample->sample_material ?></td>
                <td><?= $sample->top ?></td>
                <td><?= $sample->bottom ?></td>
                <td><?= $sample->split_fraction_taken ?></td>
                <td><?= $sample->mcd_top_depth ?></td>
                <td>
                  <?php if ($sampleRequest): ?>
                    <a href="../#/forms/sample-request-form/<?= $sampleRequest->id ?>" target="_request"><?= $sampleRequest->request_no . ' ' . $sampleRequest->request_part ?></a>
                  <?php endif; ?>
                </td>
                <td><?= $sample->igsn ?></td>
                <td><?= isset($sample->sectionSplit->box) ? $sample->sectionSplit->box : ""  ?></td>
                <td><?= isset($sample->sectionSplit->slot) ? $sample->sectionSplit->slot : "" ?></td>
                <td><?= isset($sample->sectionSplit->position) ? $sample->sectionSplit->position : "" ?></td>
              </tr>
              <?php endforeach; ?>
          <?php endforeach; ?>
          <tr>
            <td colspan="12">Click on a sample to highlight it.</td>
          </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <?php if ($i == sizeof($sections)-1 && ($prevModel || $nextModel)): ?>
    <div id="footer" class="footer">
      <span class="d-print-none">
        <span class="prev-next">
            <?php if ($prevModel): ?>
              <a class="prev" href="?model=<?= $prevModel->getModelFullName() ?>&id=<?= $prevModel->id ?>"><button>previous <?= strtolower($prevModel::SHORT_NAME) ?></button></a>
            <?php endif; ?>
            <?php if ($nextModel): ?>
              <a class="next" href="?model=<?= $nextModel->getModelFullName() ?>&id=<?= $nextModel->id ?>"><button>next <?= strtolower($nextModel::SHORT_NAME) ?></button></a>
            <?php endif; ?>
        </span>
      </span>
    </div>
  <?php endif; ?>
</div>
<?php endforeach; ?>
