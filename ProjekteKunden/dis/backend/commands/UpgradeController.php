<?php
namespace app\commands;


use yii\console\Controller;
use yii\helpers\Console;

/**
 * This commands are used to upgrade the dis app
 */
class UpgradeController extends Controller
{
    /**
     * This command is used to upgrade the dis app.
     * @param $versionNumber
     */
    public function actionIndex($versionNumber) {
        \Yii::$app->runAction('upgrade/' . 'upgrade-to-version' . $versionNumber);
    }

    /**
     * This command is used to upgrade the dis app to version 3.0.0.
     */
    public function actionUpgradeToVersion3() {
        if ($this->confirm("Are you sure you want to upgrade the current instance to the version 3.0.0 ?")) {
            self::copyDisTemplatesFiles();

            \Yii::$app->runAction('seed/templates-tables');

            \Yii::$app->runAction('migrate/mark', ['Da\User\Migration\m000000_000005_add_last_login_at']);
            \Yii::$app->runAction('migrate/up');

            \Yii::$app->runAction('fix-data/update-models', [null, 1]);
            \Yii::$app->runAction('fix-data/update-forms', [null, 1]);

            $this->stdout("Upgrade done.\n", Console::BG_GREEN);
        } else {
            $this->stdout("Upgrade canceled.\n", Console::BG_RED);
        }
    }

    const DIS_TEMPLATES_PATH = 'dis_templates';
    const DEFAULT_DIS_TEMPLATES_PATH = 'dis_templates/defaults';
    public static function copyDisTemplatesFiles () {
        $defaultModelsPath = \Yii::getAlias('@app') . '/' . self::DEFAULT_DIS_TEMPLATES_PATH . '/models';
        $disModelsPath = \Yii::getAlias('@app') . '/' . self::DIS_TEMPLATES_PATH . '/models';
        $disTemplateModels = \yii\helpers\FileHelper::findFiles($disModelsPath, ['only'=>['*.json']]);
        if (count($disTemplateModels) == 0) {
            echo "Coping dis_templates models from default folder. \n";
            foreach (\yii\helpers\FileHelper::findFiles($defaultModelsPath, ['only'=>['*.json']]) as $file) {
                if( !copy($file, $disModelsPath .'/' . basename($file)) ) {
                    echo "The file " . basename($file) . " can't be copied! \n";
                }
                else {
                    chmod($disModelsPath .'/' . basename($file), 0777);
                    echo "The file " . basename($file) . " has been copied! \n";
                }
            }
        } else {
            echo "dis_templates MODELS already exist. \n";
        }

        $defaultFormsPath = \Yii::getAlias('@app') . '/' . self::DEFAULT_DIS_TEMPLATES_PATH . '/forms';
        $disFormsPath = \Yii::getAlias('@app') . '/' . self::DIS_TEMPLATES_PATH . '/forms';
        $disTemplateModels = \yii\helpers\FileHelper::findFiles($disFormsPath, ['only'=>['*.json']]);
        if (count($disTemplateModels) == 0) {
            echo "Coping dis_templates forms from default folder. \n";
            foreach (\yii\helpers\FileHelper::findFiles($defaultFormsPath, ['only'=>['*.json']]) as $file) {
                if( !copy($file, $disFormsPath .'/' . basename($file)) ) {
                    echo "The file " . basename($file) . " can't be copied! \n";
                }
                else {
                    chmod($disFormsPath .'/' . basename($file), 0777);
                    echo "The file " . basename($file) . " has been copied! \n";
                }
            }
        } else {
            echo "dis_templates FORMS already exist. \n";
        }
    }
}
