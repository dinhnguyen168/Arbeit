<?php
namespace app\reports;

use app\models\CoreSection;
use app\models\CurationSectionSplit;
use app\reports\interfaces\IHtmlReport;

/**
 * Class VisualCoreDescriptionReport
 *
 * @package app\reports
 */
class LithologySectionReport extends Base implements IHtmlReport
{
    /**
     * {@inheritdoc}
     */
    const TITLE = 'Lithology/Section Report';

    /**
     * {@inheritdoc}
     * This reports can only be used for CoreCore forms.
     */
    const MODEL = '^(CoreCore|CoreSection|CurationSectionSplit|GeologyLithology)';

    /**
     * {@inheritdoc}
     * This report can be used for single or multiple records.
     */
    const SINGLE_RECORD = null;


    protected $originalDataProvider = null;

    /**
     * {@inheritdoc}
     */
    function validateReport($options) {
        $valid = parent::validateReport($options);
        $valid = $this->validateColumns("CoreSection", ['core_id']) && $valid;
        $valid = $this->validateColumns("CurationSectionSplit", ['section_id']) && $valid;
        $valid = $this->validateColumns("GeologyLithology", ['section_split_id', 'litho_unit', 'top_depth', 'unit_length', 'mcd_top_depth_unit', 'rock_class', 'rock_type', 'color', 'description']) && $valid;
        return $valid;
    }


    /**
     * {@inheritdoc}
     */
    public function generate($options = []) {
        $this->originalDataProvider = $this->getDataProvider($options);
        $dataProvider = null;
        switch ($options['model']) {
            case 'CurationSectionSplit':
                $dataProvider = $this->originalDataProvider;
                $dataProvider->query->joinWith ('geologyLithologies', false, 'INNER JOIN');
                break;

            case 'CoreSection':
                $subQuery = $this->getDataProvider($options)->query;
                $subModelClass = $subQuery->modelClass;
                $subQuery->select($subModelClass::tableName() . '.id');

                $query = CurationSectionSplit::find()->andWhere(['IN', 'section_id', $subQuery]);
                $options['model'] = 'CurationSectionSplit';
                $dataProvider = $this->getDataProvider($query);
                $dataProvider->query->joinWith ('geologyLithologies', false, 'INNER JOIN');
                break;

            case 'CoreCore':
                $subQuery2 = $this->getDataProvider($options)->query;
                $subModelClass2 = $subQuery2->modelClass;
                $subQuery2->select($subModelClass2::tableName() . '.id');

                $subQuery = $this->getDataProvider(['model' => 'CoreSection'])->query;
                $subModelClass = $subQuery->modelClass;
                $subQuery->select($subModelClass::tableName() . '.id');
                $subQuery->andWhere(['IN', 'core_id', $subQuery2]);

                $query = CurationSectionSplit::find()->andWhere(['IN', 'section_id', $subQuery]);
                $options['model'] = 'CurationSectionSplit';
                $dataProvider = $this->getDataProvider($query);
                $dataProvider->query->joinWith ('geologyLithologies', false, 'INNER JOIN');
                break;

            case 'GeologyLithology':
                $dataProvider = $this->originalDataProvider;
                if (!isset($options['specific-ids']) && !isset($options["id"])) {
                    $options['model'] = 'CurationSectionSplit';
                    $dataProvider = $this->getDataProvider($options);
                }
                else {
                    $query = CurationSectionSplit::find();
                    $options['model'] = 'CurationSectionSplit';
                    $query->joinWith ('geologyLithologies', false, 'INNER JOIN');
                    $query->where = $this->originalDataProvider->query->where;
                    $dataProvider = $this->getDataProvider($query);
                }

                break;
        }
        $this->content = $this->_generate($dataProvider);
    }


    protected function _generate($dataProvider) {
        $dataProvider->pagination = false;

        $sectionSplits = $dataProvider->getModels();

        $ancestorValues = ['site' => [''], 'hole' => ['']];
        if (sizeof($sectionSplits)) {
            $ancestorValues = $this->getAncestorValues($sectionSplits[0]);
            $this->setExpedition($sectionSplits[0]);
        }
        else {
            $models = $this->originalDataProvider->getModels();
            if (sizeof($models)) {
                $model = $models[0];
                $ancestorValues = $this->getAncestorValues($model);
                $this->setExpedition($model);
                $nameAttribute = constant(get_class($model) . "::NAME_ATTRIBUTE");
                $value = $model->{$nameAttribute};
                if ($model instanceof \app\models\CoreCore && isset($model->core_type)) {
                    $value .= " " . $model->core_type;
                }
                $ancestorValues[$nameAttribute] = [$value, $model->getAttributeLabel($nameAttribute)];
            }
        }

        return $this->render(null, [
            'report' => $this,
            'sectionSplits' => $sectionSplits,
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
      width: 1em; }
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
    padding-left: 2.25em; } 
}

.report-wrapper {
    height: 100vh;
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

.lithology-report {
    display: flex;
    height: calc(100vh - 12em);
}

.lithology-colors {
    flex-shrink: 0;
    display: flex;
    width: 10em;
    margin-right: 1.3em;
}

.lithology-colors img {
    max-width: min(100%, 6em) !important;
    height: 100%;
}

.colorBlocks {
    width: 1em;
    position: relative;
}

.colorBlock {
    width: 1em;
    position: absolute;
    left: 0;
}

.colorBlock svg {
    position: absolute;
    left: 0;
    top: 0;
}

.lithology-data {
    flex-grow: 3;
}

table.report {
    width: 100%;
}

table.report th {
    color: black;
}

table.report tr td, table.report tr th, table.report tr:nth-child(2n+1) td, table.report tr:nth-child(2n) td {
    background-color: white;
}

table.report tr:nth-child(4n+1) td, table.report tr:nth-child(4n+2) td {
    background: rgba(220, 230, 255, 0.8);
}

header.reports {
    height: auto;
    padding: 0 0 1em 0;
    width: auto;
}

.color-marker {
    width: 1em;
    height: 1.3em;
}

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
