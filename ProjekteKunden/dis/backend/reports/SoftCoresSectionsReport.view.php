<?php
/**
 *
 * Parameters for the view:
 * @var $report
 * @var $x_expeditions
 **/
?>
<?php foreach($x_expeditions as $x_expedition): ?>
    <?php
    $expedition = $x_expedition["expedition"];
    $countCores = 0;
    $countSections = 0;
    $drilledLength = 0;
    $coreRecovery = 0;
    $coreLength = 0;
    foreach ($x_expedition["sites"] as $x_site) {
        foreach ($x_site["holes"] as $x_hole) {
            foreach ($x_hole["cores"] as $core) {
                $countCores++;
                $countSections += count($core->coreSections);
                $drilledLength = max($drilledLength, $core->core_bottom_depth);
                $coreRecovery += $core->core_recovery;
                $coreLength += $core->drilled_length;
            }
        }
    }

    $headerAttributes = [];
    $headerAttributes['Expedition'] = $expedition->expedition;
    $headerAttributes['Cores'] = $countCores;
    $headerAttributes['Sections'] = $countSections;
    $headerAttributes['Core recovery'] = round($coreRecovery * 100).' cm = ' . \Yii::$app->formatter->asPercent($coreRecovery / $drilledLength);
    $headerAttributes['Total drilled length'] = round ($drilledLength * 100).' cm';
    $headerAttributes['Total cored length'] = round($coreLength * 100).' cm';

    $header = $report->renderDisHeader($headerAttributes, "Core / Section Summary");
    ?>
    <?= $header ?>
  <div class="report expedition-core-sections-report">
      <?php foreach ($x_expedition["sites"] as $x_site): ?>
      <?php
          $site = $x_site["site"];
          $countCores = 0;
          $countSections = 0;
          $drilledLength = 0;
          $coreRecovery = 0;
          $coreLength = 0;
          foreach ($x_site["holes"] as $x_hole) {
              foreach ($x_hole["cores"] as $core) {
                  $countCores++;
                  $countSections += count($core->coreSections);
                  $drilledLength = max($drilledLength, $core->core_bottom_depth);
                  $coreRecovery += $core->core_recovery;
                  $coreLength += $core->drilled_length;
              }
          }

          $headerAttributes = [];
          $headerAttributes['Site'] = $site->site;
          $headerAttributes['Cores'] = $countCores;
          $headerAttributes['Sections'] = $countSections;
          $headerAttributes['Core recovery'] = round($coreRecovery * 100).' cm = ' . \Yii::$app->formatter->asPercent($coreRecovery / $drilledLength);
          $headerAttributes['Total drilled length'] = round ($drilledLength * 100).' cm';
          $headerAttributes['Total cored length'] = round($coreLength * 100).' cm';
          $siteHeader = $report->renderDisExtraHeader($headerAttributes, "site")
      ?>
        <?= $siteHeader ?>
        <?php foreach ($x_site["holes"] as $x_hole): ?>
          <?php
              $hole = $x_hole["hole"];
              $countCores = 0;
              $countSections = 0;
              $drilledLength = 0;
              $coreRecovery = 0;
              $coreLength = 0;
              foreach ($x_hole["cores"] as $core) {
                  $countCores++;
                  $countSections += count($core->coreSections);
                  $drilledLength = max($drilledLength, $core->core_bottom_depth);
                  $coreRecovery += $core->core_recovery;
                  $coreLength += $core->drilled_length;
              }

              $headerAttributes = [];
              $headerAttributes['Event'] = $hole->hole;
              $headerAttributes['Gear'] = (isset($hole->gear) ? $hole->gear : "") . "&nbsp;";
              $headerAttributes['Cores'] = $countCores;
              $headerAttributes['Sections'] = $countSections;
              $headerAttributes['Core recovery'] = round($coreRecovery * 100).' cm = ' . \Yii::$app->formatter->asPercent($coreRecovery / $drilledLength);
              $headerAttributes['Total drilled length'] = round ($drilledLength * 100).' cm';
              $headerAttributes['Total cored length'] = round($coreLength * 100).' cm';

              $holeHeader = $report->renderDisExtraHeader($headerAttributes, "hole");

          ?>
              <?= $holeHeader ?>
          <table class="report" >
            <thead>
            <tr>
              <th class="blue">Core</th>
              <th class="blue">Type</th>
              <th class="blue">Top Depth (cm)</th>
              <th class="blue">Bottom Depth (cm)</th>
              <th class="blue">Drilled Len. (cm)</th>
              <th class="blue">Cored Len. (cm)</th>

              <th class="red">Section / Split Combined ID</th>
              <th class="red">Exists</th>
              <th class="red">Length (cm)</th>
              <th class="red">Curated Len. (cm)</th>
              <th class="red">Top Depth (cm)</th>
              <th class="red">Bottom Depth (cm)</th>
              <th class="red">IGSN</th>
              <th class="red">Curator</th>
              <th class="red">Comment</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($x_hole["cores"] as $core): ?>
                <?php set_time_limit (60) ?>
              <tr class="core">
                <td><?= $core->core ?></td>
                <td><?= $core->core_type ?></td>
                <td><?= $core->core_top_depth * 100 ?></td>
                <td><?= $core->core_bottom_depth * 100 ?></td>
                <td><?= $core->drilled_length * 100 ?></td>
                <td><?= $core->core_recovery * 100 ?></td>
                <td colspan="7" style="color: #999;"></td>
                <td><?= $core->curator ?></td>
                <td><?= $core->comment ?></td>
              </tr>
                <?php foreach ($core->coreSections as $section): ?>
                <tr class="sub section">
                  <td class="no-border" colspan="6">&nbsp;</td>
                  <td><?= $section->combined_id ?></td>
                  <td></td>
                  <td class="red"><?= $section->section_length * 100 ?></td>
                  <td class="red"><?= $section->curated_length * 100 ?></td>
                  <td><?= $section->top_depth * 100 ?></td>
                  <td><?= $section->bottom_depth * 100 ?></td>
                  <td></td>
                  <td><?= $section->curator ?></td>
                  <td><?= (isset($section->comment) ? $section->comment : "") ?></td>
                </tr>
                  <?php foreach ($section->curationSectionSplits as $split): ?>
                  <tr class="sub split <?= !$split->still_exists ? "no-exist" : "" ?>">
                    <td class="no-border" colspan="6">&nbsp;</td>
                    <td><?= $split->combined_id ?></td>
                    <td><?= $split->still_exists ? "yes" : "no" ?></td>
                    <td colspan="4"></td>
                    <td><?= $split->igsn ?></td>
                    <td><?= $split->curator ?></td>
                    <td class="red"><?= $split->comment ?></td>
                  </tr>
                  <?php endforeach ?>
                <?php endforeach ?>
              <tr>
                <td class="no-border" colspan="15">&nbsp;</td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
          <?php endforeach; ?>
      <?php endforeach; ?>
  </div>
<?php endforeach; ?>
