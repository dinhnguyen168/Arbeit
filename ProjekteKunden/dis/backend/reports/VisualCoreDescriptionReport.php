<?php
namespace app\reports;

use app\models\CoreSection;
use app\reports\interfaces\IHtmlReport;

/**
 * Class VisualCoreDescriptionReport
 *
 * @package app\reports
 */
class VisualCoreDescriptionReport extends Base implements IHtmlReport
{
    /**
     * {@inheritdoc}
     */
    const TITLE = 'Visual Core Description';

    /**
     * {@inheritdoc}
     * This reports can only be used for CoreCore forms.
     */
    const MODEL = '^(CoreCore|CoreSection)';

    /**
     * {@inheritdoc}
     * This report can be used for single or multiple records.
     */
    const SINGLE_RECORD = null;


    /**
     * {@inheritdoc}
     */
    function validateReport($options) {
        $valid = parent::validateReport($options);
        $valid = $this->validateColumns("CoreCore", ['core']) && $valid;
        $valid = $this->validateColumns("CoreSection", ['core_id', 'section', 'top_depth', 'bottom_depth', 'section_length']) && $valid;
        return $valid;
    }


    /**
     * {@inheritdoc}
     */
    public function generate($options = []) {
        $dataProvider = null;
        if ($options['model'] == 'CoreSection') {
            $dataProvider = $this->getDataProvider($options);
        }
        else {
            $subQuery = $this->getDataProvider($options)->query;
            $subModelClass = $subQuery->modelClass;
            $subQuery->select($subModelClass::tableName() . '.id');
            $query = CoreSection::find()->andWhere(['IN', 'core_id', $subQuery]);
            $options['model'] = 'CoreSection';
            $dataProvider = $this->getDataProvider($query);
        }
        $this->content = $this->_generate($dataProvider);
    }

    protected function _generate($dataProvider) {
        $dataProvider->pagination = false;

        $sections = $dataProvider->getModels();
        if (sizeof($sections)) {
            $ancestorValues = $this->getAncestorValues($sections[0]);
            $this->setExpedition($sections[0]);
        }
        return $this->render(null, [
            'report' => $this,
            'sections' => $sections,
            'ancestorValues' => $ancestorValues
        ]);
    }

    function getCss() {
        $cssFile = \Yii::getAlias("@webroot/css/report.css");
        $stylesheet = file_get_contents($cssFile);
        return $stylesheet . <<<'EOB'
html, body {height: 100%;}
#ruler {
  /* resetting the counter so every <ol>
     has an independent count: */
  list-style-type: none;
  counter-reset: marker -1;
  float: right;
  height: 100%;
  margin-top: -1px;
  padding-left: 3em;
  padding-top: 0;
  position: relative;
  z-index: 1; }
  #ruler li {
    list-style-type: none;
    /* 'real world' measurements are perhaps not
    entirely faithful on screen: */
    border-top: 1px solid #999;
    /* including the border in the height of the element: */
    box-sizing: border-box;
    width: 0.5em;
    /* incrementing the counter: */
    counter-increment: marker 1;
    /* to position the counter relative
    to the <li>: */
    position: relative; }
    #ruler li.deci {
      margin-left: -0.5em;
      margin-right: calc(-100vw + 5.5em);
      width: calc(100vw - 5.5em); }
      @media print {
        #ruler li.deci {
          margin-right: calc(-186mm + 2.5em);
          width: calc(186mm - 1.75em); } }
      #ruler li.deci:first-child {
        margin-right: 0;
        width: 1em; }
      #ruler li.deci::before {
        font-size: 0.8em;
        font-weight: bold;
        /* specifying the counter to use: */
        content: counter(marker);
        margin-right: 0.2em;
        padding-right: 0.25em;
        /* positioning the pseudo-element that
        contains the counter: */
        position: absolute;
        right: 100%;
        text-align: right;
        /* vertically-centering it alongside the
            top border: */
        top: -0.6em;
        /* moving it the full width of the element,
            outside of the right side of the element: */
        width: 3em; }
      #ruler li.deci:first-child::before {
        white-space: nowrap; }
    #ruler li:last-child {
      height: 0;
      border-right: none; }

@media print {
  #ruler {
    padding-left: 2.25em; } }

.report-wrapper {
    height: auto;
}
    
@media print {
  .report-wrapper {
    height: 260mm;
    overflow: hidden;
    position: relative;
    width: 186mm; } }

@media print {
  header.reports {
    height: 40mm;
    padding: 0 0 1em 0;
    width: 186mm; } }

.visual-core-description-report {
  display: flex;
  /*height: 64vh;*/
  position: relative; }
  @media print {
    .visual-core-description-report {
      height: calc(290mm - 40mm - 25mm - 1em);
      padding: 0;
      position: absolute;
      width: 186mm; } }
  .visual-core-description-report table {
    border-collapse: collapse;
    /*height: 100%;*/
    width: 100%; }
    @media print {
      .visual-core-description-report table {
        border-collapse: separate;
        border-spacing: 1px;
        font-size: 14px;
        height: calc(290mm - 40mm - 25mm - 1em); } }
    .visual-core-description-report table thead {
      height: 7em; }
    .visual-core-description-report table th, .visual-core-description-report table td {
      border-right: 1px solid black;
      width: 5%; }
      .visual-core-description-report table th:last-child, .visual-core-description-report table td:last-child {
        border-right: none; }
    .visual-core-description-report table th {
      border-bottom: 1px solid black;
      font-weight: 600;
      font-size: 12px;
      vertical-align: bottom;
      padding: 0.25em; }
      .visual-core-description-report table th.scale-unit {
        border-bottom: none;
        padding-right: 0.4em; }
      .visual-core-description-report table th.vertical div {
        height: 6em;
        padding-bottom: 0.5em;
        writing-mode: vertical-lr; }
    .visual-core-description-report table td {
      height: calc(297mm - 40mm - 24mm - 12em);
      vertical-align: top; }
      .visual-core-description-report table td.scale {
        padding: 0; }
      .visual-core-description-report table td img {
        max-width: 100%;
        width: auto;
        max-height: calc(100vh - 21em); }
  @media (min-width: 992px) {
    .visual-core-description-report body {
      font-size: 1.41vw !important; } }
  @media (min-width: 1419px) {
    .visual-core-description-report body {
      font-size: 20px !important; } }
  .visual-core-description-report .section {
    height: 100%;
    margin-right: 0.5em;
    position: relative; }
    @media print {
      .visual-core-description-report .section {
        height: 100%; } }
  .visual-core-description-report .section .head {
    background-color: #FFF;
    border: 2px solid #999;
    border-bottom: none;
    height: 1.5em;
    padding: 0.2em;
    position: relative;
    text-align: center;
    z-index: 1; }
  .visual-core-description-report .section .head span {
    font-size: 0.8em;
    font-weight: bold; }
  .visual-core-description-report .section img {
    background-color: #CCC;
    border: 1px solid #999;
    max-height: initial;
    position: absolute;
    vertical-align: top;
    width: 100%;
    z-index: 2; }
  @media print {
    .visual-core-description-report a[href]:after {
      content: none !important; } }
  .visual-core-description-report #footer {
    margin-top: 1em; }
  .visual-core-description-report .footer {
    margin-left: 5em; }
  .visual-core-description-report .footer span {
    font-size: 0.8em; }

@media print {
  @page {
    margin: 12mm;
    size: 210mm 297mm; }
  body {
    height: 260mm;
    margin: 0;
    width: 186mm; } }

EOB;
}

}
