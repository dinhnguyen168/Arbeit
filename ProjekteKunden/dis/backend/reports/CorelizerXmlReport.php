<?php
/**
 * Created by PhpStorm.
 * User: reckert
 * Date: 21.01.2019
 * Time: 14:59
 */

namespace app\reports;

use app\reports\interfaces\IHtmlReport;
use yii\base\Model;

/**
 * Class CorelizerXmlReport
 *
 * Creates an XML file to be imported into Corelizer
 *
 * @package app\reports
 */
class CorelizerXmlReport extends Base implements IHtmlReport
{
    /**
     * {@inheritdoc}
     */
    const TITLE = 'Export to Corelizer';
    /**
     * {@inheritdoc}
     * This report can be applied to every data model.
     */
    const MODEL = 'CoreCore';

    const REPORT_TYPE = 'export';

    const SINGLE_RECORD = null;

    public function getCss() {
        $cssFile = \Yii::getAlias("@webroot/css/report.css");
        $stylesheet = file_get_contents($cssFile);
        return $stylesheet;
    }

    public function getJs()
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    function validateReport($options) {
        $valid = parent::validateReport($options);
        $valid = $this->validateColumns("ProjectExpedition", ['id', 'name']) && $valid;
        $valid = $this->validateColumns("ProjectSite", ['id', 'name', 'expedition_id']) && $valid;
        $valid = $this->validateColumns("ProjectHole", ['id', 'hole', 'site_id']) && $valid;
        $valid = $this->validateColumns("CoreCore", ['id', 'hole_id', 'core', 'core_type', 'top_depth', 'drilled_length']) && $valid;
        $valid = $this->validateColumns("CoreSection", ['id', 'core_id', 'section', 'top_depth', 'bottom_depth', 'section_length']) && $valid;
        $valid = $this->validateColumns("ArchiveFile", ['id']) && $valid;

        if ($valid) {
            $dataProvider = $this->getDataProvider($options);
            foreach ($dataProvider->getModels() as $model) {
                $expedition = $model->getExpedition();
                if ($this->expeditionID != null && $expedition->id != $this->expeditionID) {
                    $this->validateErrorCode = 500;
                    $this->validateErrors[] = "This report does not work for multiple expeditions.";
                    $valid = false;
                    break;
                }
                $this->expeditionID = $expedition->id;
            }
        }
        return $valid;
    }

    /**
     * {@inheritdoc}
     */
    public function generate($options = []) {
        $headerAttributes = [];
        $dataProvider = $this->getDataProvider($options);
        foreach ($dataProvider->getModels() as $model) {
          $this->setExpedition($model);
          foreach ($this->getAncestorValues($model) as $ancestorValue) {
            $headerAttributes[$ancestorValue[1]] = $ancestorValue[0];
          }
          break;
        }
        $modelClass = $this->getModelClass($options);
        if ($modelClass != "app\\models\\CoreSection") {
            $subQuery = $dataProvider->query->select(call_user_func([$modelClass, "tableName"]) . ".id");
            $query = \app\models\CoreSection::find();
            switch (preg_replace("/^.+\\\\/", "", $modelClass)) {
                case "CoreCore":
                    $query->andWhere(['IN', "core_section.core_id", $subQuery]);
                    break;
                case "ProjectHole":
                    $query->innerJoinWith("core");
                    $query->andWhere(['IN', "core_core.hole_id", $subQuery]);
                    break;
                case "ProjectSite":
                    $query->innerJoinWith("hole");
                    $query->andWhere(['IN', "project_hole.site_id", $subQuery]);
                    break;
                case "ProjectExpedition":
                    $query->innerJoinWith("site");
                    $query->andWhere(['IN', "project_site.expedition_id", $subQuery]);
                    break;
            }
            $dataProvider = $this->getDataProvider($query);
        }
        $dataProvider->pagination = false;

        return $this->_generate($dataProvider, $headerAttributes);
    }


