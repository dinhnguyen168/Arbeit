<?php


/**
 * @var \Da\User\Module      $module
 * @var \Da\User\Model\User  $user
 * @var \Da\User\Model\Token $token
 * @var bool                 $showPassword
 */
?>
<?= Yii::t('usuario', 'Hello') ?> <?= $user->username ?>,

<?= Yii::t('usuario', 'Your account on {0} has been created', Yii::$app->name) ?>.
<?php if ($showPassword || $module->generatePasswords): ?>
    <?= Yii::t('usuario', 'We have generated a password for you') ?>:
    <?= $user->password ?>
<?php endif ?>

<?php if ($token !== null): ?>
<?= Yii::t('usuario', 'In order to complete your registration, please click the link below') ?>.

<?= $token->url ?>

<?= Yii::t('usuario', 'If you cannot click the link, please try pasting the text into your browser') ?>.
<?php endif ?>

<?= Yii::t('usuario', 'If you did not make this request you can ignore this email') ?>.
