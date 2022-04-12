<?php

namespace app\modules\cg\generators\DISModel\specializations;

/**
 * Class BaseArchiveFile
 * Extra properties added to the generated class BaseArchiveFile
 *
 */

class BaseArchiveFile
{

    public function behaviors()
    {
        return array_merge(parent::behaviors(),
            [
                [
                    'class' => \app\behaviors\CombinedIdBehavior::class,
                    'combinedIdField' => 'parent_combined_id'
                ]
            ]);
    }



    /**
     * @return \yii\db\ActiveQuery
     */
    public function getParent()
    {
        if ($this->section_id > 0)
            return $this->getSection();
        else if ($this->core_id > 0)
            return $this->getCore();
        else if ($this->hole_id > 0)
            return $this->getHole();
        else if ($this->site_id > 0)
            return $this->getSite();
        else if ($this->expedition_id > 0)
            return $this->getExpedition();
        else
            return null;
    }



    public static $subFolders = [
        'original', // Original files
        'converted', // Converted to jpeg/tif/... without compression
        'reduced' // Reduced jpeg with compression
    ];


    public static function getDataPath() {
        $path = \Yii::getAlias('@app/data');
        if (!file_exists($path)) mkdir($path);
        return $path;
    }

    public static function getUploadPath() {
        $path = static::getDataPath() . "/upload";
        if (!file_exists($path)) mkdir($path);
        return $path;
    }

    function getFileTypeDirectory () {
        $row = \app\models\core\DisListItem::find()
            ->joinWith('list')
            ->where([
                'list_name' => 'UPLOAD_FILE_TYPE',
                'display' => $this->type
            ])
            ->one();
        if ($row == null) {
            throw new \yii\web\HttpException("Unknown file type $this->type");
        }
        return $row->remark;
    }

    /**
     * @param $subfolder "original"
     * @return string
     */
    public function getDirectory($subfolder = null) {
        if ($this->type == null)
            return $this->getUploadPath();
        else {
            $path = $this->getDataPath() . "/" . $this->getFileTypeDirectory();
            if (!file_exists($path)) {
                @mkdir($path);
            }
            if ($subfolder != null) {
                $path .= "/" . $subfolder;
                if (!file_exists($path)) {
                    @mkdir($path);
                }
            }
            return $path;
        }
    }

    public function getPath($subfolder = null) {
        $path = $this->getDirectory($subfolder) . "/" . ($this->type == null ? $this->original_filename : $this->filename);
        if (in_array($subfolder, ['converted', 'reduced'])) {
            $path_parts = pathinfo($path);
            $path_parts['extension'] = 'jpg';
            $path = $path_parts['dirname'] . '/' . $path_parts['filename'] . "." . $path_parts['extension'];
        }
        return $path;
    }

    public function getPreviewFile() {
        $file = $this->getPath('reduced');
        if (!file_exists($file)) {
            $file = $this->getPath('converted');
            if (!file_exists($file)) {
                $file = $this->getPath('original');
                if (!file_exists($file)) {
                    $file = null;
                }
            }
        }

        return $file;
    }

    public function getConvertedFile() {
        $file = $this->getPath('converted');
        if (!file_exists($file)) {
            $file = $this->getPath('original');
            if (!file_exists($file)) {
                $file = null;
            }
        }
        return $file;
    }

    public function getOriginalFile() {
        $file = $this->getPath('original');
        if (!file_exists($file)) {
            $file = null;
        }
        return $file;
    }


    protected static function getFileMetaData($filename) {
        $data = \app\modules\api\common\models\FilesUploadNewFormModel::getFileMetaData($filename);
        $cMeta = "";
        foreach ($data as $section => $sectionData) {
            $cMeta .= $section . ":\n";
            foreach ($sectionData as $key => $value) {
                if (!is_array($value)) {
                    $cMeta .= "- " . $key . ". " . $value . "\n";
                }
            }
            $cMeta .= "\n";
        }
        return $cMeta;
    }

    public function assignFileType($fileType) {
        assert($this->type == null);
        $oldFile = $this->getPath();
        if (file_exists($oldFile)) {
            $this->checksum = md5_file($oldFile);
            $this->filesize = filesize($oldFile);
            $this->mime_type = mime_content_type($oldFile);
//            if (empty($this->upload_date)) { $this->upload_date = date('Y-m-d h:i:s', filemtime($oldFile)); }
            $this->type = $fileType;
            $this->metadata = $this->getFileMetaData($oldFile);
            $this->filename = $this->buildFilename(); // Temporary Name without the id in ".F9999."

            // Already save to get an id used in the built file name
            if ($this->validate()) {
                if ($this->save()) {
                    $this->filename = $this->buildFilename();
                    $this->original_filename = basename($this->original_filename);

                    $dir = $this->getDirectory();
                    if (!is_dir($dir)) mkdir ($dir);

                    $dir = $this->getDirectory("original");
                    if (!is_dir($dir)) mkdir ($dir);

                    $newFile = $dir . "/" . $this->filename;
                    rename ($oldFile, $newFile);
                    $this->save();

                    $this->postProcess();
                    return true;
                }
                else {
                    $cErrors = implode(", ", $this->getFirstErrors());
                    throw new \yii\web\ServerErrorHttpException("Cannot save file " . $oldFile . ": " . $cErrors);
                }
            }
            else {
                // Validation error
                return $this;
            }
        }
        else
            throw new \yii\web\NotFoundHttpException("Upload file not found: " . $oldFile);
        return false;
    }

