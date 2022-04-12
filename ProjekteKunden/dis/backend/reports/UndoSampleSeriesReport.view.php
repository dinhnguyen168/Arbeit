<?php
/**
 *
 * Parameters for the view:
 * @var $header
 * @var $sampleSeriesForm
 * @var $sampleColumnLabels
 *
 **/
?>
<?= $header ?>
<?php
$step = $sampleSeriesForm->step;
?>
<div class="report core-section-report">

  <div class="panel panel-default">
    <div class="panel-heading">
      <h3 class="panel-title">Undo a series based on the following sample:</h3>
    </div>
    <div class="panel-body">
      <div class="table-container">
        <table class="table sample-table">
          <thead>
          <tr>
              <?php foreach ($sampleColumnLabels as $label): ?>
                <td><?= $label ?></td>
              <?php endforeach; ?>
          </tr>
          </thead>
          <tbody>
          <tr>
              <?php foreach (array_keys($sampleColumnLabels) as $attribute): ?>
                <td><?= $sampleSeriesForm->modelToCopy->{$attribute} ?></td>
              <?php endforeach; ?>
          </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>


  <div class="panel panel-default">
    <div class="panel-heading">
      <h3 class="panel-title">1. Select samples to delete</h3>
    </div>
    <div class="panel-body">
        <?php $form0 = $sampleSeriesForm->startForm(0); ?>
      <div class="table-container">
        <table class="table sample-table">
          <thead>
          <tr>
            <th><input type="checkbox" onchange="selectAllSamples(this)"></th>
              <?php foreach ($sampleColumnLabels as $label): ?>
                <th><?= $label ?></th>
              <?php endforeach; ?>
          </tr>
          </thead>
          <tbody>
          <?php foreach ($sampleSeriesForm->matchingSamples as $model): ?>
            <tr>
              <td>
                  <?= $form0->field($sampleSeriesForm, 'sampleIdsToDelete[]', ['template' => '{input}', 'options' => ['tag' => false]])->checkbox(['value' => $model->id, 'checked' => in_array($model->id, $sampleSeriesForm->sampleIdsToDelete)], false)->label(false) ?>
              </td>
                <?php foreach (array_keys($sampleColumnLabels) as $attribute): ?>
                  <td><?= $model->{$attribute} ?></td>
                <?php endforeach; ?>
            </tr>
          <?php endforeach ?>
          </tbody>
        </table>
      </div>

        <?php $sampleSeriesForm->showErrors(); ?>
        <?= \yii\helpers\Html::submitButton('Continue', array_merge(['class' => 'btn btn-success'], ($sampleSeriesForm->isFinished ? ['disabled' => 'disabled'] : []))) ?>
        <?php $sampleSeriesForm->endForm(); ?>
    </div>
  </div>

    <?php if ($step > 1):?>
      <div class="panel panel-default">
        <div class="panel-heading">
          <h3 class="panel-title">2. Preview samples to delete</h3>
        </div>
        <div class="panel-body">
            <?php $form1 = $sampleSeriesForm->startForm(1); ?>
          <div class="table-container">
            <table class="table sample-table">
              <thead>
              <tr>
                  <?php foreach ($sampleColumnLabels as $label): ?>
                    <td><?= $label ?></td>
                  <?php endforeach; ?>
              </tr>
              </thead>
              <tbody>
              <?php foreach ($sampleSeriesForm->samplesToDelete as $model): ?>
                <tr>
                    <?php foreach (array_keys($sampleColumnLabels) as $attribute): ?>
                      <td><?= $model->{$attribute} ?></td>
                    <?php endforeach; ?>
                </tr>
              <?php endforeach ?>
              </tbody>
            </table>
          </div>

            <?php $sampleSeriesForm->showErrors(); ?>
          <div class="alert alert-warning" role="alert">
            Please confirm that these samples shall be deleted. This cannot be undone.
          </div>
            <?= \yii\helpers\Html::submitButton('Delete ' . sizeof($sampleSeriesForm->samplesToDelete). ' samples', array_merge(['class' => 'btn btn-warning'], ($sampleSeriesForm->isFinished ? ['disabled' => 'disabled'] : []))) ?>
            <?php $sampleSeriesForm->endForm(); ?>
        </div>
      </div>
    <?php endif; ?>


    <?php if ($step > 2): ?>
      <div class="panel panel-default">
        <div class="panel-heading">
          <h3 class="panel-title">3. List of deleted samples</h3>
        </div>
        <div class="panel-body">
            <?php $form2 = $sampleSeriesForm->startForm(2); ?>
          <div class="table-container">
            <table class="table sample-table">
              <thead>
              <tr>
                  <?php foreach ($sampleColumnLabels as $label): ?>
                    <th><?= $label ?></th>
                  <?php endforeach; ?>
              </tr>
              </thead>
              <tbody>
              <?php foreach ($sampleSeriesForm->samplesToDelete as $model): ?>
                <tr>
                    <?php foreach (array_keys($sampleColumnLabels) as $attribute): ?>
                      <td><?= $model->{$attribute} ?></td>
                    <?php endforeach; ?>
                </tr>
              <?php endforeach ?>
              </tbody>
            </table>
          </div>

            <?php $sampleSeriesForm->showErrors(); ?>
            <?php $sampleSeriesForm->endForm(); ?>
        </div>
      </div>
    <?php endif; ?>

</div>
