<?php


use yii\helpers\Html;

/**
 * @var \Da\User\Model\User  $user
 * @var \Da\User\Model\Token $token
 */

$url = \Yii::$app->params["baseUrl"] . "/#/reset-password/" . $user->id ."/" . $token->code;
?>
<p style="font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif; font-size: 14px; line-height: 1.6; font-weight: normal; margin: 0 0 10px; padding: 0;">
    <?= Yii::t('usuario', 'Hello') ?> <?= $user->username ?>,
</p>
</br>

<p style="font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif; font-size: 14px; line-height: 1.6; font-weight: normal; margin: 0 0 10px; padding: 0;">
    <?= Yii::t('usuario', 'We have received a request to reset the password for your account on <a href="{0}">{0}</a>', Yii::$app->params['baseUrl']) ?>.
</p>

<p style="font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif; font-size: 14px; line-height: 1.6; font-weight: normal; margin: 0 0 10px; padding: 0;">
    <?= Yii::t('usuario', 'Please click the link below to complete your password reset') ?>.  After clicking on the link, a browser window will open, and you be able to set a new password.  <?= Yii::t('usuario', 'If you cannot click the link, please try pasting the text into your browser') ?>.
</p>

</br>
<p style="font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif; font-size: 14px; line-height: 1.6; font-weight: normal; margin: 0 0 10px; padding: 0;">
    <?= Html::a(Html::encode($url), $url); ?>
</p>
</br>
</br>

<p style="font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif; font-size: 14px; line-height: 1.6; font-weight: normal; margin: 0 0 10px; padding: 0;">
    <b>Please note:</b> This email is only valid for 6 hours, and you will be able to request a new one after 6 hours pass.
</p>

</br>

<p style="font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif; font-size: 14px; line-height: 1.6; font-weight: normal; margin: 0 0 10px; padding: 0;">
    <?= Yii::t('usuario', 'If you did not make this request you can ignore this email') ?>.
</p>
