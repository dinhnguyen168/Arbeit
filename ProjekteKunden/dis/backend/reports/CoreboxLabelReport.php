<?php

namespace app\reports;

use app\models\CurationCorebox;
use app\reports\interfaces\IDirectPrintReport;
use app\reports\interfaces\IHtmlReport;
use app\reports\interfaces\ILabelsReport;
use app\reports\interfaces\IPdfReport;
use app\reports\interfaces\IReport;
use app\reports\traits\DirectPrintTrait;
use kartik\mpdf\Pdf;
use Mpdf\Mpdf;

/**
 * Class CoreboxLabelReport.
 *
 * Mudsample label QR code.
 * This report can only be applied to CurationMudsample models.
 * Replace IPdfReport with IDirectPrintReport and update Method "getPrintCommand" to print directly.
 *
 * @package app\reports
 */
class CoreboxLabelReport extends Base implements ILabelsReport, IPdfReport //, IDirectPrintReport
{
    use DirectPrintTrait;

    /**
     * {@inheritdoc}
     */
    const TITLE = 'Corebox label';

    /**
     * {@inheritdoc}
     * This report can only be applied to Core forms.
     */
    const MODEL = 'CurationCorebox';

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
        return true;
        $valid = parent::validateReport($options);
        $valid = $this->validateColumns("CurationCorebox", []) && $valid;
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

        $models = $dataProvider->getModels();
        return $this->render(null,
            [
                'repository' => $this->getRepository(),
                'coreboxes' => $models,
                'report' => $this
            ]);
    }


    public function getCss()
    {
        list($width, $height) = $this->getLabelSize();
        return <<<EOD


body {
    font-size: 8mm;
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


td.expedition {
    width: 64%;
    text-align: center;
    padding-top: 2mm;
}

div.expedition {
    font-size: 6mm;
    font-weight: bold;
}

div.hole {
    text-align: center;
    font-size: 4mm;
    height: 5mm;
}


td.combined-id {
    text-align: center;
    font-size: 7mm; 
    padding-bottom: 2mm;
    font-weight: bold;
}


img.logo {
    max-height:16mm;
    max-width: 24mm;
}

img.qr {
    max-height: 18mm;
}

td.inner {
    text-align: center;
    font-size: 5mm;
}

td.marker {
padding-left: 1mm;
}

.circle {
    height:18mm;
    width:18mm;
    border-radius:50%;
    -moz-border-radius:50%;
    -webkit-border-radius:50%;
    font-size: 12mm;
    text-align: center;
    padding-top: 0.8mm;
}

.circle.top {
    background-color: black;
    color: white;
}

.circle.bottom {
    background-color: white;
    border:2px solid black;
    color: black;
}



EOD;
    }

}
