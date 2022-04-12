<?php
namespace app\commands;

use Yii;
use yii\base\Exception;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;
use app\migrations\Migration;

/**
 * These commands are used to set application config values.
 *
 */
class ConfigController extends Controller
{

    protected $defaultKeys = ['AppName', 'AppShortName', 'CopyrightText', 'CopyrightLink', 'AboutLink', 'HelpLink', 'CanSendEmails', 'AppIcon'];
    public $defaultAction = 'list';
    /**
     * Lists all config values
     */
    public function actionList()
    {
        echo "\n";
        $config = array_merge(array_fill_keys($this->defaultKeys, ''), \Yii::$app->config->getAttributes());
        foreach ($config as $key => $value) {
            echo " - " . $key . " = " . print_r($value, true) . "\n";
        }
        echo "\n";
    }

    /**
     * Sets a config value
     * @param string $key Name of the config key
     * @param string $value Literals for integer, float or boolean values are converted into
     */
    public function actionSet($key, $value)
    {
        if ($value == strval(intval($value)))
             $value = intval($value);
        else if ($value == strval(floatval($value)))
             $value = floatval($value);
        else if (in_array(trim($value), ['true', 'false', 'yes', 'no']))
            $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
        else if (trim($key) == 'AppIcon' && trim($value)) {
            $directory = \Yii::getAlias("@app/../web/img/logos");
            $files = glob($directory . "/icon_" . trim($value) . ".*");
            if (sizeof($files) == 0) {
                echo "No image 'icon_" . trim($value) . ".png' was found in " . $directory . "\n\n";
                return;
            }
            foreach ($files as $file) {
                if (preg_match("/\\.(png|jpg)$/", $file))
                    $icon = basename($file);
                else {
                    echo "Only image files with extension png or jpg are supported: " . $file . "\n\n";
                    return;
                }
            }
        }


        Yii::$app->config[$key] = $value;
        Yii::$app->config->save();
        echo "Set config: " . $key . " = " . print_r($value, true) . "\n";
    }

    /**
     * Deletes a config value
     * @param string $key Name of the config key
     */
    public function actionDelete($key) {
        Yii::$app->config->offsetUnset($key);
        Yii::$app->config->save();
        echo "Deleted config: " . $key . "\n";
    }

    public function actionSetAppName($shortName, $longName = "", $icon = "") {
        echo "\n";
        if ($longName == "") $longName = $shortName;

        if ($icon) {
            $directory = \Yii::getAlias("@app/../web/img/logos");
            $files = glob($directory . "/icon_" . $icon . ".*");
            if (sizeof($files) == 0) {
                echo "No image 'icon_" . $icon . ".png' was found in " . $directory . "\n\n";
                return;
            }
            foreach ($files as $file) {
                if (preg_match("/\\.(png|jpg)$/", $file))
                    $icon = basename($file);
                else {
                    echo "Only image files with extension png or jpg are supported: " . $file . "\n\n";
                    return;
                }
            }
        }

        Yii::$app->config["AppShortName"] = $shortName;
        Yii::$app->config["AppName"] = $longName;
        echo "Set app name: \n";
        echo " - short name: " . $shortName . "\n";
        echo " - long name: " . $longName . "\n";
        if ($icon) {
            Yii::$app->config["AppIcon"] = $icon;
            echo " - icon file: " . $icon . "\n";
        }
        echo "\n";
        Yii::$app->config->save();
    }

}
