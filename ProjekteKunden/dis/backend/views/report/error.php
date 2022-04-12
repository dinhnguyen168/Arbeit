<?php

/* @var $this yii\web\View */
/* @var $title string Report name */
/* @var $class string Report class */
/* @var $errors array List of errors */
/* @var $code integer Http error code */

\Yii::$app->response->statusCode = $code ? $code : 500;
?>
<div class="report-error">
    <h1>Problems in report '<?= $title ?>'</h1>

    <h4>The report '<?=$title ?>' (<?= $class ?>) could not be rendered because of the following problems:</h4>
    <div class="alert alert-danger" role="alert">
      <p>
      <ul>
          <?php foreach ($errors as $error): ?>
            <li><?= $error ?></li>
          <?php endforeach; ?>
      </ul>
      </p>
    </div>
    <p>
        Please contact us if you think this is a server error. Thank you.
    </p>
</div>
