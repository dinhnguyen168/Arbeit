<?php

namespace app\reports;

use app\models\CoreCore;
use app\reports\interfaces\IDirectPrintReport;
use app\reports\interfaces\IHtmlReport;
use app\reports\interfaces\ILabelsReport;
use app\reports\interfaces\IPdfReport;
use app\reports\interfaces\IReport;
use app\reports\traits\DirectPrintTrait;
use kartik\mpdf\Pdf;
use Mpdf\Mpdf;

/**
 * Class SectionQrCodesReport.
 *
 * Example of a report that creates a printable label with a QR code.
 * This report can only be applied to Section forms.
 * Replace IPdfReport with IDirectPrintReport and update Method "getPrintCommand" to print directly.
 *
 * @package app\reports
 */
class SectionQrCodesReport extends Base implements ILabelsReport, IPdfReport //, IDirectPrintReport
{
    use DirectPrintTrait;

    /**
     * {@inheritdoc}
     */
    const TITLE = 'Section label';

    /**
     * {@inheritdoc}
     * This report can only be applied to Core forms.
     */
    const MODEL = 'CurationSectionSplit';

    /**
     * {@inheritdoc}
     */
    const SINGLE_RECORD = null;

    private $_pdf;

    function initPdf()
    {
        $pdf = new Pdf([
            // set to use core fonts only
            'mode' => Pdf::MODE_CORE,
            // A4 paper format
            'format' => $this->getLabelSize(),
            'defaultFont' => 'dejavusans',
            'marginTop' => 0,
            'marginRight' => 0,
            'marginBottom' => 0,
            'marginLeft' => 0,
        ]);
        $mPdf = $pdf->api; // fetches mpdf api
        $this->_pdf = $mPdf;
    }

    function getPdf()
    {
        return $this->_pdf;
    }

    public function getPdfFilename()
    {
        return self::TITLE;
    }

    public function getJs()
    {
        return '';
    }

    /**
     * @inheritDoc
     */
    function getLabelSize()
    {
        return [104, 56];
    }


    /**
     * {@inheritdoc}
     */
    function validateReport($options) {
        $valid = parent::validateReport($options);
        $valid = $this->validateColumns("ProjectHole", ['hole']) && $valid;
        $valid = $this->validateColumns("CoreCore", ['hole_id']) && $valid;
        $valid = $this->validateColumns("CoreSection", ['core_id', 'combined_id', 'top_depth', 'bottom_depth']) && $valid;
        $valid = $this->validateColumns("CurationSectionSplit", ['section_id', 'igsn', 'mcd_top_depth', 'mcd_bottom_depth', 'curator']) && $valid;
        return $valid;
    }

    /**
     * {@inheritdoc}
     */
    public function generate($options = []) {
        $this->controller->layout = "empty.php";
        $this->disableYiiDebugToolbar();
        $dataProvider = $this->getDataProvider($options);
        $this->content = $this->_generate($dataProvider);
    }

    /**
     * Generates the report for all records in the dataProvider
     * @param \yii\data\ActiveDataProvider $dataProvider
     * @return string HTML of the rendered report
     */
    protected function _generate($dataProvider)
    {
        $dataProvider->pagination = false;
        $singleMode = ($dataProvider->getTotalCount() == 1);

        $content = "";
        foreach ($dataProvider->getModels() as $model) {
            $x = \Yii::getAlias('@webroot') . "/report/" . $model->expedition->getIconUrl();
            $content .= $this->renderString($this->getTemplate(), ['repository' => $this->getRepository(), 'split' => $model, 'report' => $this]);
        }
        return $content;
    }

    /**
     * Returns the template to render the page with one label.
     * @return string Template
     */
    protected function getTemplate()
    {
        return <<<'EOD'
<?php
use CodeItNow\BarcodeBundle\Utils\QrCode;
$qrCode = new QrCode();
$qrCode->setText($split->igsn)
    ->setSize(300)
    ->setPadding(0)
    ->setErrorCorrection('high')
    ->setImageType(QrCode::IMAGE_TYPE_PNG);
list($width, $height) = $report->getLabelSize()
?>
<table class="label-table">
  <tr>
    <td class="logo logo1" rowspan="2">
        <?php if ($repository): ?>
            <img class="logo logo1" src="<?= $repository["url"] ?>" alt="<?= $repository["name"] ?>">
        <?php elseif ($split->expedition): ?> 
            <img class="logo logo1" src="/report/<?= $split->expedition->getIconUrl() ?>" alt="<?= $split->expedition->exp_acronym ?> Logo">
        <?php endif; ?>
    </td>
    <td class="expedition"><?= $split->expedition->exp_acronym ?></td>
    <td class="logo logo2" rowspan="2">
      <img class="logo logo2" style="max-width: 50mm" src="/img/logos/default-cropped.png" alt="ICDP">
    </td>
  </tr>
  <tr>
    <!-- td bc. rowspan -->
    <td class="hole">Hole <?= $split->hole->hole ?> - Section</td>    
    <!-- td bc. rowspan -->
  </tr>
  <tr>
    <td colspan="3" class="combined-id">
      <?= $split->combined_id ?>
    </td>
  </tr>
  <tr>
    <td class="qr"><?= '<img class="qr" src="data:'.$qrCode->getContentType().';base64,'.$qrCode->generate().'" />' ?></td>
    <td colspan="2">
      <table class="inner">
        <tr><td colspan="2" class="igsn">IGSN: <span><?= $split->igsn ?></span></td></tr>
        <tr>
          <td class="mcd mcd-top">MCD Top: <?= round($split->mcd_top_depth, 1) ?> m</td>
          <td class="date">Date: <?= date('Y-m-d') ?></td>
        </tr>
        <tr>
          <td class="mcd mcd-bottom">MCD Bottom: <?= round($split->mcd_bottom_depth, 1) ?> m</td>
          <td class="curator">Curator: <?= $split->curator ?></td>
        </tr>
        <tr><td colspan="2" class="qr-hint">&larr; Scan IGSN QR-Code to mDIS for full data record.</td></tr>
      </table>
    </td>
  </tr>
</table>
EOD;
    }

    public function getCss()
    {
        list($width, $height) = $this->getLabelSize();
        return <<<EOD


body {
    font-size: 4mm;
}

@page {
    size: {$width}mm {$height}mm;
    margin: 0mm;
}

.label-table {
    width: {$width}mm;
    height: {$height}mm;
    max-width: {$width}mm;
    max-height: {$height}mm;
    display: block;
    overflow: hidden;
    box-sizing: border-box;
    page-break-inside: avoid;
    font-family: Sans-Serif;
}


@media only screen {
    .label-table {
        border: solid black 1px;
    }
}


/* Show borders in table */
/*
table, table tr, table tr td {
    border-collapse: collapse;
    border: 1px solid gray;
}
*/

table td {
    vertical-align: top;
}

td.logo {
    width: 25%;
		vertical-align: middle;
}

td.hole {
    text-align: center;
}

td.combined-id {
    text-align: center;
    font-size: 7mm; 
    padding-bottom: 2mm;
}

td.expedition {
    text-align: center;
    width: 50%;
    padding-top: 2mm;
    font-size: 6mm;
    font-weight: bold;
}

table.inner {
    width: 100%;
}

td.igsn {
    padding-bottom: 2mm;
}

td.igsn span {
    font-weight: bold;
}

td.qr-hint {
    padding-top: 2mm;
    font-size: 3mm;
}

td.mcd {
    padding-right: 3mm;
}

img.logo {
    max-height: 16mm;
		max-width: 24mm;
}

img.qr {
    max-height: 25mm;
}


EOD;
    }

}
