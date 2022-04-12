<?php

namespace app\dis_migration;


class Column extends \app\components\templates\ModelTemplateColumn
{

    public function importDataStructure($aMetaRecord, $oImportTableSchema)
    {
        $this->name = strtolower($aMetaRecord["ATTRIBUTE"]);
        $this->importSource = $aMetaRecord["ATTRIBUTE"];
        $this->type = $this->convertType($aMetaRecord["A_TYPE"], $aMetaRecord["A_SIZE"]);
        $this->size = $this->convertSize($aMetaRecord["A_SIZE"], $aMetaRecord["A_SIZE"]);
        $this->required = ($aMetaRecord["M"] == "Y");
        $this->primaryKey = ($aMetaRecord["M"] == "P");
        $this->autoInc = (strtolower($aMetaRecord["A_TYPE"]) == "auto");
        $this->label = $aMetaRecord["A_ALIAS"];
        $this->description = $aMetaRecord["A_DESC"];
        $this->validator = trim($this->removeHash($aMetaRecord["A_RANGE"]));
        $this->validatorMessage = $this->removeHash($aMetaRecord["A_RANGE_TEXT"]);
        $this->unit = $this->removeHash($aMetaRecord["A_METRICS"]);
        $this->selectListName = $this->convertListName($aMetaRecord["A_REF"]);

        $bValid = $this->validateMetaData($oImportTableSchema);

        if ($this->name == "skey" && $this->type == "integer") {
            $this->name = "id";
            $this->size = 11;
            $this->description .= "; auto incremented id";
            $this->autoInc = true;
        }

        $this->validator = preg_replace ("/^integer value */", "", $this->validator);
        if ($this->validator == "<>''") {
            $this->validator = "";
            $this->required = true;
        }

        if ($this->type == "string" AND $this->size < 10 AND preg_match("/IN.+(.*yes.*no.*)/", $this->validator)) {
            $this->type = "boolean";
            $this->size = 1;
            $this->validator = "";
            $this->validatorMessage = "";
            $this->importSource = "CASE LOWER(" . $oImportTableSchema->name . "." . $aMetaRecord["ATTRIBUTE"] . ") WHEN 'yes' THEN 1 WHEN 'no' THEN 0 ELSE NULL END";
        }

        if ($this->validator == "") {
            $this->validatorMessage = "";
        }

        $calculate = $this->removeHash($aMetaRecord["A_CALC"]);
        if ($calculate > '') {
            $aMatches = [];
            if (preg_match("/^D_(.+)$/", $calculate, $aMatches)) {
                $this->defaultValue = $aMatches[1];
                if ($this->type == 'boolean' && preg_match("/(yes|no)/", $this->defaultValue, $aMatches)) {
                    $this->defaultValue = $aMatches[1] == "yes" ? "1" : "0";
                }
                $this->defaultValue = preg_replace("/^'(.+)'$/", "$1", $this->defaultValue);
            } else if (preg_match("/^A_(.+)$/", $calculate, $aMatches)) {
                // ignore auto increment from legacy templates
                $this->calculate = '';
            } else {
                $this->calculate = preg_replace_callback("/\\[[A-Z_]+\\]/", function ($aMatches) {
                    return strtolower($aMatches[0]);
                }, $calculate);
            }
        }

        if ($this->type == "text" && $this->defaultValue > "") {
            echo "Column '" . $this->name . "'' of type 'text' may not have a default value!\n";
            $this->defaultValue = '';
        }

        return $bValid;
    }

    protected function convertType($cType, $nSize)
    {
        $cType = strtolower($cType);
        switch ($cType) {
            case "text":
                if ($nSize <= 255)
                    return "string";
                else
                    return "text";

            case "memo":
                return "text";

            case "numeric":
            case "integer":
                return "integer";

            case "long":
                return "bigInteger";

            case "auto":
                return "integer";

            case "date":
            case "datetime":
                return "dateTime";

            case "double":
                return "double";

            default:
                echo "ERROR: convertType(" . $cType . "): unknown type\n";
                return $cType;
        }
    }

    protected function convertSize($cType, $nSize)
    {
        $cType = strtolower($cType);
        switch ($cType) {
            case "text":
                if ($nSize > 255)
                    return null;
                else
                    return intval($nSize);

            default:
                return intval($nSize);
        }
    }

    protected function removeHash($cValue)
    {
        if (trim($cValue) == "#")
            return "";
        else
            return $cValue;
    }

    protected function convertListName($cList)
    {
        return preg_replace("/^L_/", "", $this->removeHash($cList));
    }

    protected function validateMetaData($oTableSchema)
    {
        foreach ($oTableSchema->columns as $oColumnSchema) {
            if ($oColumnSchema->name == $this->importSource) {
                // TODO: Check type, length, etc.
                return true;
            }
        }
        echo "Column " . $oTableSchema->name . "." . $this->importSource . " not found!\n";
        return false;
    }

    public function validate($attributeNames = null, $clearErrors = true)
    {
        if ($this->label == '') {
            $this->label = $this->_model->generateAttributeLabel($this->name);
        }
        return parent::validate($attributeNames, $clearErrors);
    }


}
