<?php
namespace app\assets;

use yii\web\AssetBundle;

/**
 * These assets are required to print the QR code on the labels of some reports.
 * You can modify the layout of the printed labels in file web/css/print-labels.scss.
 * @link https://www.yiiframework.com/doc/api/2.0/yii-web-assetbundle
 */
class PrintLabelsAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
        'css/print-labels.scss'
    ];
    public $js = [
        'js/qrcodejs/qrcode.js',
    ];
    public $depends = [
        'app\assets\AppAsset'
    ];
}
