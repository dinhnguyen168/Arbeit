<?php
namespace app\reports;

use app\reports\interfaces\IHtmlReport;

/**
 * Class CoreOverviewReport
 *
 * @package app\reports
 */
class CoreOverviewReport extends Base implements IHtmlReport
{
    /**
     * {@inheritdoc}
     */
    const TITLE = 'Core Overview';

    /**
     * {@inheritdoc}
     * This reports can only be used for CoreCore forms.
     */
    const MODEL = 'CoreCore';

    /**
     * {@inheritdoc}
     * This reports can be used for single or multiple records.
     */
    const SINGLE_RECORD = null;


    /**
     * {@inheritdoc}
     */
    function validateReport($options) {
        $valid = parent::validateReport($options);
        $valid = $this->validateColumns("CoreCore", ['core_top_depth', 'hole', 'core']) && $valid;
        $valid = $this->validateColumns("CoreSection", ['top_depth', 'bottom_depth', 'section']) && $valid;
        $valid = $this->validateColumns("CurationSectionSplit", ['corebox']) && $valid;
        $valid = $this->validateColumns("ArchiveFile", ['upload_date', 'type', 'number', 'mime_type', 'id']) && $valid;
        return $valid;
    }

    /**
     * {@inheritdoc}
     */
    public function generate($options = []) {
        if (isset($options['section_id'])) {
            $section = \app\models\CoreSection::find()->where(['id' => $options['section_id']])->one();
            $this->content = $this->_generateSingleSection($section);
        }
        else {
            $dataProvider = $this->getDataProvider($options);
            $this->content = $this->_generate($dataProvider);
        }
        return $this->content;
    }

    protected function _generate($dataProvider) {
        $dataProvider->pagination = false;

        $models = $dataProvider->getModels();
        $ancestorValues = $this->getAncestorValues($models[0]);
        $this->setExpedition($models[0]);

        return $this->render(null, [
            "report" => $this,
            "cores" => $models,
            "ancestorValues" => $ancestorValues
        ]);
    }

    public function getCss() {
        $cssFile = \Yii::getAlias("@webroot/css/report.css");
        $stylesheet = file_get_contents($cssFile);
        return $stylesheet . <<<'EOB'
html, body {height: 100%;}

.core-overview-single-section-report {
    display: flex;
}


.core-overview-report {
  display: flex;
  flex-wrap: nowrap;
  height: calc(100% - 23.25em); 
  min-height: 20em;
  }
  @media (min-width: 768px) {
    .core-overview-report {
      height: calc(100% - 15.25em); } }
  @media print {
    .core-overview-report {
      height: calc(100% - 15.25em); } }

@media (min-width: 992px) {
  body {
    font-size: 1.41vw !important; } }
@media (min-width: 1419px) {
  body {
    font-size: 20px !important; } }
    
#ruler {
  /* resetting the counter so every <ol>
     has an independent count: */
  list-style-type: none;
  counter-reset: marker -10;
  height: calc(100%);
  margin-top: 1.5em;
  margin-right: 0.5em;
  padding-left: 3em;
  padding-top: 0; }
  #ruler li {
    list-style-type: none;
    /* 'real world' measurements are perhaps not
    entirely faithful on screen: */
    border-top: 1px solid #999;
    /* including the border in the height of the element: */
    box-sizing: border-box;
    width: 0.5em;
    /* incrementing the counter: */
    counter-increment: marker 10;
    /* to position the counter relative
    to the <li>: */
    position: relative;
    border-right: 1px solid #999; }
    #ruler li::before {
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
    #ruler li:first-child::before {
      content: counter(marker) " cm";
      white-space: nowrap; }
    #ruler li:last-child {
      height: 0; }

.section {
  height: 100%;
  margin-right: 0.5em;
  }
  .section .head {
    background-color: #FFF;
    border: 2px solid #999;
    border-bottom: none;
    height: 1.5em;
    padding: 0.2em;
    position: relative;
    text-align: center;
    z-index: 1; }
    .section .head span {
      font-size: 0.8em;
      font-weight: bold; }
  .section img {
    background-color: #CCC;
    border: 2px solid #999;
    position: relative;
    vertical-align: top;
    width: 100%;
    z-index: 0; }

@media print {
  a[href]:after {
    content: none !important;
  }
}

#footer {
    margin-top: 1em;
}
.footer {
    margin-left: 5em;
}

.footer span {
    font-size: 0.8em;
}


EOB;
    }




    protected function _generateSingleSection($section) {
        $ancestorValues = $this->getAncestorValues($section);
        $this->setExpedition($section);

        $aSectionImageUrl = $this->getSectionImageUrl($section);

        return $this->render(static::getClassWithoutNamespace() . "-Single.view.php",
                    [
                        "section" => $section,
                        "maxLength" => ceil($section->section_length * 100 / 10) * 10,
                        "sectionImageUrl" => $aSectionImageUrl,
                        "scaleFactor" => "10em",
                        'header' => $this->renderDisHeader([
                            'Site' => $ancestorValues['site'][0],
                            'Hole' => $ancestorValues['hole'][0],
                            'Core' => $ancestorValues['core'][0],
                            'Top depth' => $section->top_depth . " m",
                            'Bottom depth' => $section->bottom_depth . " m"
                        ], "Core Overview (Single section)", $this->expedition)
                    ]);
    }


}
