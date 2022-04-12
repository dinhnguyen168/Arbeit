<?php
namespace app\reports;

use app\reports\interfaces\IHtmlReport;

/**
 * Class HoleOverviewReport
 *
 * @package app\reports
 */
class HoleOverviewReport extends Base implements IHtmlReport
{
    /**
     * {@inheritdoc}
     */
    const TITLE = 'Hole Overview';

    /**
     * {@inheritdoc}
     * This reports can only be used for CoreCore forms.
     */
    const MODEL = 'ProjectHole';

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
        $valid = $this->validateColumns("ProjectHole", ['hole', ]) && $valid;
        $valid = $this->validateColumns("CoreCore", ['hole_id', 'core', 'core_type', 'core_top_depth', 'core_bottom_depth', 'drilled_length']) && $valid;
        $valid = $this->validateColumns("CoreSection", ['core_id', 'top_depth', 'bottom_depth', 'section_length']) && $valid;
        return $valid;
    }

    /**
     * {@inheritdoc}
     */
    public function generate($options = []) {
        if (isset($options['core_id'])) {
            $core = \app\models\CoreCore::find()->where(['id' => $options['core_id']])->one();
            $this->content = $this->_generateSingleCore($core);
        }
        else {
            $dataProvider = $this->getDataProvider($options);
            $this->content = $this->_generate($dataProvider);
        }
    }

    protected function _generate($dataProvider) {
        $dataProvider->pagination = false;

        $ancestorValues = [];
        $holes = $dataProvider->getModels();
        if (sizeof($holes)) {
            $ancestorValues = $this->getAncestorValues($holes[0]);
            $this->setExpedition($holes[0]);
        }
        return $this->render(null, [
            "report" => $this,
            "holes" => $holes,
            "ancestorValues" => $ancestorValues
        ]);
    }

    function getJs()
    {
        return <<<'EOB'
            function zoomCore(coreId) {
                window.open ("?core_id=" + coreId, "Zoom");
            }
EOB;
    }

    public function getCss() {
        $cssFile = \Yii::getAlias("@webroot/css/report.css");
        $stylesheet = file_get_contents($cssFile);
        return $stylesheet . <<<'EOB'
html, body {height: 100%;}

.hole-overview-report {
  display: flex;
  flex-wrap: nowrap;
  height: calc(100% - 23.25em); 
  min-height: 20em;
  }
  @media (min-width: 768px) {
    .hole-overview-report {
      height: calc(100% - 15.25em); } }
  @media print {
    .hole-overview-report {
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
  cursor: pointer;
  margin-right: 0.3em;
  }
  .section .head {
    background-color: #FFF;
    border: 1px solid #999;
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
    border-left: 1px solid #999;
    border-right: 1px solid #999;
    position: relative;
    vertical-align: top;
    width: 100%;
    display: block;
    z-index: 0; }
  .section img:last-child, .section img.no-img {
    border-bottom: 1px solid #999;
  }

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

.hole-overview-single-core-report {
    display: flex;
}
.hole-overview-single-core-report .section {
    min-width: 8em;
}


EOB;
    }



    protected function _generateSingleCore($core) {
        $ancestorValues = $this->getAncestorValues($core);
        $this->setExpedition($core);

        $sectionImageUrls = [];
        $coreLength = 0;
        foreach ($core->coreSections as $section) {
            $coreLength += $section->section_length;
            $sectionImageUrls[$section->id] = $this->getSectionImageUrl($section);
        }

        return $this->render("HoleOverviewReport-Single.view.php",
            [
                "core" => $core,
                "coreLength" => $coreLength,
                "maxLength" => ceil($coreLength * 100 / 10) * 10,
                "sectionImageUrls" => $sectionImageUrls,
                "scaleFactor" => "10em",
                'header' => $this->renderDisHeader([
                    'Site' => $ancestorValues['site'][0],
                    'Hole' => $ancestorValues['hole'][0],
                    'Core' => $core->core . " " . $core->core_type,
                    'Top depth' => $core->core_top_depth . " m",
                    'Bottom depth' => $core->core_bottom_depth . " m"
                ], "Hole Overview (Single core)", $this->expedition)
            ]);
    }


}
