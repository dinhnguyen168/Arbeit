<?php
/**
 *
 * Parameters for the view:
 * @var $header
 * @var $models
 * @var $splitForm
 * @var $splitResults
 **/
?>
<?= $header ?>
<div class="container">
    <?php
    $form = \yii\widgets\ActiveForm::begin([
        'id' => 'unsplit-form',

    ]) ?>  <table class="table">
    <thead>
    <tr>
      <th>Core</th>
      <th>Section</th>
      <th>Split ID</th>
      <th>Split Type</th>
      <th>Split Percent</th>
      <th>Still exists?</th>
      <th>Has samples?</th>
      <th>Undo Split now?</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($models as $core => $sections): ?>
    <tr>
        <?php
        $rowspan = 0;
        foreach ($sections as $splits) {
            $rowspan += count($splits);
        }
        ?>
      <td rowspan="<?= $rowspan ?>"><?= $core ?></td>
        <?php foreach ($sections as $section => $splits): ?>
      <td rowspan="<?= count($splits) ?>"><?= $section ?></td>
        <?php foreach ($splits as $split): ?>
        <?php $canSelect = $split['canSelect'] ?>
      <td><?= $split['id'] ?></td>
      <td><?= $split['type'] ?></td>
      <td><?= $split['percent'] ?></td>
      <td><?= $split['still_exists'] ?></td>
      <td><?= $split['hasSamples'] ? 'yes' : 'no' ?></td>
      <td>
          <?php if ($canSelect): ?>
              <?= $form->field($splitForm, 'idsToSplit['.$split['id'].']')->checkbox(['label' => null, 'disabled' => !$canSelect, 'checked' => $canSelect]) ?>
          <?php endif; ?>
      </td>
    </tr>
    <?php if ($split['id'] != $splits[count($splits) - 1]['id']):?>
    <tr>
        <?php endif; ?>
        <?php endforeach; ?>
        <?php if ($section != array_keys($sections)[count($sections) - 1]):?>
    <tr>
        <?php endif; ?>
        <?php endforeach; ?>
        <?php endforeach; ?>
    </tbody>
  </table>
  <div class="row">
    <div class="form-group">
        <?= \yii\helpers\Html::submitButton('Undo split', ['class' => 'btn btn-primary']) ?>
    </div>
      <?php \yii\widgets\ActiveForm::end() ?>
    <div class="report details-report">
      <ul>
          <?php foreach ($splitResults as $splitResult): ?>
            <li><?= $splitResult ?></li>
          <?php endforeach; ?>
      </ul>
    </div>
  </div>
</div>