    /**
     * Generates the report for all records in the dataProvider
     * @param \yii\data\ActiveDataProvider $dataProvider
     * @return string HTML of the rendered report
     */
    protected function _generate($dataProvider, $headerAttributes)
    {
      $exportFilename = "";
      $exportFilePath = "";
      $localExportFilePath = "";
      $showXml = "";

      $form = new OutputForm();
      $models = $dataProvider->getModels();
      if ($form->load(\Yii::$app->getRequest()->getBodyParams()) && $form->validate()) {

        if (isset ($_REQUEST["download"]) && file_exists($form->getFullExportPath())) {
          return $this->downloadGeneratedFiles($form);
        }

        $this->removeOldExports($form);
        @mkdir($form->getFullExportPath());
        $this->chmod($form->getFullExportPath());

        $dataProvider->pagination = false;
        foreach ($models as $section) {
          $sectionXml = $this->getSectionXml($section, $form);
        }
        $exportFilename = implode("_", array_values($headerAttributes)) . ".xml";
        $exportFilePath = $form->getFullExportPath() . $exportFilename;
        $localExportFilePath = $form->getFullLocalPath($exportFilename);
        file_put_contents($exportFilePath, $this->expeditionXml->asXml());
        $this->chmod($exportFilePath);
        $showXml = $this->formatXml($this->expeditionXml);

        if (isset ($_REQUEST["download"])) {
          return $this->downloadGeneratedFiles($form);
        }
      }

      // $this->getView()->registerCssFile("@web/css/report.css");
      $this->content = $this->renderString($this->getTemplate(), [
        'header' => $this->renderDisHeader($headerAttributes, "Export to Corelizer"),
        'formModel' => $form,
        'localExportFilePath' => $localExportFilePath,
        'showXml' => $showXml,
        'exportFilename' => $exportFilename
      ]);
    }

  /**
   * Creates the xml part for a section
   * @param $section
   * @param $form
   */
    protected function getSectionXml($section, $form) {
      set_time_limit (30);
      $coreXml = $this->getCoreXml($section);
      $sectionXml = $coreXml->addChild ("SECTION");
      $sectionXml->addAttribute("id", $section->id);
      $sectionXml->addAttribute("name", $section->section);
      $sectionXml->addAttribute("top_depth", $section->top_depth);
      $sectionXml->addAttribute("mcd_depth", $section->top_depth); // TODO
      $sectionXml->addAttribute("length", $section->section_length);

      $image = $this->getSectionImage($section);
      if ($image) {
        $archiveFilePath = $image->getConvertedFile();
        if ($archiveFilePath) {
          $archiveFilename = basename($archiveFilePath);
          // Remove ".F999" record identifier from filename
          $exportFilename = preg_replace ('/\.F' . $image->id . '\./', '.', $archiveFilename);
          copy ($archiveFilePath, $form->getFullExportPath() . $exportFilename);
          $this->chmod($form->getFullExportPath() . $exportFilename);
          $imageXml = $sectionXml->addChild("IMAGE");
          $imageXml->addAttribute("id", $image->id);
          $imageXml->addAttribute("name", $exportFilename);
          $imageXml->addAttribute("url", $form->getWebFullLocalPath($exportFilename));
        }
      }
    }

    protected function chmod($file) {
      @chmod($file, 0775);
    }

    protected $coresXml = [];

  /**
   * Creates the xml part for the core (referenced by a section)
   * @param $section
   * @return mixed
   */
    protected function getCoreXml($section) {
        if (!isset($this->coresXml[$section->core_id])) {
            $core = $section->core;
            $holeXml = $this->getHoleXml($core);
            $coreXml = $holeXml->addChild ("CORE");
            $coreXml->addAttribute("id", $core->id);
            $coreXml->addAttribute("name", $core->core . $core->core_type);
            $coreXml->addAttribute("top_depth", $core->top_depth);
            $coreXml->addAttribute("mcd_depth", $core->top_depth); // TODO
            $coreXml->addAttribute("length", $core->drilled_length);
            $this->coresXml[$section->core_id] = $coreXml;
        }
        return $this->coresXml[$section->core_id];
    }


    protected $holesXml = [];

  /**
   * Creates the XML part for the hole (referenced by a core)
   * @param $core
   * @return mixed
   */
    protected function getHoleXml($core) {
        if (!isset($this->holesXml[$core->hole_id])) {
            $hole = $core->hole;
            $siteXml = $this->getSiteXml($hole);
            $holeXml = $siteXml->addChild ("HOLE");
            $holeXml->addAttribute("id", $hole->id);
            $holeXml->addAttribute("name", $hole->hole);
            $this->holesXml[$core->hole_id] = $holeXml;
        }
        return $this->holesXml[$core->hole_id];
    }


