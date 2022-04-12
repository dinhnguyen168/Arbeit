<?php
namespace app\components\web;

use Yii;

class Request extends \yii\web\Request
{

    /**
     * Change the Alias @web to a relative URL
     * @return array
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\web\NotFoundHttpException
     */
    public function resolve()
    {
        $web = rtrim(str_repeat("../", substr_count ( $this->getPathInfo() , "/")), "/");
        Yii::setAlias('@web', $web);
        Yii::$app->name = \Yii::$app->config['AppShortName'] ? \Yii::$app->config['AppShortName'] : "mDIS";

        return parent::resolve();
    }


    /**
     * Determines, based on the IP address of the client computer, whether it's
     * in the IP address range defined by the intranetIPs configuration setting.
     * @return bool
     */
    public function isFromIntranet () {
        $isFromIntranet = false;
        if (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
            $ipParts = explode('.', $ip);
            // Check all IntanetIPs entries
            if (isset(\Yii::$app->params["intranetIPs"])) {
                $intranetIPs = Yii::$app->params["intranetIPs"];
                if ($intranetIPs === true) {
                    $isFromIntranet = true;
                } elseif (is_array($intranetIPs)) {
                    $matches = [];
                    for ($i = 0; !$isFromIntranet && $i < sizeOf($intranetIPs); $i++) {
                        $intranetIP = $intranetIPs[$i];
                        if (preg_match('/([0-9]+\.[0-9]+\.[0-9]+\.[0-9]+),\s*([0-9]+\.[0-9]+\.[0-9]+\.[0-9]+)/', $intranetIP, $matches)) {
                            // If it is an array (network address and mask), check if IP belongs to subnet
                            // Maske und abgefragte Adresse bitweise verknuepfen
                            // Dies ergibt die Netzwerkadresse, wenn die IP zum Subnetz gehoehrt
                            $subNet = explode('.', $matches[1]); // Adresse, z.B. 192.168.0.0
                            $mask = explode('.', $matches[2]); // Maske, z.B. 255.255.255.0
                            $isFromIntranet = true;
                            for ($j = 0; $j < 4 && $isFromIntranet; $j++) {
                                if ((intval($ipParts[$j]) & intval($mask[$j])) != intval($subNet[$j])) {
                                    $isFromIntranet = false;
                                }
                            }
                        } else {
                            if (preg_match('/([0-9]+\.[0-9]+\.[0-9]+\.[0-9]+)/', $intranetIP)) {
                                // Wenn es sich um einen String handelt (einzelne IP), diesen vergleichen
                                if ($intranetIP == $ip) {
                                    $isFromIntranet = true;
                                }
                            }
                        }
                    }
                }
            }
        }
        return $isFromIntranet;
    }
}
