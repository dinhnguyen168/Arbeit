<?php


/**
 * @var \Da\User\Model\User  $user
 * @var \Da\User\Model\Token $token
 */

$url = "http://".$_SERVER['HTTP_HOST'] . "/#/reset-password/" . $user->id ."/" . $token->code;
?>
<?= Yii::t('usuario', 'Hello') ?> <?= $user->username ?>,

<?= Yii::t('usuario', 'We have received a request to reset the password for your account on {0}', Yii::$app->name) ?>.

<?= Yii::t('usuario', 'Please click the link below to complete your password reset') ?>.
After clicking on the link, a browser window will open, and you be able to set a new password.
Please note: This email is only valid for 6 hours, and you will be able to request a new one after 6 hours pass.
<?= Yii::t('usuario', 'If you cannot click the link, please try pasting the text into your browser') ?>.

<?= $url ?>

<?= Yii::t('usuario', 'If you did not make this request you can ignore this email') ?>.
