<?php
/**
 *
 * Parameters for the view:
 * @var $header
 * @var $sampleSeriesForm
 * @var $sampleColumnLabels
 * @var $removeSampleColumnsInPreview
 *
 **/
?>
<?= $header ?>
<?php $step = $sampleSeriesForm->step; ?>
<div class="report core-section-report">

  <div class="panel panel-default">
    <div class="panel-heading">
      <h3 class="panel-title">Create a series based on and starting below the following sample:</h3>
    </div>
    <div class="panel-body">
      <div class="table-container">
        <table class="table sample-table">
          <thead>
          <tr>
            <th>Section split</th>
              <?php foreach ($sampleColumnLabels as $label): ?>
                <th><?= $label ?></th>
              <?php endforeach; ?>
          </tr>
          </thead>
          <tbody>
          <tr>
            <td><?= $sampleSeriesForm->modelToCopy->sectionSplit->combined_id ?></td>
              <?php foreach (array_keys($sampleColumnLabels) as $attribute): ?>
                <td><?= $modelToCopy->{$attribute} ?></td>
              <?php endforeach; ?>
          </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="panel panel-default">
    <div class="panel-heading">
      <h3 class="panel-title">1. Enter parameters for the sample series</h3>
    </div>
    <div class="panel-body">
        <?php $form1 = $sampleSeriesForm->startForm(0); ?>
      <div class="row">
        <div class="col col-sm-6">
            <?= $form1->field($sampleSeriesForm, 'interval')->textInput() ?>
        </div>
        <div class="col col-sm-6">
            <?= $form1->field($sampleSeriesForm, 'numberSamples')->textInput() ?>
        </div>
      </div>
        <?php $sampleSeriesForm->showErrors(); ?>
        <?= \yii\helpers\Html::submitButton('Continue', array_merge(['class' => 'btn btn-success'], ($sampleSeriesForm->isFinished ? ['disabled' => 'disabled'] : []))) ?>
        <?php $sampleSeriesForm->endForm(); ?>
    </div>
  </div>

    <?php if ($step > 1):?>
      <div class="panel panel-default">
        <div class="panel-heading">
          <h3 class="panel-title">2. Select splits to use</h3>
        </div>
        <div class="panel-body">
            <?php $form2 = $sampleSeriesForm->startForm(1); ?>
          <table class="table table-condensed table-hover">
            <thead>
            <tr>
              <th>Section</th>
              <th>Split to sample?</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($sampleSeriesForm->getSplitsToSample() as $section => $splits): ?>
              <tr>
                <td ><?= $section ?></td>
                <td>
                    <?= $form2->field($sampleSeriesForm, 'idsToSample['.$section.']')->radioList(
                        yii\helpers\ArrayHelper::map($splits, 'id', 'type')
                    )->label(false) ?>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
            <?php if ($sampleSeriesForm->isSamplingArchive): ?>
                <?= $form2->field($sampleSeriesForm, 'forceSamplingArchive')->checkbox() ?>
            <?php endif; ?>

            <?php $sampleSeriesForm->showErrors(); ?>
            <?= \yii\helpers\Html::submitButton('Continue', array_merge(['class' => 'btn btn-success'], ($sampleSeriesForm->isFinished ? ['disabled' => 'disabled'] : []))) ?>
            <?php $sampleSeriesForm->endForm(); ?>
        </div>
      </div>
    <?php endif; ?>



    <?php if ($step > 2):?>
        <?php $countColumns = sizeof(array_diff_key($sampleColumnLabels, $removeSampleColumnsInPreview)) + 1 ?>
      <div class="panel panel-default">
        <div class="panel-heading">
          <h3 class="panel-title">3. Preview of samples to be created</h3>
        </div>
        <div class="panel-body">
            <?php $form3 = $sampleSeriesForm->startForm(2); ?>
          <div class="table-container">
            <table class="table sample-table">
              <thead>
              <tr>
                <th>Section split</th>
                  <?php foreach (array_diff_key($sampleColumnLabels, $removeSampleColumnsInPreview) as $label): ?>
                    <th><?= $label ?></th>
                  <?php endforeach; ?>
              </tr>
              </thead>
              <tbody>
              <?php foreach ($sampleSeriesForm->validSamples as $model): ?>
                  <?php $reducedInterval = $model->sample_length < $sampleSeriesForm->modelToCopy->sample_length ?>
                <tr <?= $reducedInterval ? 'class="warning"' : '' ?>>
                  <td>
                      <?= $model->sectionSplit->combined_id ?></td>
                  </td>
                    <?php foreach (array_keys(array_diff_key($sampleColumnLabels, $removeSampleColumnsInPreview)) as $attribute): ?>
                      <td><?= $model->{$attribute} ?></td>
                    <?php endforeach; ?>
                </tr>
                  <?php if ($reducedInterval): ?>
                  <tr class="warning">
                    <td colspan="<?= $countColumns ?>">In the above sample the sample length has been reduced to the bottom of the split.</td>
                  </tr>
                  <?php endif; ?>
              <?php endforeach ?>

              <?php if (sizeof($sampleSeriesForm->invalidSamples)): ?>
                <tr class="error">
                  <th colspan="<?= $countColumns ?>">
                    <br>
                    The following samples cannot be created:
                  </th>
                </tr>
                  <?php foreach ($sampleSeriesForm->invalidSamples as $model): ?>
                  <tr class="error">
                    <td>
                        <?= $model->sectionSplit->combined_id ?></td>
                    </td>
                      <?php foreach (array_keys(array_diff_key($sampleColumnLabels, $removeSampleColumnsInPreview)) as $attribute): ?>
                        <td><?= $model->{$attribute} ?></td>
                      <?php endforeach; ?>
                  </tr>
                  <tr class="error">
                    <td colspan="<?= $countColumns ?>">
                        <?= implode('<br>', $model->getErrorSummary(true)) ?>
                    </td>
                  </tr>
                  <?php endforeach ?>
              <?php endif; ?>
              </tbody>
            </table>
          </div>

            <?php $sampleSeriesForm->showErrors(); ?>
          <div class="alert alert-warning" role="alert">
            <div>Please confirm that these samples shall be created or change the settings above.</div>
              <?= \yii\helpers\Html::submitButton('Create ' . sizeof($sampleSeriesForm->validSamples). ' samples', array_merge(['class' => 'btn btn-warning'], ($sampleSeriesForm->isFinished ? ['disabled' => 'disabled'] : []))) ?>
          </div>
            <?php $sampleSeriesForm->endForm(); ?>
        </div>
      </div>
    <?php endif; ?>


    <?php if ($step > 3): ?>
      <div class="panel panel-default">
        <div class="panel-heading">
          <h3 class="panel-title">4. List of created samples</h3>
        </div>
        <div class="panel-body">
            <?php $form3 = $sampleSeriesForm->startForm(3); ?>
            <?php $countColumns = sizeof($sampleColumnLabels) + 1 ?>
          <div class="table-container">
            <table class="table sample-table">
              <thead>
              <tr>
                <th>Section split</th>
                  <?php foreach ($sampleColumnLabels as $label): ?>
                    <th><?= $label ?></th>
                  <?php endforeach; ?>
              </tr>
              </thead>
              <tbody>
              <?php foreach ($sampleSeriesForm->createdSamples as $model): ?>
                  <?php $reducedInterval = $model->sample_length < $sampleSeriesForm->modelToCopy->sample_length ?>
                <tr <?= $reducedInterval ? 'class="warning"' : '' ?>>
                  <td>
                      <?= $model->sectionSplit->combined_id ?></td>
                  </td>
                    <?php foreach (array_keys($sampleColumnLabels) as $attribute): ?>
                      <td><?= $model->{$attribute} ?></td>
                    <?php endforeach; ?>
                </tr>
                  <?php if ($reducedInterval): ?>
                  <tr class="warning">
                    <td colspan="<?= $countColumns ?>">In the above sample the sample length has been reduced to the bottom of the split.</td>
                  </tr>
                  <?php endif; ?>
              <?php endforeach ?>
              </tbody>
            </table>
          </div>

            <?php $sampleSeriesForm->showErrors(); ?>
            <?= \yii\helpers\Html::a('Generate labels for created samples', '/report/SampleQrCodes?model=CurationSample&specific-ids=' . implode(',', $sampleSeriesForm->createdSampleIds), ['class'=>'btn btn-info', 'target' => '_blank']) ?>
            <?= \yii\helpers\Html::a('Undo series', '/report/UndoSampleSeries?model=CurationSample&id=' . $sampleSeriesForm->modelToCopy->id, ['class'=>'btn btn-danger', 'target' => '_blank']) ?>
            <?php $sampleSeriesForm->endForm(); ?>
        </div>
      </div>
    <?php endif; ?>
</div>