    protected $sitesXml = [];

  /**
   * Creates the XML part for the site (referenced by a hole)
   * @param $hole
   * @return mixed
   */
    protected function getSiteXml($hole) {
        if (!isset($this->sitesXml[$hole->site_id])) {
            $site = $hole->site;
            $expeditionXml = $this->getExpeditionXml($site);
            $siteXml = $expeditionXml->addChild ("SITE");
            $siteXml->addAttribute("id", $site->id);
            $siteXml->addAttribute("name", $site->name);
            $this->sitesXml[$hole->site_id] = $siteXml;
        }
        return $this->sitesXml[$hole->site_id];
    }

    protected $expeditionXml = null;
    protected $expeditionID = null;

  /**
   * Creates the XML part for the expedition (referenced by a site)
   * @param $site
   * @return \SimpleXMLElement|null
   */
    protected function getExpeditionXml($site) {
        $expedition = $site->expedition;
        if ($this->expeditionXml == null) {
            $this->expeditionID = $expedition->id;
            $this->expeditionXml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><EXPEDITION></EXPEDITION>');
            $this->expeditionXml->addAttribute("id", $expedition->id);
            $this->expeditionXml->addAttribute("name", $expedition->name);
            $this->expeditionXml->addAttribute("highresformat", "jpg");
            $this->expeditionXml->addAttribute("topoffset", 0);
            $this->expeditionXml->addAttribute("bottomoffset", 0);
        }
        else if ($expedition->id !== $this->expeditionID) {
            die ("multiple expeditions");
        }
        return $this->expeditionXml;
    }


  /**
   * Formats the xml to display in a textarea form field.
   * @param $simpleXMLElement
   * @return string
   */
    protected function formatXml ($simpleXMLElement)
    {
        $xmlDocument = new \DOMDocument('1.0');
        $xmlDocument->preserveWhiteSpace = false;
        $xmlDocument->formatOutput = true;
        $xmlDocument->loadXML($simpleXMLElement->asXML());
        return $xmlDocument->saveXML();
    }


  /**
   * @return string Template to render the GUI
   */
    public function getTemplate() {
      return <<<'EOD'
        <?= $header ?>
<div class="report corelizer-xml-report">
    <?php
      $generated = ($localExportFilePath > "");
      $form = \yii\widgets\ActiveForm::begin([
        'id' => 'corlizer-xml',
        ]) ?>
    <div class="row">
      <div class="col col-sm-12">
        <?= $form->field($formModel, 'folder')->hiddenInput()->label(false) ?>
        <?= $form->field($formModel, 'exportPath')->textInput(['disabled' => true]) ?>
      </div>
    </div>
    <?php if (!$generated || $formModel->localPath > ''): ?>
    <div class="row">
      <div class="col col-sm-12">
        <?= $form->field($formModel, 'localPath')->textInput($generated ? ['disabled' => true] : []) ?>
      </div>
    </div>
    <?php endif; ?>
    <?php if($generated): ?>
      <div class="alert alert-success" role="alert">
        <div>
          On the server the files have been exported to directory<br>
          <b><?= $formModel->getFullExportPath() ?></b>.
        </div>
         <?= \yii\helpers\Html::submitButton('Download generated files', ['class' => 'btn btn-success', 'name' => 'download']) ?>
      </div>
      <?php if ($formModel->localPath > ""): ?>
        <div class="alert alert-success" role="alert">
          In your local filesystem you find the files in directory. Otherwise copy them into that folder.<br>
          <b><?= $formModel->getFullLocalPath() ?></b>
        </div>
      <?php endif; ?> 
      
      <div class="alert alert-light" role="alert">
        Content of the exported file "<?= $exportFilename ?>"<br/>
        <textarea style="width:100%" rows="20">
        <?= ltrim($showXml) ?>
        </textarea>
      </div>
    <?php else: ?>
    <div class="form-group">
      <?= \yii\helpers\Html::submitButton('Export', ['class' => 'btn btn-primary']) ?>
    </div>
    <?php endif; ?>
    <?php \yii\widgets\ActiveForm::end() ?>
</div>
EOD;
    }

  /**
   * Maximum time (in hours) an export folder is kept on the server.
   */
    const EXPORT_MAX_AGE_HOURS = 8;


