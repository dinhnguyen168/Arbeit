<?php
/**
 * Created by PhpStorm.
 * User: reckert
 * Date: 21.01.2019
 * Time: 14:59
 */

namespace app\reports;

use app\reports\interfaces\IHtmlReport;

/**
 * Class CoreSectionsReport
 *
 * Example for a more complex report of Cores with their sections.
 * It creates HTML pages with a list of the filtered cores together with their sections.
 *
 * @package app\reports
 */
class CoreSectionsReport extends Base implements IHtmlReport
{
    /**
     * {@inheritdoc}
     */
    const TITLE = 'Cores with Sections';

    /**
     * {@inheritdoc}
     * This reports can only be used for CoreCore forms.
     */
    const MODEL = 'CoreCore';

    /**
     * {@inheritdoc}
     */
    const SINGLE_RECORD = null;

    function getJs()
    {
        return '';
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
        $this->content = $this->_generate($dataProvider);
    }

    /**
     * Generates the report for all records in the dataProvider
     * @param \yii\data\ActiveDataProvider $dataProvider
     * @return string Rendered report
     */
    protected function _generate(\yii\data\ActiveDataProvider $dataProvider) {
        $dataProvider->pagination = false;

        $ancestorValues = [];
        $totalDrilledLength = 0;
        $totalDrillRecovery = 0;
        $sectionCount = 0;
        foreach ($dataProvider->getModels() as $model) {
            if (sizeof($ancestorValues) == 0) {
                $ancestorValues = $this->getAncestorValues($model);
                $this->setExpedition($model);
            }
            $sectionCount += count($model->coreSections);
            $totalDrilledLength += $model->drilled_length;
            $totalDrillRecovery += $model->core_recovery;
        }
        $headerAttributes = [];
        foreach ($ancestorValues as $ancestorValue) $headerAttributes[$ancestorValue[1]] = $ancestorValue[0];
        $headerAttributes['total drilled length'] = \Yii::$app->formatter->asDecimal($totalDrilledLength, 2).' m';
        $headerAttributes['core recovery'] = \Yii::$app->formatter->asDecimal($totalDrillRecovery, 2).' m    '.\Yii::$app->formatter->asPercent($totalDrillRecovery/$totalDrilledLength);
        $headerAttributes['Cores'] = $dataProvider->totalCount;
        $headerAttributes['Sections'] = $sectionCount;

        return $this->render(null, [
            'header' => $this->renderDisHeader($headerAttributes, "Core / Section Summary"),
            'cores' => $dataProvider->getModels()
        ]);
    }

}
