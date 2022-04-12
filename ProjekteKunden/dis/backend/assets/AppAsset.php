<?php
namespace app\assets;

use yii\web\AssetBundle;

/**
 * These assets are only used by the reports (and maybe the import tools)
 * @link https://www.yiiframework.com/doc/api/2.0/yii-web-assetbundle
 */
class AppAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $depends = [
        'yii\web\YiiAsset',
        'yii\bootstrap\BootstrapAsset',
    ];
}
