<?php
/**
 *
 * Parameters for the view:
 * @var $report
 * @var $coreboxes
 * @var $repository
 **/

use CodeItNow\BarcodeBundle\Utils\QrCode;

function getSectionCombinedIdPart($section) {
    $c = $section->combined_id;
    return preg_replace("/^.+(_[^_]+_[^_]+)$/", "$1", $section->combined_id);
}



foreach ($coreboxes as $corebox):
    $qrCode = new QrCode();
    $qrCode->setText($corebox->corebox_combined_id ? $corebox->corebox_combined_id : "0")
        ->setSize(300)
        ->setPadding(0)
        ->setErrorCorrection('high')
        ->setImageType(QrCode::IMAGE_TYPE_PNG);
    list($width, $height) = $report->getLabelSize()
?>


<?php foreach (["top", "bottom"] as $topBottom): ?>
<?php foreach (["icdp"] as $logo2): ?>
<?php
        $showValue = ($logo2 == "icdp" ? "depth" : "section");
        $firstSection = null;
        $lastSection = null;
        foreach ($corebox->curationSectionSplits as $sectionSplit) {
          $section = $sectionSplit->section;
          if ($firstSection == null || $section->combined_id < $firstSection->combined_id) {
            $firstSection = $section;
          }
          if ($lastSection == null || $section->combined_id > $lastSection->combined_id) {
              $lastSection = $section;
          }
        }

        $expeditionIconUrl = null;
        if ($corebox->expedition) {
            $expeditionIconUrl = preg_replace("/\\.png$/", "-full.png", $corebox->expedition->getIconUrl());
        }

  ?>

<table class="label-table">
  <tr>
<td class="logo logo1">
        <?php if ($corebox->expedition): ?>
            <img class="logo logo1" src="/report/<?= $corebox->expedition->getIconUrl() ?>" alt="<?= $corebox->expedition->exp_acronym ?> Logo">
        <?php elseif ($repository): ?>
            <img class="logo logo1" src="<?= $repository["url"] ?>" alt="<?= $repository["name"] ?>">
        <?php endif; ?>
    </td>
    <td class="expedition">
      <div class="expedition"><?= $corebox->expedition->exp_acronym ?></div>
      <div class="hole">Hole <?= $corebox->hole->hole ?> - Core Box</div>
    </td>
    <td class="logo logo2">
        <?php if ($repository && $logo2 == "icdp"): ?>
            <img class="logo logo1" src="<?= $repository["url"] ?>" alt="<?= $repository["name"] ?>">
        <?php else: ?>
            <img class="logo logo2" src="/img/logos/default-cropped.png" alt="ICDP">
        <?php endif; ?>
    </td>
  </tr>
  <tr>
    <td colspan="3" class="combined-id">
      <?= $corebox->corebox_combined_id ?>
    </td>
  </tr>
  <tr>
    <td class="qr"><?= '<img class="qr" src="data:'.$qrCode->getContentType().';base64,'.$qrCode->generate().'" />' ?></td>
    <td class="inner">

      <div class="label">Depth [m]:<?= round($firstSection->top_depth + $firstSection->mcd_offset, 2) ?> to <?= round($lastSection->top_depth + $lastSection->mcd_offset + $lastSection->section_length, 2) ?></div>

        <div class="label">Sections:<?= getSectionCombinedIdPart($firstSection) ?> to <?= getSectionCombinedIdPart($lastSection) ?></div>

    </td>
    <td class="marker">
      <?php if ($topBottom == "bottom"): ?>
        <svg height="8mm" width="8mm">
          <circle cx="50%" cy="50%" r="45%" stroke="black" stroke-width="2" fill="white" />
          <text x="50%" y="50%" text-anchor="middle" stroke="black" stroke-width="0.1mm" dy="2.2mm" font-size="6mm">B</text>
        </svg>
      <?php else: ?>
        <svg height="8mm" width="8mm">
          <circle cx="50%" cy="50%" r="45%" fill="black" />
          <text x="50%" y="50%" text-anchor="middle" fill="white" stroke="white" stroke-width="0.3mm" dy="2.3mm" font-size="5.5mm">T</text>
        </svg>
      <?php endif; ?>
    </td>
  </tr>
</table>

<?php endforeach; ?>
<?php endforeach; ?>
<?php endforeach; ?>
