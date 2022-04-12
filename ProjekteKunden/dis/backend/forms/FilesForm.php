<?php

namespace app\forms;

use Yii;

/**
* @inheritdoc
*/
class FilesForm extends \app\models\ArchiveFile
{

    const FORM_NAME = 'files';

    public function scenarios() {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_DEFAULT] = ['parent_combined_id', 'type', 'filename', 'original_filename', 'upload_date', 'analyst', 'remarks', 'filesize', 'sample_request_id'];
        return $scenarios;
    }
}