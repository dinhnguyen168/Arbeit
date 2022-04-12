<?php
/**
 *
 * Parameters for the view:
 * @var $header
 * @var $batchDeleteForm
 * @var $columns
 *
 **/
?>
<?= $header ?>
<?php
$step = $batchDeleteForm->step;
?>
<div class="report batch-delete-report">

  <div class="panel panel-default">
    <div class="panel-heading">
      <h3 class="panel-title">1. Select records to delete</h3>
    </div>
    <div class="panel-body">
        <?php $form0 = $batchDeleteForm->startForm(0); ?>
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
          <?php foreach ($batchDeleteForm->getModels() as $model): ?>
            <tr>
              <td>
                  <?= $form0->field($batchDeleteForm, 'modelIdsToDelete[]', ['template' => '{input}', 'options' => ['tag' => false]])->checkbox(['value' => $model->id, 'checked' => in_array($model->id, $batchDeleteForm->modelIdsToDelete)], false)->label(false) ?>
              </td>
                <?php foreach ($columns as $column => $label): ?>
                  <td><?= $model->{$column} ?></td>
                <?php endforeach; ?>
            </tr>
          <?php endforeach ?>
          </tbody>
        </table>
      </div>

      <?php
        $refModelTemplates = $batchDeleteForm->getReferencingModelTemplates();
      ?>
      <?php if (sizeof($refModelTemplates) > 0): ?>
        <div class="alert alert-warning" role="alert">
          These records could be recursively referenced in:
          <ul>
            <?php foreach ($refModelTemplates as $refModelTemplate): ?>
              <li><?= $refModelTemplate->fullName ?></li>
            <?php endforeach ?>
          </ul>
          <?php if ($batchDeleteForm->canDeleteRecursive): ?>
            <?= $form0->field($batchDeleteForm, 'deleteRecursive')->checkbox() ?>
          <?php else: ?>
            Records with relations to it cannot be deleted.
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <?php $batchDeleteForm->showErrors(); ?>
      <?= \yii\helpers\Html::submitButton('Continue', array_merge(['class' => 'btn btn-success'], ($batchDeleteForm->isFinished ? ['disabled' => 'disabled'] : []))) ?>
      <?php $batchDeleteForm->endForm(); ?>
    </div>
  </div>

    <?php if ($step > 1):?>
      <div class="panel panel-default">
        <div class="panel-heading">
          <h3 class="panel-title">2. Preview records to delete</h3>
        </div>
        <div class="panel-body">
            <?php $form1 = $batchDeleteForm->startForm(1); ?>
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
              <?php foreach ($batchDeleteForm->getSelectedModels() as $model): ?>
                <tr>
                    <?php foreach ($columns as $column => $label): ?>
                      <td><?= $model->{$column} ?></td>
                    <?php endforeach; ?>
                </tr>
              <?php endforeach ?>
              </tbody>
            </table>
          </div>

            <?php $batchDeleteForm->showErrors(); ?>
          <div class="alert alert-warning" role="alert">
            Please confirm that these records shall be deleted. This cannot be undone.
          </div>
          <?php if ($batchDeleteForm->deleteRecursive): ?>
            <div class="alert alert-danger">All related records will be deleted recursively!</div>
          <?php endif; ?>

          <?= \yii\helpers\Html::submitButton('Delete ' . sizeof($batchDeleteForm->getSelectedModels()). ' records' . ($batchDeleteForm->deleteRecursive ? ' with all related records!' : ''), array_merge(['class' => 'btn btn-warning'], ($batchDeleteForm->isFinished ? ['disabled' => 'disabled'] : []))) ?>
          <?php $batchDeleteForm->endForm(); ?>
      </div>
      </div>
    <?php endif; ?>


    <?php if ($step > 2): ?>
      <div class="panel panel-default">
        <div class="panel-heading">
          <h3 class="panel-title">3. List of deleted records</h3>
        </div>
        <div class="panel-body">
          <?php if (sizeof($batchDeleteForm->getDeletedModels()) > 0): ?>
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
              <?php foreach ($batchDeleteForm->getDeletedModels() as $model): ?>
                <tr>
                    <?php foreach ($columns as $column => $label): ?>
                      <td><?= $model->{$column} ?></td>
                    <?php endforeach; ?>
                </tr>
              <?php endforeach ?>
              </tbody>
            </table>
          </div>
          <div class="alert alert-info">
            <?= sizeof($batchDeleteForm->getDeletedModels()) ?> records <?= $batchDeleteForm->deleteRecursive ? ' with all related records ' : ' ' ?>have been deleted.
          </div>
          <?php else: ?>
            <div class="alert alert-warning" role="alert">
              No records have been deleted!
            </div>
          <?php endif; ?>
        </div>
      </div>

      <?php if (sizeof($batchDeleteForm->getDeleteErrorModels()) > 0 || $batchDeleteForm->getDeleteError() > ""): ?>
      <div class="panel panel-default">
        <div class="panel-heading">
          <h3 class="panel-title">4. List of records that could not be deleted</h3>
        </div>
        <div class="panel-body">
          <?php if (sizeof($batchDeleteForm->getDeleteErrorModels()) > 0): ?>
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
              <?php foreach ($batchDeleteForm->getDeleteErrorModels() as $model): ?>
                <tr class="error">
                    <?php foreach ($columns as $column => $label): ?>
                      <td><?= $model->{$column} ?></td>
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
              <?= sizeof($batchDeleteForm->getDeleteErrorModels()) ?> records could not be deleted.
          </div>
          <?php else: ?>
            <div class="alert alert-danger" role="alert">
              <?= $batchDeleteForm->getDeleteError() ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>

    <?php endif; ?>

</div>
