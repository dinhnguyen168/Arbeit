<?php
/**
 * Created by PhpStorm.
 * User: alali
 * Date: 20.03.2019
 * Time: 11:22
 */

namespace app\modules\api\common\models;

use Yii;


class FilesUploadNewFormModel extends \yii\base\Model
{
    /**
     * @var array the files to upload
     */
    public $files;

    /**
     * @var string Path to upload files to
     */
    public $uploadPath = '';

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['uploadPath', 'string'],
            [['files'], 'file', 'maxFiles' => 100, 'skipOnEmpty' => false],
        ];
    }

    /**
     * save the uploaded files in the upload folder
     * @return array of saved files
     */
    public function saveUploadedFiles() {
        $aFiles = [];
        $uploadPath = \app\models\ArchiveFile::getUploadPath() . "/" . trim($this->uploadPath, "/");
        $uploadPath = str_replace("..", "", $uploadPath);
        foreach ($this->files as $file) {
            $targetFile = $uploadPath . "/" . $file->basename . "." . $file->extension;
            $nCnt = 1;
            while (file_exists($targetFile)) {
                $nCnt++;
                $targetFile = $uploadPath . "/" . $file->basename  . "_" . $nCnt . "." . $file->extension;
            }
            $file->saveAs($targetFile);
            $aFiles[] = $targetFile;
        }
        return $aFiles;
    }

    /**
     * return meta data about the specified file
     * @param $cFile string file name
     * @return array of key value meta data
     */
    public static function getFileMetaData($cFile) {
        $aFile = ['FileName' => basename($cFile), 'FileSize' => \Yii::$app->formatter->asShortSize(filesize($cFile), 1), 'FileDateTime' => filemtime($cFile), 'MimeType' => mime_content_type($cFile)];
        $aData = ['FILE' => $aFile];
        try {
            $aExif = exif_read_data ($cFile, NULL, true, false);
            static::fixExif($aExif);
            $aData = array_merge($aData, $aExif);
        }
        catch (\Exception $e){};
        unset ($aData['FILE']['SectionsFound']);
        unset ($aData['FILE']['FileType']);
        $aData['FILE']['FileDateTime'] = date('Y-m-d h:i:s', $aData['FILE']['FileDateTime']);

        unset ($aData['THUMBNAIL']);
        return $aData;
    }

    /**
     * remove huge meta data values
     * @param $aExif array of meta data
     */
    protected static function fixExif(&$aExif) {
        foreach ($aExif as $key => &$value) {
            if (is_string($value)) {
                if (strlen($value) > 1000)
                    unset($aExif[$key]);
                else
                    $value = utf8_encode($value);
            }
            elseif (is_array($value)) {
                if (sizeof($value) > 100)
                    unset($aExif[$key]);
                else
                    static::fixExif($value);
            }
        }
    }

    /**
     * return thumbnail of the specified file as a base64 string
     * @param $cFile string file name
     * @return null|string
     */
    public static function getThumbNail($cFile)
    {
        $data = null;
        try {
            $aExif = exif_read_data ($cFile, 'THUMBNAIL', true, true);
            if (isset($aExif['THUMBNAIL']['THUMBNAIL'])) {
                $data = 'data:image/jpeg;base64,' . base64_encode($aExif['THUMBNAIL']['THUMBNAIL']);
            }
        }
        catch (\Exception $e){};

        if ($data == null) {
            $mime = mime_content_type ($cFile);
            if (preg_match("/^image\\//", $mime)) {
                // Delete old thumb files
                foreach (glob(sys_get_temp_dir() .  "/thumb_*.jpg") as $thumbFile) {
                    if (filemtime($thumbFile) < time() - 60) @unlink($thumbFile);
                }

                $extension = pathinfo($cFile, PATHINFO_EXTENSION);
                $thumbFile = sys_get_temp_dir() .  "/thumb_" . basename($cFile, "." . $extension) . ".jpg";
                if (file_exists($thumbFile)) @unlink($thumbFile);
                \app\models\ArchiveFile::resizeImage($cFile, $thumbFile, 800, 50);
                $mime = "image/jpeg";

                if (!file_exists($thumbFile)) {
                    $thumbFile = Yii::getAlias('@app/views/images/no-preview-available.png');
                    $mime = "image/png";
                }

                if (file_exists($thumbFile)) {
                    $content = base64_encode(file_get_contents($thumbFile));
                    $data = 'data:' . $mime . ';base64,' . $content;
                }
            }
        }
        return $data;
    }
}
