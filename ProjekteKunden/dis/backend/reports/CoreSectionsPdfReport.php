<?php

namespace app\reports;

use app\reports\interfaces\IPdfReport;
use kartik\mpdf\Pdf;
use Mpdf\Mpdf;

/**
 * Class CoreSectionsPdfReport
 *
 * Example of a reports the outputs a PDF file.
 * Same report as CoreSectionsReport but formatted as PDF.
 *
 * @package app\reports
 */
class CoreSectionsPdfReport extends Base implements IPdfReport
{
    /**
     * {@inheritdoc}
     */
    const TITLE = 'Cores with Sections (PDF)';

    /**
     * {@inheritdoc}
     * This report can only be applied to CoreCore forms.
     */
    const MODEL = 'CoreCore';

    /**
     * {@inheritdoc}
     */
    const SINGLE_RECORD = null;

    /**
     * {@inheritdoc}
     */
    const REPORT_TYPE = 'export';

    private $_pdf;
    private $_pdfFilename;

    public function getPdfFilename()
    {
        return $this->_pdfFilename;
    }

    function initPdf()
    {
        $pdf = new Pdf([
            // set to use core fonts only
            'mode' => Pdf::MODE_CORE,
            // A4 paper format
            'format' => Pdf::FORMAT_A4,
            // portrait orientation
            'orientation' => Pdf::ORIENT_LANDSCAPE,
            'defaultFont' => 'dejavusans',
            'marginTop' => 40
        ]);
        $this->_pdf = $pdf->api; // fetches mpdf api
    }

    function getPdf()
    {
        return $this->_pdf;
    }

    function getCss()
    {
        $cssFile = \Yii::getAlias("@webroot/css/report.css");
        $stylesheet = file_get_contents($cssFile);
        return $stylesheet;
    }


    /**
     * {@inheritdoc}
     */
    function validateReport($options) {
        $valid = parent::validateReport($options);
        $valid = $this->validateColumns("CoreCore", ['drilled_length', 'core', 'core_type', 'core_ondeck', 'core_top_depth', 'core_bottom_depth', 'core_recovery', 'core_recovery_pc']) && $valid;
        $valid = $this->validateColumns("CoreSection", ['section', 'section_length', 'curated_length', 'top_depth', 'bottom_depth', 'comment']) && $valid;
        return $valid;
    }

    /**
     * {@inheritdoc}
     */
    public function generate($options = []) {
        $options['model'] = 'CoreCore';
        $dataProvider = $this->getDataProvider($options);
        $dataProvider->pagination = false;

        $ancestorValues = [];
        $totalDrilledLength = 0;
        $totalDrillRecovery = 0;
        $sectionCount = 0;
        foreach ($dataProvider->getModels() as $model) {
            if (sizeof($ancestorValues) == 0) {
                $ancestorValues = $this->getAncestorValues($model);
            }
            $sectionCount += count($model->coreSections);
            $totalDrilledLength += $model->drilled_length;
            $totalDrillRecovery += $model->core_recovery;
        }

        if (sizeof($ancestorValues) == 0) {
            $ancestorValues = [
                'site' => ['', ''],
                'hole' => ['', ''],
                'expedition' => ['', '']
            ];
        }

        $header = $this->renderPartialString($this->getHeaderTemplate(), [
            'big_title' => 'Expedition ' . $ancestorValues['expedition'][0],
            'title' => 'CORE / SECTION SUMMARY',
            'expedition' => $ancestorValues['expedition'][0],
            'site' => $ancestorValues['site'][0],
            'hole' => $ancestorValues['hole'][0],
            'info_list' => [
                'total drilled length' => \Yii::$app->formatter->asDecimal($totalDrilledLength, 2).' m',
                'core recovery' => \Yii::$app->formatter->asDecimal($totalDrillRecovery, 2).' m    '.\Yii::$app->formatter->asPercent($totalDrillRecovery/$totalDrilledLength),
                'Cores' => $dataProvider->totalCount,
                'Sections' => $sectionCount,
            ]
        ]);

        $this->getPdf()->SetHTMLHeader($header);
        $this->getPdf()->SetFooter('Page {PAGENO}');
        $this->_pdfFilename = 'Core_Section_Report_Exp-'.$ancestorValues['expedition'][0].'_Site-'.$ancestorValues['site'][0].'_Hole-'.$ancestorValues['hole'][0].'.pdf';

        $this->content = $this->renderPartialString($this->getTemplate(), ['dataProvider' => $dataProvider, 'header' => $header]);
    }


