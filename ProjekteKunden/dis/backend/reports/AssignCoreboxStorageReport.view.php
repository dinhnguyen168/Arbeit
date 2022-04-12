<?php
/**
 *
 * Parameters for the view:
 * @var $header
 * @var $assignLocationForm
 * @var $columns
 *
 **/

use \kartik\select2\Select2;

?>
<?= $header ?>
<?php
$step = $assignLocationForm->step;
$columns = [
  'hole->combined_id' => 'Hole',
  'corebox' => 'Corebox',
  'storage->combined_id' => 'Storage location'
];

function getColumnValue ($model, $column) {
  $parts = explode("->", $column);
  $value = $model;
  foreach ($parts as $part) {
      $value = $value->{$part};
      if ($value == null) break;
  }
  return $value;
}

?>
<div class="report assign-location-report">

  <div class="panel panel-default">
    <div class="panel-heading">
      <h3 class="panel-title">1. Select corebox records and the new storage location to assign</h3>
    </div>
    <div class="panel-body form0">
        <?php $form0 = $assignLocationForm->startForm(0); ?>
      <div class="table-container">
        <table class="table sample-table">
          <thead>
          <tr>
            <th><input type="checkbox" onchange="selectAllRecords(this)"></th>
              <?php foreach ($columns as $column => $label): ?>
                <th><?= $label ?></th>
              <?php endforeach; ?>
          </tr>
          </thead>
          <tbody>
          <?php foreach ($assignLocationForm->getModels() as $model): ?>
            <tr>
              <td>
                  <?= $form0->field($assignLocationForm, 'modelIds[]', ['template' => '{input}', 'options' => ['tag' => false]])->checkbox(['value' => $model->id, 'checked' => in_array($model->id, $assignLocationForm->modelIds)], false)->label(false) ?>
              </td>
                <?php foreach ($columns as $column => $label): ?>
                  <td><?= getColumnValue($model, $column) ?></td>
                <?php endforeach; ?>
            </tr>
          <?php endforeach ?>
          </tbody>
        </table>
        <div>
        <?= $form0->field($assignLocationForm, 'storageLocationId')->widget(Select2::classname(), [
            'data' => $assignLocationForm->getStorageLocations(),
            'options' => ['prompt' => 'Select a storage location ...'],
            'pluginOptions' => [
                'allowClear' => true
            ],
          ]) ?>
        </div>
      </div>


      <?php $assignLocationForm->showErrors(); ?>
      <?= \yii\helpers\Html::submitButton('Continue', array_merge(['class' => 'btn btn-success'], ($assignLocationForm->isFinished ? ['disabled' => 'disabled'] : []))) ?>
      <?php $assignLocationForm->endForm(); ?>
    </div>
  </div>

    <?php if ($step > 1):?>
      <?php
        $storageLocation = $assignLocationForm->storageLocation;
      ?>
      <div class="panel panel-default">
        <div class="panel-heading">
          <h3 class="panel-title">2. Preview corebox records to assign to the new storage location</h3>
        </div>
        <div class="panel-body">
            <?php $form1 = $assignLocationForm->startForm(1); ?>
          <div class="table-container">
            <table class="table sample-table">
              <thead>
              <tr>
                  <?php foreach ($columns as $column => $label): ?>
                    <?php if ($column == "storage->combined_id"): ?>
                      <?php $label = "New " . lcfirst($label); ?>
                    <?php endif; ?>
                    <th><?= $label ?></th>
                  <?php endforeach; ?>
              </tr>
              </thead>
              <tbody>
              <?php foreach ($assignLocationForm->getSelectedModels() as $model): ?>
                <tr>
                    <?php foreach ($columns as $column => $label): ?>
                      <?php if ($column == "storage->combined_id"): ?>
                        <td class="alert-warning"><?= $storageLocation->combined_id ?></td>
                      <?php else: ?>
                        <td><?= getColumnValue($model, $column) ?></td>
                      <?php endif; ?>
                    <?php endforeach; ?>
                </tr>
              <?php endforeach ?>
              </tbody>
            </table>
          </div>

          <?php $assignLocationForm->showErrors(); ?>
          <div class="alert alert-warning" role="alert">
            Please confirm that these corebox records shall be assigned to new storage location. This cannot be undone.
          </div>

          <?= \yii\helpers\Html::submitButton('Assign storage location to ' . sizeof($assignLocationForm->getSelectedModels()). ' records', array_merge(['class' => 'btn btn-warning'], ($assignLocationForm->isFinished ? ['disabled' => 'disabled'] : []))) ?>
          <?php $assignLocationForm->endForm(); ?>
      </div>
      </div>
    <?php endif; ?>


    <?php if ($step > 2): ?>
      <div class="panel panel-default">
        <div class="panel-heading">
          <h3 class="panel-title">3. List of assigned corebox records</h3>
        </div>
        <div class="panel-body">
          <?php if (sizeof($assignLocationForm->getAssignedModels()) > 0): ?>
          <div class="table-container">
            <table class="table sample-table">
              <thead>
              <tr>
                  <?php foreach ($columns as $column => $label): ?>
                    <th><?= $label ?></th>
                  <?php endforeach; ?>
              </tr>
              </thead>
              <tbody>
              <?php foreach ($assignLocationForm->getAssignedModels() as $model): ?>
                <tr>
                    <?php foreach ($columns as $column => $label): ?>
                      <td><?= getColumnValue($model, $column) ?></td>
                    <?php endforeach; ?>
                </tr>
              <?php endforeach ?>
              </tbody>
            </table>
          </div>
          <?php else: ?>
            <div class="alert alert-warning" role="alert">
              No corebox records have been assigned!
            </div>
          <?php endif; ?>
        </div>
      </div>

      <?php if (sizeof($assignLocationForm->getErrorAssignedModels()) > 0): ?>
      <div class="panel panel-default">
        <div class="panel-heading">
          <h3 class="panel-title">4. List of corebox records that could not be assigned to the new storage location</h3>
        </div>
        <div class="panel-body">
          <div class="table-container">
            <table class="table sample-table">
              <thead>
              <tr>
                  <?php foreach ($columns as $column => $label): ?>
                    <th><?= $label ?></th>
                  <?php endforeach; ?>
              </tr>
              </thead>
              <tbody>
              <?php foreach ($assignLocationForm->getErrorAssignedModels() as $model): ?>
                <tr class="error">
                    <?php foreach ($columns as $column => $label): ?>
                      <td><?= getColumnValue($model, $column) ?></td>
                    <?php endforeach; ?>
                </tr>
                <tr>
                  <td colspan="<?= sizeof($columns) ?>"><?= $model->getFirstError('') ?></td>
                </tr>
              <?php endforeach ?>
              </tbody>
            </table>
          </div>
          <div class="alert alert-danger">
              <?= sizeof($assignLocationForm->getErrorAssignedModels()) ?> corebox records could not assigned.
          </div>
        </div>
      </div>
      <?php endif; ?>

    <?php endif; ?>

</div>