  /**
   * Delete all export folders and zip files older than the time given in EXPORT_MAX_AGE_HOURS
   * @param $form
   * @throws \yii\base\ErrorException
   */
    protected function removeOldExports($form) {
      foreach (glob ($form->exportPath . OutputForm::FOLDER_PREFIX . "*") as $file) {
        if (filemtime($file) < time() - static::EXPORT_MAX_AGE_HOURS * 60 * 60) {
          if (is_dir($file))
            \yii\helpers\FileHelper::removeDirectory($file);
          else
            @unlink ($file);
        }
      }
    }


  /**
   * Zip all exported files and download the zip file.
   * @param $form
   * @throws \yii\base\ErrorException
   */
    protected function downloadGeneratedFiles($form) {
      $zipFile = $form->exportPath . $form->folder . ".zip";
      $zip = new \ZipArchive();
      $zip->open($zipFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

      $rootPath = $form->getFullExportPath();
      $files = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator($rootPath),
        \RecursiveIteratorIterator::LEAVES_ONLY
      );
      foreach ($files as $name => $file)
      {
        // Skip directories (they would be added automatically)
        if (!$file->isDir())
        {
          // Get real and relative path for current file
          $filePath = $file->getRealPath();
          $relativePath = substr($filePath, strlen($rootPath));

          // Add current file to archive
          $zip->addFile($filePath, $relativePath);
        }
      }
      $zip->close();

      \yii\helpers\FileHelper::removeDirectory($rootPath);

      \Yii::$app->response->sendFile($zipFile, basename($zipFile))->on(\yii\web\Response::EVENT_AFTER_SEND, function($event) {
        unlink($event->data);
      }, $zipFile);

    }

}


class OutputForm extends Model
{
  /**
   * Prefix for the export folders
   */
  const FOLDER_PREFIX = "CorelizerXmlExport_";

  /**
   * @var Path on the server in with a folder for the export is created.
   */
  public $exportPath;

  /**
   * @var Local path  the is used to create the absolute file names in the xml file
   */
  public $localPath;

  /**
   * @var Folder name created for the export
   */
  public $folder;

  /**
   * @inheritDoc
   */
  public function init() {
    parent::init();
    $this->folder = static::FOLDER_PREFIX . (new \DateTime("now"))->format("Y-m-d_h.i.s");
    $this->exportPath = \app\models\ArchiveFile::getUploadPath() . "/";
  }

  /**
   * @return string Full export path with trailing slash
   */
  public function getFullExportPath() {
    return $this->exportPath . $this->folder . "/";
  }

  /**
   * @param string $filename
   * @return string Full local path (of given filename)
   */
  public function getFullLocalPath($filename = "") {
    $separator = strpos($this->localPath,"\\") !== FALSE ? "\\" : "/";
    return ($this->localPath ? $this->localPath . $this->folder . $separator : "") . $filename;
  }


  /**
   * @param string $filename
   * @return string Full local path, beginning with "file://", (of given filename)
   */
  public function getWebFullLocalPath($filename = "") {
    $path = ($this->localPath ? str_replace("\\", "/", $this->localPath . $this->folder) . "/" : "") . $filename;
    $path = "file:///" . ltrim($path, "/");
    return $path;
  }

  /**
   * @inheritDoc
   */
  public function rules()
  {
    return [
      [['exportPath', 'localPath', 'folder'], 'safe'],
      ['localPath', 'string'],
      ['exportPath', 'validatePaths']
    ];
  }

  /**
   * @inheritDoc
   */
  public function attributeLabels() {
    return [
      'exportPath' => 'Export path on the server',
      'localPath' => 'If you can access that path on your local machine, enter the local path for it. Otherwise enter the local path where you will download the files into. This path will be used in the generated xml file (i.e. "C:\Virtual\upload").'
    ];
  }

  /**
   * Normalizes the exportPath and localPath
   * @param $attribute Name of validated attribute
   * @param $params
   */
  public function validatePaths($attribute, $params) {
    $this->exportPath = rtrim($this->exportPath, "/") . "/";
    if (empty($this->localPath))
      $this->localPath = "";
    else {
      $separator = strpos($this->localPath,"\\") !== FALSE ? "\\" : "/";
      $this->localPath = rtrim($this->localPath, $separator) . $separator;
    }
  }

}
