<?php
/**
 *
 * Parameters for the view:
 * @var $header
 * @var $cratesForm
 * @var $overviewReportUrl
 **/
?>
<?= $header ?>
<?php $step = $cratesForm->step; ?>
<div class="report create-crates-report">
  <div class="panel panel-default">
    <div class="panel-heading">
      <h3 class="panel-title">Select crate to use</h3>
    </div>
    <div class="panel-body">
        <?php $form1 = $cratesForm->startForm(0); ?>

      <div class="row">
        <div class="col col-sm-4">
            <?= $form1->field($cratesForm, 'crateMaxWeight')->textInput() ?>
        </div>
      </div>
      <table class="table table-condensed table-hover">
        <thead>
        <tr>
          <th>Select</th>
          <th>Crate name</th>
          <th>No. of section splits</th>
          <th>Weight (kg)</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($cratesForm->existingCrates as $crate): ?>
          <tr>
            <td><?= $form1->field($cratesForm, 'crateName')->radio(["value" => $crate["name"], "uncheck" => null, "disabled" => ($crate["weight"] > $cratesForm->crateMaxWeight)], false)->label(false) ?></td>
            <td><?= $crate["name"] ?></td>
            <td><?= $crate["splits"] ?></td>
            <td><?= $crate["weight"] ?></td>
          </tr>
        <?php endforeach; ?>
        <?php $isSelected = ($step > 1 && $cratesForm->selectedCrate["name"] == "!!NEW!!"); ?>
        <tr>
          <td><?= $form1->field($cratesForm, 'crateName')->radio(["value" => "!!NEW!!", "uncheck" => null], false)->label(false) ?></td>
          <td>
              <?= $form1->field($cratesForm, 'newCrateName')->textInput()->label(false) ?>
          </td>
          <td>0</td>
          <td>0</td>
        </tr>
        </tbody>
      </table>
        <?php $cratesForm->showErrors(); ?>
        <?= \yii\helpers\Html::submitButton('Continue', array_merge(['class' => 'btn btn-success'], ($cratesForm->isFinished ? ['disabled' => 'disabled'] : []))) ?>
        <?= \yii\helpers\Html::a('Show crates overview', $overviewReportUrl, ['class'=>'btn btn-info', 'target' => '_blank']) ?>
        <?php $cratesForm->endForm(); ?>
    </div>
  </div>


    <?php if ($step > 1): ?>
      <div class="panel panel-default">
        <div class="panel-heading">
          <h3 class="panel-title">Selected crate</h3>
        </div>
        <div class="panel-body">
          <div class="table-container">
            <table class="table selected-crate-table">
              <thead>
              <tr>
                <th>Crate name</th>
                <th>No. of section splits</th>
                <th>Weight (kg)</th>
              </tr>
              </thead>
              <tbody>
              <?php $crate = $cratesForm->selectedCrate ?>
              <tr class="selected">
                <td><?= $crate["name"] ?></td></td>
                <td data-splits="<?= intval($crate["splits"]) ?>"><?= $crate["splits"] ?></td></td>
                <td data-weight="<?= intval($crate["weight"]) ?>"<?= $crate["weight"] > $cratesForm->crateMaxWeight ? ' class="overweight"' : '' ?>><?= $crate["weight"] ?></td></td>
              </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    <?php endif; ?>

  <div class="panel panel-default">
    <div class="panel-heading">
      <h3 class="panel-title"><?= $step > 1 ? '2. Select section splits to assign to the selected crate' : 'Unassigned section splits' ?></h3>
    </div>
    <div class="panel-body">
        <?php $form2 = $cratesForm->startForm(1); ?>

      <div class="table-container">
        <table class="table select-splits-table">
          <thead>
          <tr>
              <?php if ($step > 1): ?>
                <th>Select</th>
              <?php endif; ?>
            <th>Split</th>
            <th>Weight (kg)</th>
          </tr>
          </thead>
          <tbody>
          <?php foreach ($cratesForm->unassignedSplits as $split): ?>
            <tr>
                <?php if ($step > 1): ?>
                  <td><?= $form1->field($cratesForm, 'selectedSplitIds[' . $split->id . ']')->checkbox(["value" => 1, "data-weight" => $split->weight, "onchange" => "updateCrateValues(this)" ], false)->label(false) ?></td>
                <?php endif; ?>
              <td><?= $split->combined_id ?></td></td>
              <td><?= $split->weight ?></td></td>
            </tr>
          <?php endforeach ?>
          </tbody>
        </table>
      </div>

        <?php if ($step > 1): ?>
            <?php $cratesForm->showErrors(); ?>
            <?= \yii\helpers\Html::submitButton('Continue', array_merge(['class' => 'btn btn-success'], ($cratesForm->isFinished ? ['disabled' => 'disabled'] : []))) ?>
        <?php endif; ?>
        <?php $cratesForm->endForm(); ?>
    </div>
  </div>


    <?php if ($step > 2): ?>
      <div class="panel panel-default">
        <div class="panel-heading">
          <h3 class="panel-title">3. Confirm selected splits</h3>
        </div>
        <div class="panel-body">
            <?php $form2 = $cratesForm->startForm(2); ?>
          <div class="table-container">
            <table class="table selected-crate-table">
              <thead>
              <tr>
                <th>Crate name</th>
                <th>No. of section splits</th>
                <th>Weight (kg)</th>
              </tr>
              </thead>
              <tbody>
              <?php $crate = $cratesForm->selectedCrate ?>
              <tr class="selected">
                <td><?= $crate["name"] ?></td></td>
                <td><?= $crate["splits"] ?></td></td>
                <td><?= $crate["weight"] ?></td></td>
              </tr>
              </tbody>
            </table>
          </div>


          <div class="table-container">
            <table class="table confirm-splits-table">
              <thead>
              <tr>
                <th>Split</th>
                <th>Weight (kg)</th>
              </tr>
              </thead>
              <tbody>
              <?php foreach ($cratesForm->selectedSplits as $split): ?>
                <tr>
                  <td><?= $split->combined_id ?></td></td>
                  <td><?= $split->weight ?></td></td>
                </tr>
              <?php endforeach ?>
              </tbody>
            </table>
          </div>

            <?php $cratesForm->showErrors(); ?>
          <div class="alert alert-warning" role="alert">
            <div>Please confirm that these splits shall be assigned to the selected crate.</div>
              <?= \yii\helpers\Html::submitButton('Assign ' . sizeof($cratesForm->selectedSplits). ' splits to the selected crate', array_merge(['class' => 'btn btn-warning'], ($cratesForm->isFinished ? ['disabled' => 'disabled'] : []))) ?>
          </div>
            <?php $cratesForm->endForm(); ?>
        </div>
      </div>
    <?php endif; ?>



    <?php if ($step > 3): ?>
      <div class="panel panel-default">
        <div class="panel-heading">
          <h3 class="panel-title">4. List of assigned section splits</h3>
        </div>
        <div class="panel-body">
            <?php $form3 = $cratesForm->startForm(3); ?>
          <div class="table-container">
            <table class="table confirm-splits-table">
              <thead>
              <tr>
                <th>Split</th>
                <th>Crate name</th>
                <th>Weight (kg)</th>
              </tr>
              </thead>
              <tbody>
              <?php foreach ($cratesForm->assignedSplits as $split): ?>
                <tr>
                  <td><?= $split->combined_id ?></td></td>
                  <td><?= $split->crate_name ?></td></td>
                  <td><?= $split->weight ?></td></td>
                </tr>
              <?php endforeach ?>
              </tbody>
            </table>
          </div>

            <?php $cratesForm->showErrors(); ?>
            <?= \yii\helpers\Html::a('Assign more section splits to crates', '#', ['class'=>'btn btn-info', 'target' => '_blank']) ?>
            <?= \yii\helpers\Html::a('Show crates overview', $overviewReportUrl, ['class'=>'btn btn-info', 'target' => '_blank']) ?>
            <?php $cratesForm->endForm(); ?>
        </div>
      </div>
    <?php endif; ?>
</div>
