<?php

/**
 * @var \Da\User\Model\User  $user
 * @var \Da\User\Model\Token $token
 */
?>
<?= Yii::t('usuario', 'Hello') ?> <?= $user->username ?>,

<?= Yii::t('usuario', 'Thank you for signing up on {0}', Yii::$app->name) ?>.
<?= Yii::t('usuario', 'In order to complete your registration, please click the link below') ?>.

<?= $token->url ?>

<?= Yii::t('usuario', 'If you cannot click the link, please try pasting the text into your browser') ?>.

<?= Yii::t('usuario', 'If you did not make this request you can ignore this email') ?>.
