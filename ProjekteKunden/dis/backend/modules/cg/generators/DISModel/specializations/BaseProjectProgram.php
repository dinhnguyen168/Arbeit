<?php

namespace app\modules\cg\generators\DISModel\specializations;

/**
 * Class BaseProjectProgram
 * Extra properties added to the generated class BaseProjectProgram
 * - getIconUrl(): Get Icon of this program
 */

class BaseProjectProgram
{

    public function getIconUrl() {
        $filename = "img/logos/" . $this->program . ".png";
        if (!file_exists(\Yii::getAlias("@app/../web/") . $filename)) {
            $filename = "img/logos/none.png";
        }
        return \Yii::getAlias("@web/") . $filename;
    }

}

