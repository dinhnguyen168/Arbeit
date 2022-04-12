<?php

namespace app\reports;

use app\models\CoreCore;
use app\reports\interfaces\IDirectPrintReport;
use app\reports\interfaces\ILabelsReport;
use app\reports\interfaces\IPdfReport;
use app\reports\interfaces\IReport;
use app\reports\traits\DirectPrintTrait;
use kartik\mpdf\Pdf;
use Mpdf\Mpdf;

/**
 * Class CoreQrCodesReport.
 *
 * Example of a report that creates a printable label with a QR code.
 * This report can only be applied to Core forms.
 * This report prints only one label for the current record of the form.
 *
 * @package app\reports
 */
class CoreQrCodesReport extends Base implements ILabelsReport, IDirectPrintReport
{
    use DirectPrintTrait;

    /**
     * {@inheritdoc}
     */
    const TITLE = 'Print Core QR code';

    /**
     * {@inheritdoc}
     * This report can only be applied to Core forms.
     */
    const MODEL = 'CoreCore';

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
        $valid = $this->validateColumns("ProjectExpedition", ['exp_acronym']);
        $valid = $this->validateColumns("ProjectHole", ['hole']) && $valid;
        $valid = $this->validateColumns("CoreCore", ['core_ondeck', 'combined_id', 'igsn', 'top_depth', 'bottom_depth', 'hole_id']) && $valid;
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
            $content .= $this->renderString($this->getTemplate(), ['repository' => $this->getRepository(), 'core' => $model, 'report' => $this]);
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
$qrCode->setText($core->igsn)
    ->setSize(100)
    ->setPadding(0)
    ->setErrorCorrection('high')
    ->setImageType(QrCode::IMAGE_TYPE_PNG);
list($width, $height) = $report->getLabelSize()
?>
<table class="label-table">
  <tr>
    <td style="width: 50%">
    <?php if ($repository): ?>
        <img style="max-width: 50mm" src="<?= $repository["url"] ?>" alt="<?= $repository["name"] ?>">
    <?php elseif ($core->expedition): ?>
        <img style="max-width: 50mm" src="/report/<?= $core->expedition->getIconUrl() ?>" alt="<?= $core->expedition->exp_acronym ?> Logo">
    <?php endif; ?>
    </td>
    <td><?= $core->core_ondeck; ?></td>
  </tr>
  <tr>
    <td colspan="2">
      <?= sprintf("%s", $core->combined_id); ?>
    </td>
  </tr>
  <tr>
    <td colspan="2">
      <?= sprintf("%s", $core->igsn); ?>
    </td>
  </tr>
  <tr>
    <td>
        <?= '<img src="data:'.$qrCode->getContentType().';base64,'.$qrCode->generate().'" />' ?>
    </td>
    <td>
        <span><?= $core->top_depth?> - <?= $core->bottom_depth?></span>
        <span><?= $core->hole->hole ?></span>
    </td>
  </tr>
</table>
EOD;
    }

    public function getCss()
    {
        list($width, $height) = $this->getLabelSize();
        return <<<EOD
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
}

@media only screen {
    .label-table {
        border: solid black 1px;
    }
}

.label-table td {
    display: inline-block;
    padding: 2px
}
.label-table img {
    max-width: 100%;
    width: 100%;
    height: auto;
}
EOD;
    }

}