    /**
     * Return the template to render the head of the report
     * @return string Template
     */
    protected function getHeaderTemplate() {
        return <<<'EOD'
<h1><?= $big_title?></h1>

<table class="header" cellspacing="0">
    <tr>
        <td style="width:33%;text-align: left">mDIS: Data-Report</td>
        <td style="width:34%;text-align: center"><?= $title?></td>
        <td style="width:33%;text-align: right"></td>
    </tr>
    <tr>
        <td colspan="3">
            <table class="extra" cellspacing="0" cellpadding="0">
                <tr>
                    <td class="big">
                        <u>Expedition:</u> <?= $expedition ?>
                    </td>
                    <td class="big">
                        <u>Site:</u> <?= $site ?>
                    </td>
                    <td class="big">
                        <u>Hole:</u> <?= $hole ?>
                    </td>
                    <td class="normal">
                        <?php foreach ($info_list as $label => $value): ?>
                            <span style="font-size: 3mm; font-weight: normal; white-space: nowrap;"><?= $label?>: <?= $value?></span>&nbsp;&nbsp;&nbsp;
                        <?php endforeach; ?>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>        
EOD;
    }


    /**
     * Returns the template to render the reports data with.
     * @return string Template
     */
    protected function getTemplate() {
        $this->getView()->registerCssFile("@web/css/report.css");
        return <<<'EOD'
<div class="core-section-report">
    <table class="report" >
        <thead>
        <tr>
            <th class="blue">Core</th>
            <th class="blue" style="width: 35mm;">On-Deck</th>
            <th class="blue">Core Top Depth [m]</th>
            <th class="blue">Core Bottom Depth [m]</th>
            <th class="blue">Length Cored [m]</th>
            <th class="blue">Length Recovered [m]</th>
            <th class="blue">Core Recovered [%]</th>

            <th class="red">Section Number</th>
            <th class="red">Section Length [m]</th>
            <th class="red">Curated Length [m]</th>
            <th class="red">Top Depth [m]</th>
            <th class="red">Bottom Depth [m]</th>
            <th class="red">Section Remarks</th>

        </tr>
        </thead>
        <tbody>
        <?php foreach ($dataProvider->getModels() as $core): ?>
            <?php $sections = $core->coreSections; ?>
            <tr>
                <td><?= $core->core ?> - <?= $core->core_type ?></td>
                <td><?= $core->core_ondeck ?></td>
                <td><?= $core->core_top_depth ?></td>
                <td><?= $core->core_bottom_depth ?></td>
                <td><?= $core->drilled_length ?></td>
                <td><?= $core->core_recovery ?></td>
                <td><?= \Yii::$app->formatter->asDecimal($core->core_recovery_pc, 2) ?></td>
                <td colspan="6" style="color: #999;"><?= count($sections) ?> Section/s</td>
            </tr>
            <?php foreach ($sections as $section): ?>
                <tr>
                    <td class="no-border" colspan="7">&nbsp;</td>
                    <td><?= $section->section ?></td>
                    <td><?= $section->section_length ?></td>
                    <td><?= $section->curated_length ?></td>
                    <td><?= $section->top_depth ?></td>
                    <td><?= $section->bottom_depth ?></td>
                    <td style="text-align: left"><?= $section->comment ?></td>
                </tr>
                // 'id' => '10',
                // 'core_id' => '1',
                // 'section' => '1',
                // 'combined_id' => '1234_1_A_1_1',
                // 'top_depth' => '0',
                // 'section_length' => '0.98',
                // 'bottom_depth' => '0.98',
                // 'curator' => 'kunkelc',
                // 'igsn' => 'ICDP1234ESA0001',
                // 'section_state' => NULL,
                // 'curated_length' => '0.98',
                // 'comment' => NULL,
            <?php endforeach ?>
            <tr>
                <td class="no-border" colspan="13">&nbsp;</td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
EOD;
    }
}
