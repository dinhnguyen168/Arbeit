<?php
/**
 *
 * Parameters for the view:
 * @var $report
 * @var $models
 **/

global $coreBoxColumns, $sectionSplitColumns;

$coreBoxColumns = [
    "corebox" => "Box",
    "[topDepth]" => "Top Depth [m]",
    "[bottomDepth]" => "Bottom Depth [m]",
    "[storedLength]" => "Stored core length [m]",
];

$sectionSplitColumns = [
    "combined_id" => "Combined ID sections",
    "corebox_slot" => "Slot",
    "corebox_position" => "Position",
    "section->curated_length" => "Curated Length [m]",
    "section->mcd_top_depth" => "Section Top Depth [m]",
    // "comment_storage" => "Remarks",
];


global $coreBoxData;
$coreBoxData = [];

function getBoxData ($report, $coreBox) {
    global $coreBoxData;

    if (!isset($coreBoxData[$coreBox->id])) {
        set_time_limit(100);
        // "Run" == "Core"
        $topCore = null;
        $bottomCore = null;
        $topSection = null;
        $bottomSection = null;
        $storedLength = 0;

        foreach ($coreBox->curationSectionSplits as $split) {
            $core = $split->core;
            if ($topCore == null || $core->core_top_depth < $topCore->core_top_depth) {
                $topCore = $core;
            }
            if ($bottomCore == null || $core->core_top_depth > $bottomCore->core_top_depth) {
                $bottomCore = $core;
            }

            $section = $split->section;
            if ($topSection == null || $section->top_depth < $topSection->top_depth) {
                $topSection = $section;
            }
            if ($bottomSection == null || $section->top_depth > $bottomSection->top_depth) {
                $bottomSection = $section;
            }

            $storedLength += $section->curated_length;
        }

        $type = "";
        $image = getBoxImage($coreBox);
        if ($image) {
            $type = preg_replace("/^B/", "", $image->type);
        }

        $coreBoxData[$coreBox->id] = [
            "corebox" => $coreBox->corebox,
            "[topDepth]" => $topSection->mcd_top_depth,
            "[bottomDepth]" => $bottomSection->mcd_top_depth + $bottomSection->section_length,
            "[storedLength]" => $storedLength,
            "[type]" => $type,
            "[image]" => $image,
            "[storage]" => $coreBox->storage ? $coreBox->storage->combined_id : "",
            "remarks" => $coreBox->comment
        ];
    }
    return $coreBoxData[$coreBox->id];
}

function getBoxImage($coreBox) {
    $hole = $coreBox->hole;
    $fileTypes = ["BA", "BW"];

    foreach ($hole->archiveFiles as $archiveFile) {
        if ($archiveFile->number == $coreBox->corebox && in_array($archiveFile->type, $fileTypes) && preg_match("/^image/", $archiveFile->mime_type)) {
            $file = $archiveFile->getConvertedFile();
            if (file_exists($file)) {
                return $archiveFile;
            }
        }
    }
    return null;
}

function formatColumnValue ($column, $value) {
    if (in_array($column, ["[topDepth]", "[bottomDepth]", "[storedLength]", "curated_length", "mcd_top_depth"])) {
        $value = number_format($value, 2, '.', ' ');
    }
    return $value;
}


global $lastCoreboxId;
$lastCoreboxId = 0;

global $headerAttributes;
$headerAttributes = [];