    public function unAssign() {
        $oldFile = $this->getPath("original");
        if (file_exists($oldFile)) {
            $this->type = null;
            $newFile = $this->getPath();
            if (!rename($oldFile, $newFile)) return false;
        }
        return $this->delete();
    }


    public function delete()
    {
        $id = $this->id;
        $result = parent::delete();

        if ($result !== false) {
            $matches = [];
            foreach (static::$subFolders as $subFolder) {
                $path = $this->getDirectory($subFolder);
                foreach (glob($path . "/*.F" . $id . ".*") as $file) {
                    if (preg_match('/\.F' . $id . '\.([^\.]+)$/', $file, $matches)) {
                        if (strlen($matches[1]) <= 5) {
                            unlink($file);
                        }
                    }
                }
            }
        }
        return $result;
    }


    protected function buildFilename() {
        $subfolder = null;
        $filename = $this->original_filename;
        switch ($this->type) {
            case 'UN':
                $filename = $this->type . "_" . $this->buildAncestorsFilename();
                if ($this->number > 0) $filename .= '_' . $this->number;
                $filename .= "_" . pathinfo($this->original_filename, PATHINFO_FILENAME);
                $filename .= "." . strtolower(pathinfo($this->original_filename, PATHINFO_EXTENSION));
                break;

            default:
                $filename = $this->type . "_" . $this->buildAncestorsFilename();
                if ($this->number > 0) $filename .= '_' . $this->number;
                $filename .= "." . strtolower(pathinfo($this->original_filename, PATHINFO_EXTENSION));
                break;
        }

        $path_parts = pathinfo($filename);
        $path_parts['extension'] = strtolower($path_parts['extension']);
        if ($path_parts['extension'] == "jpeg") $path_parts['extension'] == "jpg";

        $filename = $path_parts['filename'] . ".F" . ($this->id ? $this->id : "_____") . "." . $path_parts['extension'];
        return $filename;
    }

    protected function buildAncestorsFilename() {
        $labels = ['expedition' => '', 'site' => '', 'hole' => '', 'core' => '', 'section' => ''];

        $filename = "";
        foreach ($labels as $relation => $label) {
            $ancestor = $this->{$relation};
            if ($ancestor) {
                $filename .= "_" . $label . $ancestor->{constant(get_class($ancestor) . "::NAME_ATTRIBUTE")};
            }
        }
        return preg_replace("/^_/", "", $filename);
    }


    protected function postProcess() {
        if (preg_match("/^image/", $this->mime_type)) {
            switch ($this->type) {
                default:
                    $this->postProcessImage();
            }
        }

    }


    protected function postProcessImage() {
        $commands = [];
        $filename = $this->getPath("original");
        if (!in_array($this->mime_type, ["image/jpeg", "image/jpg"])) {
            $commands[] = $this->getConvertImageCmd($filename);
            $filename = $this->getPath("converted");
        }

        // Conditional statement to allow certain image types to be scaled according to height. Include the abbreviations of those image types  (e.g XX and YY): '$commands[] = ($this->type == "XX" || $this->type == "YY" ) ? ... '
        $commands[] = ($this->type == "CS" || $this->type == "SS") ? $this->getReduceCSCmd($filename) : $this->getReduceImageBoxCmd($filename);

        $this->runCommands($commands, $this->id);
    }

    protected $convertImageQuality = 80;

    protected function getConvertImageCmd($inputFile = null) {
        $cmd = '';
        $convertedDir = $this->getDirectory('converted');
        if (file_exists($convertedDir)) {
            if ($inputFile == null) $inputFile = $this->getPath("original");
            $outputFile = $this->getPath("converted");
            $cmd = 'convert -quality ' . $this->convertImageQuality . ' "' . $inputFile . '" "' . $outputFile . '"';
        }
        return $cmd;
    }


    protected $reduceImageQuality = 80;
    protected $reduceImageBox = 1000; // for basic rescaling function, side length of box in pixels to fit image into
    protected $reduceImageHeight;

    // This is the basic rescaling function that fits images into a box with $reduceImageBox as side length and preserves the aspect ratio.
    protected function getReduceImageBoxCmd($inputFile = null) {
        $cmd = '';
        $reducedDir = $this->getDirectory('reduced');
        if (file_exists($reducedDir)) {
            if ($inputFile == null) $inputFile = $this->getPath("converted");
            $outputFile = $this->getPath("reduced");
            $cmd = static::getImageResizeCommand($inputFile, $outputFile, $this->reduceImageBox, $this->reduceImageQuality);
        }
        return $cmd;
    }

