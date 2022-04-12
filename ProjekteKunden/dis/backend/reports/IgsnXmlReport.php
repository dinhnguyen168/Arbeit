<?php
/**
 * Created by PhpStorm.
 * User: reckert
 * Date: 21.01.2019
 * Time: 14:59
 */

namespace app\reports;

use app\reports\interfaces\IXmlReport;

/**
 * Class IgsnXmlReport
 *
 * Creates an XML file to be imported into Corelizer
 *
 * @package app\reports
 */
class IgsnXmlReport extends Base implements IXmlReport
{
    /**
     * {@inheritdoc}
     */
    const TITLE = 'IGSN export';
    /**
     * {@inheritdoc}
     * This report can be applied to every data model.
     */
    const MODEL = '^(CoreCore|ProjectHole|CurationSample)$';

    const REPORT_TYPE = 'export';

    const SINGLE_RECORD = null;


    /**
     * {@inheritdoc}
     */
    function validateReport($options) {
        $valid = parent::validateReport($options);
        $valid = $this->validateColumns("ProjectProgram", ['program']) && $valid;
        $valid = $this->validateColumns("ProjectExpedition", ['project_location', 'country', 'state', 'county', 'city', 'rock_classification', 'geological_age', 'expedition', 'chief_scientist', 'start_date', 'end_date']) && $valid;
        $valid = $this->validateColumns("ProjectSite", ['expedition_id', 'platform_type', 'platform_name', 'platform_description', 'platform_operator']) && $valid;
        $valid = $this->validateColumns("ProjectHole", ['site_id', 'combined_id', 'igsn', 'comments', 'latitude_dec', 'longitude_dec', 'ground_level', 'igsn']) && $valid;
        $valid = $this->validateColumns("CoreCore", ['hole_id', 'top_depth', 'mcd_offset', 'bottom_depth', 'drilled_length', 'combined_id', 'igsn', 'core_ondeck']) && $valid;
        $valid = $this->validateColumns("CurationSectionSplit", ['igsn']) && $valid;
        $valid = $this->validateColumns("CurationSample", ['combined_id', 'igsn', 'request_no', 'scientist', 'sample_type', 'sample_date', 'top', 'bottom']) && $valid;
        $valid = $this->validateColumns("SampleRequest", ['request_complete', 'sample_material']) && $valid;
        return $valid;
    }



    /**
     * {@inheritdoc}
     */
    public function generate($options = []) {
        $dataProvider = $this->getDataProvider($options);
        $this->content = $this->_generate($dataProvider);
    }

    protected $rootXml = null;

    /**
     * Generates the report for all records in the dataProvider
     * @param \yii\data\ActiveDataProvider $dataProvider
     * @return string HTML of the rendered report
     */
    protected function _generate($dataProvider)
    {
        $this->rootXml = new \SimpleXMLElement($this->getXmlHead() . '<samples></samples>');
        $this->rootXml->addAttribute("xsi:schemaLocation", "http://doidb.wdc-terra.org/igsnaa http://doidb.wdc-terra.org/igsnaa/doidb.wdc-terra.org/igsnaa/0.1/samplev2.xsd");

        $modelClass = null;
        $dataProvider->pagination = false;
        $singleMode = ($dataProvider->getTotalCount() == 1);
        foreach ($dataProvider->getModels() as $model) {
            if ($modelClass == null) {
                $modelClass = get_class($model);
            }

            $xml = null;
            $cClass = preg_replace("/^.+\\\\/", "", $modelClass);
            $cClass = preg_replace ("/\\d+$/", "", $cClass);
            switch ($cClass) {
                case "ProjectHole":
                    $this->addProjectHoleXml($model);
                    break;

                case "CoreCore":
                    $this->addCoreCoreXml($model);
                    break;

                case "CurationSample":
                    $this->addCurationSampleXml($model);
                    break;
            }
        }

        return $this->rootXml->asXml();
    }

    protected function getXmlHead() {
        return '<?xml version="1.0" encoding="UTF-8"?>';
    }

    protected function formatDateTime($date) {
        if ($date) {
            $d = \DateTime::createFromFormat("Y-m-d H:i:s", $date);
            if (!$d) $d = \DateTime::createFromFormat("Y-m-d", $date);
            return preg_replace("/:0/", ":", $d->format("Y-n-j G:i:s"));
        }
        else
            return "";
    }



