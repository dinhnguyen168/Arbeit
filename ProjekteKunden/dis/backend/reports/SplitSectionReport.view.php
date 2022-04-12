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
        'id' => 'split-form',
    ]) ?>
  <table class="table">
    <thead>
    <tr>
      <th>Core</th>
      <th>Section</th>
      <th>Split ID</th>
      <th>Split Type</th>
      <th>Split Percent</th>
      <th>Still exists?</th>
      <th>Split now?</th>
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
      <td><?= $split['id'] ?></td>
      <td><?= $split['type'] ?></td>
      <td><?= $split['percent'] ?></td>
      <td><?= $split['still_exists'] ?></td>
      <td>
          <?= $form->field($splitForm, 'idsToSplit['.$split['id'].']')->checkbox(['label' => null, 'disabled' => !$split['still_exists'], 'checked' => $split['type'] === 'WR' && $split['still_exists']]) ?>
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
    <div class="col col-sm-3">
        <?= $form->field($splitForm, 'firstSplitType')->dropdownList($splitForm->getTypesList(), ['prompt'=>'Select Archive Split', 'options' => [
            'W' => ['disabled' => true], 'A' => ['selected' => true],
        ]]) ?>
    </div>
    <div class="col col-sm-3">
        <?= $form->field($splitForm, 'firstSplitPercent')->textInput(["type" => "number"]) ?>
    </div>
  </div>
  <div class="row">
    <div class="col col-sm-3">
        <?= $form->field($splitForm, 'secondSplitType')->dropdownList($splitForm->getTypesList(), ['prompt'=>'Select Working Split', 'options' => [
            'A' => ['disabled' => true], 'W' => ['selected' => true],
        ]]) ?>
    </div>
    <div class="col col-sm-3">
        <?= $form->field($splitForm, 'secondSplitPercent')->textInput(["type" => "number"]) ?>
    </div>
  </div>
  <div class="form-group">
      <?= \yii\helpers\Html::submitButton('Split', ['class' => 'btn btn-primary']) ?>
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
