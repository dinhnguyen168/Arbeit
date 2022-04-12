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
 * Class CuttingsQrCodesReport.
 *
 * Cuttings label QR code.
 * This report can only be applied to CurationCuttings models.
 * Replace IPdfReport with IDirectPrintReport and update Method "getPrintCommand" to print directly.
 *
 * @package app\reports
 */
class CuttingsQrCodesReport extends Base implements ILabelsReport, IPdfReport //, IDirectPrintReport
{
    use DirectPrintTrait;

    /**
     * {@inheritdoc}
     */
    const TITLE = 'Cuttings Label';

    /**
     * {@inheritdoc}
     * This report can only be applied to Core forms.
     */
    const MODEL = 'CurationCuttings';

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
        $valid = $this->validateColumns("CurationCuttings", ['hole_id', 'igsn', 'average_depth', 'drillers_sieve', 'curator', 'sampling_datetime']) && $valid;
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
            $model->sampling_datetime = preg_replace("/:\\d+$/", "", $model->sampling_datetime);
            $x = \Yii::getAlias('@webroot') . "/report/" . $model->expedition->getIconUrl();
            $content .= $this->renderString($this->getTemplate(), ['repository' => $this->getRepository(), 'cuttings' => $model, 'report' => $this]);
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
$qrCode->setText($cuttings->igsn)
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
        <?php elseif ($cuttings->expedition): ?> 
            <img class="logo logo1" src="/report/<?= $cuttings->expedition->getIconUrl() ?>" alt="<?= $cuttings->expedition->exp_acronym ?> Logo">
        <?php endif; ?>
    </td>
    <td class="expedition"><?= $cuttings->expedition->exp_acronym ?></td>
    <td class="logo logo2" rowspan="2">
      <img class="logo logo2" src="/img/logos/default-cropped.png" alt="ICDP">
    </td>
  </tr>
  <tr>
    <!-- td bc. rowspan -->
    <td class="hole">Hole <?= $cuttings->hole->hole ?> - Cuttings</td>    
    <!-- td bc. rowspan -->
  </tr>
  <tr>
    <td colspan="3" class="combined-id">
      <?= $cuttings->combined_id ?>
    </td>
  </tr>
  <tr>
    <td class="qr"><?= '<img class="qr" src="data:'.$qrCode->getContentType().';base64,'.$qrCode->generate().'" />' ?></td>
    <td colspan="2">
      <table class="inner">
        <tr><td colspan="2" class="igsn">IGSN: <span><?= $cuttings->igsn ?></span></td></tr>
        <tr>
          <td class="depth">Depth: <?= $cuttings->average_depth ?> m</td>
          <td class="sieve">Driller‘s sieve: <?= $cuttings->drillers_sieve ?> mm</td>
        </tr>
        <tr>
          <td class="curator">Curator: <?= $cuttings->curator ?></td>
          <td class="date">Date: <?= $cuttings->sampling_datetime ?> UTC</td>
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
    width: 23%;
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
    width: 54%;
    text-align: center;
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

td.depth, td.curator {
    font-size: 3.6mm;
    padding-right: 0.3mm;
}

td.date, td.sieve {
    font-size: 3.6mm
}

img.logo {
    max-height: 16mm;
    max-width: 24mm;
}

img.qr {
    max-height: 24mm;
}


EOD;
    }

}