    protected function addProjectHoleXml($hole) {
        $final_depth = 0;
        $depth_max = 0;
        $depth_min = 99999;
        foreach ($hole->coreCores as $c) {
            $depth_min = min($depth_min, $c->top_depth + $c->mcd_offset);
            $depth_max = max($depth_max, $c->top_depth + $c->mcd_offset);
            $final_depth = max($final_depth, $c->bottom_depth);
        }

        $xml = $this->rootXml->addChild("sample");
        $xml->addChild("user_code", $hole->program->program);
        $xml->addChild("sample_type", "Hole");
        $xml->addChild("name", $hole->combined_id);
        $xml->addChild("igsn", $hole->igsn);
        $xml->addChild("parent_igsn", "");
        $xml->addChild("comments", $hole->comments);
        $xml->addChild("is_private", 0);
        // $xml->addChild("publish_date", ""); // It is the registration date. It is not to be filled by the DIS.
        $xml->addChild("latitude", $hole->latitude_dec);
        $xml->addChild("longitude", $hole->longitude_dec);
        $xml->addChild("coordinate_system", "WGS84");
        $xml->addChild("elevation", $hole->ground_level);
        $xml->addChild("elevation_end", $final_depth);
        $xml->addChild("elevation_unit", "m");
        $xml->addChild("elevation_end_unit", "m");
        $xml->addChild("primary_location_type", "");
        $xml->addChild("primary_location_name", $hole->expedition->project_location);
        $xml->addChild("location_description", "");
        $xml->addChild("locality", "");
        $xml->addChild("locality_description", "");
        $xml->addChild("country", $hole->expedition->country);
        $xml->addChild("province", $hole->expedition->state);
        $xml->addChild("county", $hole->expedition->county);
        $xml->addChild("city", $hole->expedition->city);
        $xml->addChild("material", "rock"); // will always be rock for hole, core, section.
        $xml->addChild("classification", $hole->expedition->rock_classification);
        $xml->addChild("field_name", ""); // Leave it empty
        $xml->addChild("depth_min", $depth_min);
        $xml->addChild("depth_max", $depth_max);
        $xml->addChild("depth_scale", "meter below ground level");
        $xml->addChild("size", $depth_max - $depth_min);
        $xml->addChild("size_unit", "m");
        $xml->addChild("age_unit", "");
        $xml->addChild("geological_age", $hole->expedition->geological_age);
        $xml->addChild("geological_unit", "");
        $xml->addChild("description", ""); //  it is the lithological description. Only relevant for section and sample. But it is not in the mDIS at the moment. It needs to be modified for every project.
        $xml->addChild("sample_image", "??"); //  the core overview report would be good there. I looked at an COSC IGSN and there the overview is linked to the our server (https://data.icdp-online.org/sites/cosc/news/cores/CO_5054_1_A_2-Z_1.jpg). Maybe @knb can say more to the path.
        $xml->addChild("sample_image_path", "??");


        /*
        $methodsXml = $xml->addChild("methods");
        $mXml = $methodsXml->addChild("method", "??"); $mXml->addAttribute("methodScheme", "MSCL");
        $mXml = $methodsXml->addChild("method", "??"); $mXml->addAttribute("methodScheme", "XRF");
        $mXml = $methodsXml->addChild("method", "??"); $mXml->addAttribute("methodScheme", "LITHOLOGICAL DESCRIPTION");
        $mXml = $methodsXml->addChild("method", "??"); $mXml->addAttribute("methodScheme", "CORE OVERVIEW");
        $mXml = $methodsXml->addChild("method", "??"); $mXml->addAttribute("methodScheme", "CORE SECTION SCAN");
        $mXml = $methodsXml->addChild("method", "??"); $mXml->addAttribute("methodScheme", "CORE CATCHER SCAN");
        */

        $xml->addChild("collection_method", ""); // It is not in the DIS. Maybe I will implement it. It would be information from several fields.
        $xml->addChild("collection_method_descr", ""); // It is not in the DIS. Maybe I will implement it. It would be information from several fields.
        $xml->addChild("length", ""); // TODO: ??
        $xml->addChild("length_unit", "m");
        $xml->addChild("sample_comment", ""); // leave empty
        $xml->addChild("cruise_field_prgrm", $hole->program->program . " " . $hole->expedition->expedition);
        $xml->addChild("platform_type", $hole->site->platform_type);
        $xml->addChild("platform_name", $hole->site->platform_name);
        $xml->addChild("platform_descr", $hole->site->platform_description);
        $xml->addChild("operator", $hole->site->platform_operator);
        $xml->addChild("funding_agency", ""); // not in DIS. leave it empty. needs to be filled later.
        $xml->addChild("collector", $hole->expedition->chief_scientist);
        $xml->addChild("collection_start_date", $this->formatDateTime($hole->expedition->start_date));
        $xml->addChild("collection_end_date", $this->formatDateTime($hole->expedition->end_date));
        $xml->addChild("collection_date_precision", "day");
        $xml->addChild("current_archive", ""); // TODO: ??
        $xml->addChild("current_archive_contact", ""); // TODO: ??
        $xml->addChild("original_archive", ""); // that is information to be inserted later and not in the mDIS. leave it empty
        $xml->addChild("original_archive_contact", ""); // that is information to be inserted later and not in the mDIS. leave it empty

        /*
                $relatedXml = $xml->addChild("relatedIdentifiers");
                $mXml = $relatedXml->addChild("relatedIdentifier", "??");
                $mXml->addAttribute("relatedIdentifierType", "DOI");
                $mXml->addAttribute("relationType", "isCitedBy");
        */
    }


