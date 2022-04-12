<?php

namespace app\modules\cg\generators\DISModel\specializations;

/**
 * Class BaseProjectExpedition
 * Extra properties added to the generated class BaseProjectExpedition
 * - getIconUrl(): Get Icon of this expedition
 */

class BaseProjectExpedition
{

    public function getIconUrl() {
        $filename = "img/logos/" . $this->exp_acronym . ".png";
        if (!file_exists(\Yii::getAlias("@app/../web/") . $filename)) {
            $filename = "img/logos/default.png";
        }
        return \Yii::getAlias("@web/") . $filename;
    }

}

