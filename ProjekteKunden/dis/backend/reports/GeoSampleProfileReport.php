<?php
namespace app\reports;

use app\reports\interfaces\IHtmlReport;

/**
 * Class GeoSampleProfileReport
 *
 * @package app\reports
 */
class GeoSampleProfileReport extends Base implements IHtmlReport
{
    /**
     * {@inheritdoc}
     */
    const TITLE = 'Geo Sample Profile';

    /**
     * {@inheritdoc}
     * This reports can only be used for CoreCore forms.
     */
    const MODEL = '(CoreCore|CoreSection)';

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
        $valid = $this->validateColumns("CoreSection", ['core_id', 'section', 'top_depth', 'bottom_depth', 'section_length']) && $valid;
        $valid = $this->validateColumns("CurationSectionSplit", ['section_id', 'origin_split_id', 'type']) && $valid;
        $valid = $this->validateColumns("CurationSample", ['section_split_id', 'split_fraction_taken', 'bottom', 'top', 'mcd_top_depth', 'sample_request_id', 'igsn'/*, 'box', 'slot', 'position'*/]) && $valid;
        return $valid;
    }


    /**
     * {@inheritdoc}
     */
    public function generate($options = []) {
        $dataProvider = $this->getDataProvider($options);
        $modelClass = $this->getModelClass($options);
        $prevModel = null;
        $nextModel = null;

        if ($modelClass != "app\\models\\CoreSection") {
            if (isset($options["id"]) && $dataProvider->getCount() == 1) {
                $core = $dataProvider->getModels()[0];
                $prevModel = \app\models\CoreCore::find()
                    ->andWhere(['and',
                        ['hole_id' => $core->hole_id],
                        ['<', 'core', $core->core]])
                    ->orderBy(['core' => SORT_DESC])
                    ->one();
                $nextModel = \app\models\CoreCore::find()
                    ->andWhere(['and',
                        ['hole_id' => $core->hole_id],
                        ['>', 'core', $core->core]])
                    ->orderBy(['core' => SORT_ASC])
                    ->one();
            }


            $subQuery = $dataProvider->query->select("core_core.id");
            $query = \app\models\CoreSection::find();
            switch (preg_replace("/^.+\\\\/", "", $modelClass)) {
                case "CoreCore":
                    $query->andWhere(['IN', "core_section.core_id", $subQuery]);
                    break;
            }
            $dataProvider = $this->getDataProvider($query);
        }
        else {
            $cPrevNextLabel = "Section";
            if (isset($options["id"]) && $dataProvider->getCount() == 1) {
                $section = $dataProvider->getModels()[0];
                $prevModel = \app\models\CoreSection::find()
                    ->andWhere(['and',
                        ['core_id' => $section->core_id],
                        ['<', 'section', $section->section]])
                    ->orderBy(['section' => SORT_DESC])
                    ->one();
                $nextModel = \app\models\CoreSection::find()
                    ->andWhere(['and',
                        ['core_id' => $section->core_id],
                        ['>', 'section', $section->section]])
                    ->orderBy(['section' => SORT_ASC])
                    ->one();
            }
        }

        $this->content = $this->_generate($dataProvider, $prevModel, $nextModel);
    }

    protected function _generate($dataProvider, $prevModel, $nextModel) {
        $dataProvider->pagination = false;

        $ancestorValues = [];
        $content = "";
        $sections = $dataProvider->getModels();
        if (sizeof($sections)) {
            $ancestorValues = $this->getAncestorValues($sections[0]);
            $this->setExpedition($sections[0]);
        }

        $this->getView()->registerCssFile("@web/css/report.css");

        return $this->render(null, [
            "report" => $this,
            "sections" => $sections,
            "prevModel" => $prevModel,
            "nextModel" => $nextModel,
            "ancestorValues" => $ancestorValues
        ]);
    }

    public function getCss() {
        return <<<'EOB'
html, body {height: 100%;}

* {
    -webkit-print-color-adjust: exact !important;   /* Chrome, Safari, Edge */
    color-adjust: exact !important;                 /*Firefox*/
}

.geo-sample-profile-report .heads {
  display: flex;
  flex-wrap: nowrap;
}

.geo-sample-profile-report .data {
  position: relative;
  display: flex;
  flex-wrap: nowrap;
  height: calc(100% - 2em); 
  min-height: 20em;
  }
  @media (min-width: 768px) {
    .geo-sample-profile-report {
      height: calc(100% - 15.25em); } }
  @media print {
    .geo-sample-profile-report {
      height: calc(100% - 17.25em); } }

@media (min-width: 992px) {
  body {
    font-size: 1.41vw !important; } }
@media (min-width: 1419px) {
  body {
    font-size: 20px !important; } }
    
#ruler {
  /* resetting the counter so every <ol>
     has an independent count: */
  margin-top: 1.5em; 
  list-style-type: none;
  counter-reset: marker -10;
  height: calc(100%);
  width: 4em;
  padding-left: 2.5em;
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
  width: 3em;  
  height: calc(1.5em + 100%);
  display: flex;
  background-size: 100% calc(100% - 1.5em);
  background-position: 0% 1.5em;
  background-repeat: no-repeat;
  border: 1px solid #999;
}

.split {
  position: relative;
  display: flex;
  width: 3em;
  border: 1px solid #999;
  height: calc(1.5em + 100%);
}

.infos {
    margin-left: 1em;
}
  
  .split .sample {
    position: absolute;
    cursor: pointer;
    background-color: blue !important;
    width: 3em;
    
  }


  
  .head {
    width: 3em;
    background-color: #FFF !important;
    border-bottom: none;
    height: 1.5em;
    padding: 0.2em;
    position: relative;
    text-align: center;
    z-index: 1; }

  .head span {
      font-size: 0.8em;
      font-weight: bold; }

  .section img {
    background-color: #CCC;
    border: 1px solid #999;
    position: relative;
    vertical-align: top;
    width: 100%;
    z-index: 0; }
    
  

@media print {
  a[href]:after {
    content: none !important;
  }
}

table tbody tr {
  cursor: pointer;
}

div.sample.unavail {
  background: repeating-linear-gradient(
      -40deg,
      #666,
      #666 5px,
      #FFF 5px,
      #FFF 10px    
  );
  opacity: 0.4;
}

div.sample.conflict {
    background-color: red;
}

div.sample.highlight {
    background-color: orange;
    opacity: 0.8;
}

tr.highlight {
    background-color: rgb(255,165,0, 0.8);
}

table.report tr.highlight td {
  background: none;
}

#footer .prev-next {
  padding-left: 5em;
}

#footer .prev-next a {
  font-size: 0.8em;
  padding-right: 1em;
}

EOB;
    }


    /**
     * @inheritDoc
     */
    function getJs()
    {
        return <<<'EOD'
        function showSample(sampleId) {
            console.log ('showSample(', sampleId, ')');
            Array.from(document.getElementsByClassName("highlight")).forEach((e) => {
                e.classList.remove("highlight");
            });
            Array.from(document.getElementsByClassName("sample" + sampleId)).forEach((e) => {
                e.classList.add("highlight");
            });
        }
EOD;
    }

}