    protected function addCoreCoreXml($core) {
        $final_depth = 0;
        foreach ($core->hole->coreCores as $c) {
            $final_depth = max($final_depth, $c->bottom_depth);
        }

        $xml = $this->rootXml->addChild("sample");
        $xml->addChild("user_code", $core->program->program);
        $xml->addChild("sample_type", $core::SHORT_NAME);
        $xml->addChild("name", $core->combined_id);
        $xml->addChild("igsn", $core->igsn);
        $xml->addChild("parent_igsn", $core->parent->igsn);
        $xml->addChild("is_private", 0);
        // $xml->addChild("publish_date", ""); // It is the registration date. It is not to be filled by the DIS.
        $xml->addChild("latitude", $core->hole->latitude_dec);
        $xml->addChild("longitude", $core->hole->longitude_dec);
        $xml->addChild("coordinate_system", "WGS84");
        $xml->addChild("elevation", $core->hole->ground_level);
        $xml->addChild("elevation_end", $final_depth);
        $xml->addChild("elevation_unit", "m");
        $xml->addChild("elevation_end_unit", "m");
        $xml->addChild("sampling_date", $this->formatDateTime($core->core_ondeck));
        $xml->addChild("primary_location_type", "");
        $xml->addChild("primary_location_name", $core->expedition->project_location);
        $xml->addChild("location_description", "");
        $xml->addChild("locality", "");
        $xml->addChild("locality_description", "");
        $xml->addChild("country", $core->expedition->country);
        $xml->addChild("province", $core->expedition->state);
        $xml->addChild("county", $core->expedition->county);
        $xml->addChild("city", $core->expedition->city);
        $xml->addChild("material", "rock"); // will always be rock for hole, core, section.
        $xml->addChild("classification", $core->expedition->rock_classification);
        $xml->addChild("field_name", ""); // Leave it empty
        $xml->addChild("depth_min", $core->top_depth);
        $xml->addChild("depth_max", $core->bottom_depth);
        $xml->addChild("depth_scale", "meter below ground level");
        $xml->addChild("size", $core->drilled_length);
        $xml->addChild("size_unit", "m");
        $xml->addChild("age_unit", "");
        $xml->addChild("geological_age", $core->expedition->geological_age);
        $xml->addChild("geological_unit", "");
        $xml->addChild("description", ""); //  it is the lithological description. Only relevant for section and sample. But it is not in the mDIS at the moment. It needs to be modified for every project.
        $xml->addChild("sample_image", "??"); //  the core overview report would be good there. I looked at an COSC IGSN and there the overview is linked to the our server (https://data.icdp-online.org/sites/cosc/news/cores/CO_5054_1_A_2-Z_1.jpg). Maybe @knb can say more to the path.
        $xml->addChild("sample_image_path", "??");


        $this->addMethods($xml, $core);

        $xml->addChild("collection_method", ""); // It is not in the DIS. Maybe I will implement it. It would be information from several fields.
        $xml->addChild("collection_method_descr", ""); // It is not in the DIS. Maybe I will implement it. It would be information from several fields.
        $xml->addChild("length", $core->drilled_length);
        $xml->addChild("length_unit", "m");
        $xml->addChild("sample_comment", ""); // leave empty
        $xml->addChild("cruise_field_prgrm", $core->program->program . " " . $core->expedition->expedition);
        $xml->addChild("platform_type", $core->site->platform_type);
        $xml->addChild("platform_name", $core->site->platform_name);
        $xml->addChild("platform_descr", $core->site->platform_description);
        $xml->addChild("operator", $core->site->platform_operator);
        $xml->addChild("funding_agency", ""); // not in DIS. leave it empty. needs to be filled later.
        $xml->addChild("collector", $core->expedition->chief_scientist);
        $xml->addChild("collection_start_date", $this->formatDateTime($core->expedition->start_date));
        $xml->addChild("collection_end_date", $this->formatDateTime($core->expedition->end_date));
        $xml->addChild("collection_date_precision", "day");
        $xml->addChild("current_archive", ""); // TODO: ??
        $xml->addChild("current_archive_contact", ""); // TODO: ??
        $xml->addChild("original_archive", ""); // that is information to be inserted later and not in the mDIS. leave it empty
        $xml->addChild("original_archive_contact", ""); // that is information to be inserted later and not in the mDIS. leave it empty

/*
        $relatedXml = $xml->addChild("relatedIdentifiers");
        $mXml = $relatedXml->addChild("relatedIdentifier", "??");
        $mXml->addAttribute("relatedIdentifierType", "DOI");
        $mXml->addAttribute("relationType", "isCitedBy");
*/
    }