function showCoreboxes ($report, $models, $showHeader = true) {
  global $coreBoxColumns, $sectionSplitColumns;
  global $lastCoreboxId, $headerAttributes;

  if (!$models || sizeof($models) == 0) return;

  $coreBox = $models[0];
  $hole = $coreBox->hole;
  $ancestorValues = $report->getAncestorValues($hole);
  $report->setExpedition($hole);
  $nameAttribute = constant(get_class($hole) . "::NAME_ATTRIBUTE");
  $ancestorValues[$nameAttribute] = [$hole->{$nameAttribute}, $hole->getAttributeLabel($nameAttribute)];

  $headerAttributes = [];
  foreach ($ancestorValues as $ancestorValue) $headerAttributes[$ancestorValue[1]] = $ancestorValue[0];
  $headerAttributes["Date"] = date("d-M-Y");
  $headerAttributes["&nbsp;"] = '<span class="warning">Check if depth is finalized!</span>';

      $header = $report->renderDisHeader($headerAttributes, "Core Boxes Summary");
?>
  <?= $header ?>
  <div class="report core-box-report">
    <table class="report">
      <thead>
      <tr>
          <?php foreach($coreBoxColumns as $column => $label): ?>
            <th class="blue"><?= $label ?></th>
          <?php endforeach; ?>
          <?php foreach($sectionSplitColumns as $column => $label): ?>
            <th class="blue"><?= $label ?></th>
          <?php endforeach; ?>
      </tr>
      </thead>
      <tbody>
        <?php
          $n = 1;
          $coreBoxData = [];
        ?>
        <?php foreach ($models as $coreBox): ?>
            <?php
              $coreBoxData[] = $data = getBoxData($report, $coreBox);
            ?>
            <?php foreach($coreBox->curationSectionSplits as $sectionSplit): ?>
                <?php if ($coreBox->id == $lastCoreboxId): ?>
                  <tr class="<?= ($n % 2 == 0 ? "even" : "odd") ?>">
                  <td colspan="<?= sizeof($coreBoxColumns) ?>"></td>
                <?php else: ?>
                  <?php
                    $lastCoreboxId = $coreBox->id;
                    $n++;
                  ?>
                  <tr class="<?= ($n % 2 == 0 ? "even" : "odd") ?>">
                  <?php foreach($coreBoxColumns as $column => $label): ?>
                    <td><?= array_key_exists($column, $data) ? formatColumnValue($column, $data[$column]) : formatColumnValue($column, $coreBox->{$column}) ?></td>
                  <?php endforeach; ?>
                <?php endif; ?>

                <?php foreach($sectionSplitColumns as $column => $label): ?>
                  <?php
                    $model = $sectionSplit;
                    while (preg_match("/^(.+)->(.+)$/i", $column, $matches)) {
                        $model = $model->{$matches[1]};
                        $column = $matches[2];
                    }
                    $value = $model->{$column};
                  ?>
                  <td><?= formatColumnValue($column, $value) ?></td>
                <?php endforeach; ?>
              </tr>
            <?php endforeach; ?>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php
}

?>

<?php if (sizeof($models) && $models[0] instanceof \app\models\ProjectHole): ?>
  <?php foreach ($models as $hole): ?>
      <?php
      $dataProvider = $report->getCoreboxesDataProvider($hole->id);
      $coreBoxes = $dataProvider->getModels();
      ?>
      <?php for ($page=0; $page < $dataProvider->pagination->pageCount; $page++): ?>
        <?php
          if ($page > 0) {
              $dataProvider->pagination->page = $page;
              $dataProvider->refresh();
              $coreBoxes = $dataProvider->getModels();
          }
          $lastCoreboxId = 0;
          showCoreboxes($report, $coreBoxes, $page == 0);
        ?>
      <?php endfor; ?>
  <?php endforeach ?>
<?php else: ?>
  <?php
    showCoreboxes($report, $models);
  ?>
<?php endif; ?>

<div>
    <?php foreach ($coreBoxData as $data): ?>
        <?php
        $header = $report->renderDisHeader($headerAttributes, "Core Boxes Summary");
        $extraHeaderAttr = [];
        foreach ($coreBoxColumns as $column => $label) {
            if (!in_array($column, ["remarks", "[image]", "[storage]", "[interval]"])) {
                $value = $data[$column];
                $extraHeaderAttr[$label] = formatColumnValue($column, $value);
            }
        }
        $extraHeader = $report->renderDisExtraHeader ($extraHeaderAttr);
        ?>
        <?php $archiveFile = $data["[image]"]; ?>
        <?php if ($archiveFile): ?>
            <?= $header ?>
            <?= $extraHeader ?>
        <div class="report core-box-image">
            <?php if ($archiveFile): ?>
              <a href="/files/converted/<?= $archiveFile->id ?>" target="preview"><img src="/files/converted/<?= $archiveFile->id ?>" alt=""></a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    <?php endforeach; ?>
</div>