    protected static function getImageResizeCommand($inputFile, $outputFile, $maxSize, $quality = 80) {
        $cmd = 'convert -quality ' . $quality . ' -resize ' . $maxSize . 'x' . $maxSize . ' "' . $inputFile . '" "' . $outputFile . '"';
        return $cmd;
    }

    public static function resizeImage ($inputFile, $outputFile, $maxSize, $quality = 80) {
        $cmd = static::getImageResizeCommand($inputFile, $outputFile, $maxSize, $quality);
        static::runCommands($cmd, 0, true);
    }

    // Rescaling function for image types CS and SS only: it scales the images according to the core diameter/circumference with constant resolution on core surface.
    protected function getReduceCSCmd($inputFile = null) {
        // retrieve core diameter from data base
        $corediameter = CoreCore::findOne([ 'id' => $this->core_id])->core_diameter;
        // set reduceimageheight proportional to circumference for unrolled scans and proportional to diameter for slabbed scans
        if ($this->type == 'CS') {
            $this->reduceImageHeight = round($corediameter * M_PI * 2); // last number is image resolution corresponding to pixel/mm on the core surface
        }
        else {
            $this->reduceImageHeight = round($corediameter * 2);
        }
        $cmd = '';
        // set source and target directories and resize the image
        $reducedDir = $this->getDirectory('reduced');
        if (file_exists($reducedDir)) {
            if ($inputFile == null) $inputFile = $this->getPath("converted");
            $outputFile = $this->getPath("reduced");
            $cmd = 'convert -quality ' . $this->reduceImageQuality . ' -resize x' . $this->reduceImageHeight . ' "' . $inputFile . '" "' . $outputFile . '"';
        }
        return $cmd;
    }

    protected static function runCommands($commands, $id = 0, $wait = false) {
        $logFile = \Yii::getAlias('@runtime/logs/convert.log');

        if (is_array($commands)) $commands = implode ("; ", $commands);
        file_put_contents($logFile, "\n" . date("Y-m-d H:i:s") . ": Convert file " . ($id ? $id : '') . ": " . $commands, FILE_APPEND);

        $command = sprintf(
            '(%s) >> "%s" 2>&1' . ($wait ? '' : ' &'),
            $commands,
            $logFile
        );
        $output = shell_exec($command);
    }





    public static function __processGeneratorParams ($params)
    {
        /**
         * This method is not inserted into the generated class file "backend/models/base/BaseCoreCore"
         * but could be used to modify the parameters from the generator. These parameters are
         * used in the template "default/baseModel.php" to generate the source code of the class.
         */

        $archiveFileTemplate = \Yii::$app->templates->getModelTemplate('ArchiveFile');
        $relations = $archiveFileTemplate->relations;
        $relationsTablesModels = [];
        foreach ($relations as $relation) {
            $foreignModel = array_filter(
                \Yii::$app->templates->getModelTemplates(),
                function ($value) use ($relation) {
                    return $value->table === $relation->foreignTable;
                });
            $foreignModel = reset($foreignModel);
            $relationsTablesModels[$relation->foreignTable] = $foreignModel;
        }

        $relationsTablesModelsNames = array_map(function ($value) {
            return $value ? $value->fullName : "";
        }, $relationsTablesModels);

        $formFilters = [];
        foreach ($relations as $relation) {
            $foreignModel = $relationsTablesModels[$relation->foreignTable];
            if (!$foreignModel) continue;
            $filterValueColumn = function ($foreignModel) :string {
                switch ($foreignModel->fullName) {
                case "ProjectExpedition":
                return"exp_acronym";
                case "ContactPerson":
                return"person_acronym";
                case "sample":
                return "combined_id";
                case "sampleRequest":
                return "combined_id";
                default:
                return $foreignModel->getCustomClass()::NAME_ATTRIBUTE;
                }
            }; 
            
            $formFilters[lcfirst($foreignModel->name)] = [
                "model" => $foreignModel->fullName,
                "value" => "id",
                "text" => $filterValueColumn($foreignModel),
                "ref" => $relation->localColumns[0]
            ];
            if ($foreignModel->parentModel && in_array($foreignModel->parentModel, array_values($relationsTablesModelsNames))) {
                $parentModel = $relationsTablesModels[array_search($foreignModel->parentModel, $relationsTablesModelsNames, true)];
                $requiredFilterRelation = array_filter($relations, function($value) use ($parentModel) {
                    return $value->foreignTable === $parentModel->table;
                });
                $requiredFilterRelation = reset($requiredFilterRelation);
                $formFilters[lcfirst($foreignModel->name)]["require"] = [
                    "value" => lcfirst($relationsTablesModels[$parentModel->table]->name),
                    "as" => $requiredFilterRelation->localColumns[0]
                ];
            }
        }

        $params["ancestorFormFilters"] = [];
        foreach ($formFilters as $name => $formFilter) {
            $params["ancestorFormFilters"][] = '"' . $name . '" => ' . preg_replace("/, *\\)/", ")", str_replace("\n", "", var_export ($formFilter, true)));
        }

        return $params;
    }

}