    protected function addCurationSampleXml($sample) {
        $final_depth = 0;
        $length = 0;
        foreach ($sample->hole->coreCores as $c) {
            $final_depth = max($c->bottom_depth, $final_depth);
            $length += $c->drilled_length;
        }

        $sampleRequest = \app\models\SampleRequest::find()->where(["request_complete" => $sample->request_no])->one();

        $xml = $this->rootXml->addChild("sample");
        $xml->addChild("user_code", $sample->program->program);
        $xml->addChild("sample_type", "Core Sample");
        $xml->addChild("name", $sample->combined_id);
        $xml->addChild("igsn", $sample->igsn);
        $xml->addChild("parent_igsn", $sample->parent->igsn);
        $xml->addChild("is_private", 0);
        $xml->addChild("sample_request", $sample->request_no);
        $xml->addChild("sampled_by", $sample->scientist);
        $xml->addChild("sample_purpose", $sample->sample_type);
        // $xml->addChild("publish_date", ""); // It is the registration date. It is not to be filled by the DIS.
        $xml->addChild("latitude", $sample->hole->latitude_dec);
        $xml->addChild("longitude", $sample->hole->longitude_dec);
        $xml->addChild("coordinate_system", "WGS84");
        $xml->addChild("elevation", $sample->hole->ground_level);
        $xml->addChild("elevation_end", $final_depth);
        $xml->addChild("elevation_unit", "m");
        $xml->addChild("elevation_end_unit", "m");
        $xml->addChild("sample_date", $this->formatDateTime($sample->sample_date));
        $xml->addChild("primary_location_type", "");
        $xml->addChild("primary_location_name", $sample->expedition->project_location);
        $xml->addChild("location_description", "");
        $xml->addChild("locality", "");
        $xml->addChild("locality_description", "");
        $xml->addChild("country", $sample->expedition->country);
        $xml->addChild("province", $sample->expedition->state);
        $xml->addChild("county", $sample->expedition->county);
        $xml->addChild("city", $sample->expedition->city);
        $xml->addChild("material", $sampleRequest ? $sampleRequest->sample_material : "");
        $xml->addChild("classification", $sample->expedition->rock_classification);
        $xml->addChild("field_name", ""); // Leave it empty
        $xml->addChild("depth_min", $sample->section->top_depth + $sample->top / 100); // TODO: ??
        $xml->addChild("depth_max", $sample->section->top_depth + $sample->bottom / 100); // TODO: ??
        $xml->addChild("depth_scale", "meter below ground level");
        $xml->addChild("size", ($sample->bottom - $sample->top) / 100); // TODO: ??
        $xml->addChild("size_unit", "m");
        $xml->addChild("age_unit", "");
        $xml->addChild("geological_age", $sample->expedition->geological_age);
        $xml->addChild("geological_unit", "");
        /*
        $methodsXml = $xml->addChild("descriptions");
        $mXml = $methodsXml->addChild("description", "??"); $mXml->addAttribute("descriptionScheme", "Interval");
        $mXml = $methodsXml->addChild("description", "??"); $mXml->addAttribute("descriptionScheme", "Colours");
        $mXml = $methodsXml->addChild("description", "??"); $mXml->addAttribute("descriptionScheme", "Minerals");
        $mXml = $methodsXml->addChild("description", "??"); $mXml->addAttribute("descriptionScheme", "Grainsizes");
        $mXml = $methodsXml->addChild("description", "??"); $mXml->addAttribute("descriptionScheme", "Textures");
        $mXml = $methodsXml->addChild("description", "??"); $mXml->addAttribute("descriptionScheme", "Foliation");
        $mXml = $methodsXml->addChild("description", "??"); $mXml->addAttribute("descriptionScheme", "Disturbance");
        $mXml = $methodsXml->addChild("description", "??"); $mXml->addAttribute("descriptionScheme", "Discontinuities");
        */

        $filename = "";
        $archiveFile = $this->getSectionImage($sample->section);
        if ($archiveFile) $filename = $archiveFile->getConvertedFile();
        $xml->addChild("sample_image", basename($filename));
        $xml->addChild("sample_image_path", dirname($filename));

        $this->addMethods($xml, $sample->core);

        $xml->addChild("collection_method", ""); // It is not in the DIS. Maybe I will implement it. It would be information from several fields.
        $xml->addChild("collection_method_descr", ""); // It is not in the DIS. Maybe I will implement it. It would be information from several fields.
        $xml->addChild("length", $length);
        $xml->addChild("length_unit", "m");
        $xml->addChild("cruise_field_prgrm", $sample->program->program . " " . $sample->expedition->expedition);
        $xml->addChild("platform_type", $sample->site->platform_type);
        $xml->addChild("platform_name", $sample->site->platform_name);
        $xml->addChild("platform_descr", $sample->site->platform_description);
        $xml->addChild("operator", $sample->site->platform_operator);
        $xml->addChild("funding_agency", ""); // not in DIS. leave it empty. needs to be filled later.
        $xml->addChild("collector", $sample->expedition->chief_scientist);
        $xml->addChild("collection_start_date", $this->formatDateTime($sample->expedition->start_date));
        $xml->addChild("collection_end_date", $this->formatDateTime($sample->expedition->end_date));
        $xml->addChild("collection_date_precision", "day");
        $xml->addChild("current_archive", ""); // TODO: ??
        $xml->addChild("current_archive_contact", ""); // TODO: ??
        $xml->addChild("original_archive", ""); // that is information to be inserted later and not in the mDIS. leave it empty
        $xml->addChild("original_archive_contact", ""); // that is information to be inserted later and not in the mDIS. leave it empty

/*
        $relatedXml = $xml->addChild("relatedIdentifiers");
        $mXml = $relatedXml->addChild("relatedIdentifier", "??");
        $mXml->addAttribute("relatedIdentifierType", "DOI");
        $mXml->addAttribute("relationType", "isCitedBy");
*/
    }

    protected function addMethods($xml, $core) {
        if ($core->hasAttribute('methods_core')) {
            $methods = explode(";", $core->methods_core);
            if (sizeof($methods)) {
                $methodsXml = $xml->addChild("methods");
                foreach ($methods as $method) {
                    $mXml = $methodsXml->addChild("method", "yes");
                    $mXml->addAttribute("methodScheme", trim($method));
                }
            }
        }
    }


    protected function getTemplate()
    {
        // TODO: Implement getTemplate() method.
    }
}
