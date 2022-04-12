<?php

namespace app\reports;

use app\models\CurationSample;
use app\reports\interfaces\IDirectPrintReport;
use app\reports\interfaces\ILabelsReport;
use app\reports\interfaces\IPdfReport;
use app\reports\traits\DirectPrintTrait;
use kartik\mpdf\Pdf;
use Mpdf\Mpdf;

/**
 * Class SampleQrCodesReport.
 * Replace IPdfReport width IDirectPrintReport and update Method "getPrintCommand" to automatically print
 *
 * @package app\reports
 */
class SampleQrCodesReport extends Base implements ILabelsReport, IPdfReport // , IDirectPrintReport
{
//    use DirectPrintTrait;

    /**
     * {@inheritdoc}
     */
    const TITLE = 'Sample Label';

    /**
     * {@inheritdoc}
     * This report can only be applied to Core forms.
     */
    const MODEL = '^CurationSample\d*$';

    /**
     * {@inheritdoc}
     * This reports prints only one label for the current record of the form.
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
        $valid = $this->validateColumns("CoreCore", ['hole_id']) && $valid;
        $valid = $this->validateColumns("CurationSectionSplit", ['type']) && $valid;
        $valid = $this->validateColumns("CurationSample", ['curator','igsn','sample_request_id', 'top', 'bottom', 'section_split_id']) && $valid;
        return $valid;
    }

    /**
     * {@inheritdoc}
     */
    public function generate($options = []) {
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
            $content .= $this->renderString($this->getTemplate(), ['repository' => $this->getRepository(), 'sample' => $model, 'report' => $this]);
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
$qrCode->setText($sample->igsn)
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
        <?php elseif ($sample->expedition): ?> 
            <img class="logo logo1" src="/report/<?= $sample->expedition->getIconUrl() ?>" alt="<?= $sample->expedition->exp_acronym ?> Logo">
        <?php endif; ?>
    </td>
    <td class="expedition"><?= $sample->expedition->exp_acronym ?></td>
    <td class="logo logo2" rowspan="2">
      <img class="logo logo2" src="/img/logos/default-cropped.png" alt="ICDP">
    </td>
  </tr>
  <tr>
    <!-- td bc. rowspan -->
    <td class="hole">Hole <?= $sample->hole->hole ?> - Core Sample</td>    
    <!-- td bc. rowspan -->
  </tr>
  <tr>
    <td colspan="3" class="combined-id">
      <?= $sample->sample_combined_id ?>
    </td>
  </tr>
  <tr>
    <td class="qr"><?= '<img class="qr" src="data:'.$qrCode->getContentType().';base64,'.$qrCode->generate().'" />' ?></td>
    <td colspan="2">
      <table class="inner">
        <tr><td colspan="2" class="igsn">IGSN: <span><?= $sample->igsn ?></span></td></tr>
        <tr>
          <td class="top">Top: <?= $sample->top ?> cm</td>
          <td class="date">Date: <?= $sample->sample_date ?> </td>
        </tr>
        <tr>
          <td class="bottom">Bottom: <?= $sample->bottom ?> cm</td>
          <td class="curator">Curator: <?= $sample->curator ?> </td>
		</tr>
		 <tr>
		  <td colspan="2" class="request">Request: <?= $sample->sample_request_id ?> </td>
        </tr>
        
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

td.top	{
    font-size: 3.6mm;
    padding-right: 0.3mm;
}

td.request {
    font-size: 3.6mm;
    padding-right: 0.3mm;
	padding-top: 2mm;
}

td.bottom, td.date {
    font-size: 3.6mm;
	padding-right: 0.3mm;
}

td.Curator {
    font-size: 3.6mm;
	
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

    function getPrintCommand($filePath)
    {
        return "lpr -P PDF \"$filePath\"";
    }
}
