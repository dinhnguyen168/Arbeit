<?php
namespace app\components;

use app\models\core\DisIgsn;
use function Webmozart\Assert\Tests\StaticAnalysis\isEmptyString;

/**
 * Manegement of IGSN numbers
 * - Unique creation with different approaches
 * - Updating from existing data
 * - Later: registering
 */
class Igsn extends \yii\base\Component {

    const METHOD_ICDP_CLASSIC = "ICDP_classic";
    const METHOD_ICDP_2021 = "ICDP_2021";
    const METHOD_FIXED_PREFIX = "fixed_prefix";

    /**
     * @var string Method to calculate IGSNs
     */
    public $method = self::METHOD_ICDP_CLASSIC;

    /**
     * @var int Maximum length of the full igsn number
     */
    public $maxLength = 0;

    /**
     * @var int Length of the variable (i.e. incremental) part of the igsn
     */
    public $variablePartLength = 0;

    /**
     * @var string Prefix of the igsn number
     */
    public $prefix = "";

    /**
     * @var bool Save IGSN numbers to data table 'dis_igsn'
     * For some methods (i.e. METHOD_FIXED_PREFIX) this is necessary.
     */
    public $saveToDatabase = false;

    /**
     * @var string Only for ICDP methods: default prefixes
     */
    public $defaultProgramPrefix = "";
    public $defaultExpeditionPrefix = "";
    public $defaultRepPrefix = "E"; // "E" = expedition OR "R" = repository
    public $defaultSitePrefix= "0";
    public $defaultHolePrefix= " ";


    public static $IcdpObjectTags = [
        'HOLE' => 'H',
        'CORE' => 'C',
        'SECTION' => 'S',
        'SAMPLE' => 'X',
        'BOTTLE' => 'B',
        'WATER' => 'W',
        'CUTTING' => 'U',
        'THINSECTION' => 'T',
        'SMEARSLIDE' => 'Y',
        'MUD' => 'Y',
        'SIDEWALL' => 'Z',
        'FLUIDGAS' => 'F'
    ];


    public function __construct ($config = []) {
        // set default values depending on method
        $method = isset($config["method"]) ? $config["method"] : $this->method;
        if (in_array($method, [self::METHOD_ICDP_CLASSIC])) {
            foreach (["defaultProgramPrefix" => "ICDP",
                      "defaultExpeditionPrefix" => "5063",
                      "defaultRepPrefix" => "E",
                      "variablePartLength" => 5,
                      "maxLength" => 16
                     ] as $key => $value) {
                if (!isset($config[$key])) $config[$key] = $value;
            }
        }

        foreach (["variablePartLength"] as $key) {
            if (!isset($config[$key])) {
                throw new \yii\base\InvalidConfigException("Igsn-Component: config-value '" . $key . "' is required");
            }
        }

        parent::__construct($config);

        if (!in_array($this->method, [self::METHOD_ICDP_CLASSIC, self::METHOD_ICDP_2021, self::METHOD_FIXED_PREFIX])) {
            throw new \yii\base\InvalidConfigException("Igsn-Component: unknown method '" . $this->method . "'");
        }

        if ($this->method == self::METHOD_FIXED_PREFIX) {
            // This method does not work without database
            $this->saveToDatabase = true;
        }

        if (strpos($this->prefix, "%") !== FALSE || strpos($this->prefix, "'") !== FALSE) {
            throw new \yii\base\InvalidConfigException("Igsn-Component: prefix must not contain '%' OR \"'\"");
        }

    }

    public function isObjectTagRequired() {
        return (in_array($this->method, [self::METHOD_ICDP_CLASSIC, self::METHOD_ICDP_2021]));
    }

    public static function getModelClassName($model) {
        $className = get_class($model);
        if (defined($className . '::FORM_NAME')) {
            $className = get_parent_class($model);
        }
        $className = \yii\helpers\StringHelper::basename($className);
        return $className;
    }

    /**
     * Igsn generation for method METHOD_ICDP_CLASSIC
     * @param $model Model to create IGSN number for
     * @param $objectTag Optional Object tag (Used in ICDP methods)
     * @return string IGSN number
     **/
    public function createIgsn ($model, $objectTag = "") {
        switch ($this->method) {
            case self::METHOD_ICDP_CLASSIC:
                return $this->createIcdpClassicIgsn($model, $objectTag);

            case self::METHOD_ICDP_2021:
                return $this->createIcdp2021Igsn($model, $objectTag);

            case self::METHOD_FIXED_PREFIX:
                return $this->createFixedPrefixIgsn ($model);

            default:
                throw new \yii\base\InvalidConfigException("Igsn-Component: unknown method '" . $this->method . "'");
        }
    }


    /**
     * Save IGSN number to database
     * Optimistic approach: Dont search for existing model or IGSN in advance but handle errors if insertion does not work.
     * @param $igsnNumber IGSN number to save
     * @param $model Model to save it for
     * @throws \Exception
     */
    public function saveIgsn($igsnNumber, $model) {
        if (!$this->saveToDatabase && in_array($this->method, [self::METHOD_FIXED_PREFIX])) {
            throw new IgsnException ("Igsn::saveIgsn() Method METHOD_FIXED_PREFIX does not work without activated database", IgsnException::CODE_SAVE_DISABLED, null, $model);
        }

        if ($this->saveToDatabase) {
            $className = static::getModelClassName($model);
            $igsn = new DisIgsn(['igsn' => $igsnNumber,
                'model' => $className,
                'model_id' => $model->id]);

            if ($igsn->validate()) {
                try {
                    $saved = $igsn->save();
                }
                catch (\Exception $e) {
                    $existingIgsn = DisIgsn::find()->where(['igsn' => $igsnNumber])->one();
                    if ($existingIgsn) {
                        if ($existingIgsn->model !== $className || $existingIgsn->model_id !== $model->id) {
                            throw new IgsnException ("Duplicate IGSN number '" . $igsnNumber . "' for model " . $className . ":" . $model->id . " already exists for " . $existingIgsn->model . ":" . $existingIgsn->model_id, IgsnException::CODE_DUPLICATE_IGSN, null, $model);
                        }
                    }
                    else {
                        $existingModelEntry = DisIgsn::find()->where(['model' => $className])->andWhere(['model_id' => $model->id])->one();
                        // Must have a different igsn otherwise existingIgsn would have been found.
                        if ($existingModelEntry) {
                            throw new IgsnException ("Changed IGSN number for model " . $className . ":" . $model->id . ": existing: '" . $existingModelEntry->igsn . "', new: '" . $igsnNumber . "'", IgsnException::CODE_IGSN_CHANGED, null, $model);
                        }
                        else
                            throw new IgsnException ("Cannot save IGSN number '" . $igsnNumber . "' for model " . $className . ":" . $model->id . ": " . $e->getMessage(), 0, null, $model);
                    }
                }
            }
            else
                throw new IgsnException("IGSN not valid: " . print_r($igsn->getErrorSummary(false), true), IgsnException::CODE_NOT_VALID, null, $model);
        }
    }


    /**
     * Check if igsn number already exists (or exists for a different model)
     * @param $igsnNumber IGSN number to check
     * @param $model Model to check
     */
    public function validateIgsn ($igsnNumber, $model = null) {
        if ($this->saveToDatabase) {
            $className = static::getModelClassName($model);
            $existingIgsn = DisIgsn::find()->where(['igsn' => $igsnNumber])->one();
            if ($existingIgsn) {
                if ($model == null || $model->getIsNewRecord())
                    return false;
                else if ($existingIgsn->model !== $className || $existingIgsn->model_id !== $model->id)
                    return false;
            }
        }
        return true;
    }



    /**
     * Igsn generation for method METHOD_ICDP_CLASSIC
     * @param $model Model to create IGSN number for
     * @param $objectTag Object tag used in the prefix (see $IcdpObjectTags)
     * @return string IGSN number
   **/
    protected function createIcdpClassicIgsn($model, $objectTag) {
        $progPrefix = $this->defaultProgramPrefix;
        $expPrefix = $this->defaultExpeditionPrefix;
        $repPrefix = $this->defaultRepPrefix;

        $expedition = $model->expedition;
        if ($expedition) {
            $expPrefix = str_pad($expedition->expedition, 4, "0", STR_PAD_LEFT);
            $program = $expedition->program;
            if ($program) {
                $progPrefix = strtoupper(str_pad($program->program_acronym, 4, ' ', STR_PAD_RIGHT));
            }
        }
        $S_PREFIX = $objectTag;
        $this->prefix = $progPrefix . $expPrefix . $repPrefix . $S_PREFIX;

        $igsnNumber = strrev(str_pad(strtoupper(base_convert ( "" . ($model->id + pow(36, 4)), 10, 36)), $this->variablePartLength, "0", STR_PAD_LEFT));
        $calcOldStyle = $this->calculateIcdpIgsnOldStyle($model->id);
        if ($calcOldStyle !== $igsnNumber) {
            throw new IgsnException("Error in new Algorithm: old calulcation:" . $calcOldStyle . ", new:" . $igsnNumber, IgsnException::CODE_ALGORITHM_ERROR, null, $model);
        }

        $igsnNumber = $this->prefix . $igsnNumber;

        // By default, IGSNs are not saved to data table 'dis_igsn'. That can be activated by 'saveToDatabse'
        $this->saveIgsn($igsnNumber, $model);

        return $igsnNumber;
    }

    protected function calculateIcdpIgsnOldStyle ($num) {
        $alphabet = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $base = strlen($alphabet);
        $maxLen = 5;
        $pad = $maxLen -1;
        $num = ($num + (pow($base,$pad)));
        $fNum = $num;
        $fBase = $base;
        $fNum = log($fNum);
        $fBase = log($fBase);
        $i = (int) ($fNum / $fBase);
        $ret = "";

        while ($i >= 0) {
            $BCP = pow($base, $i);
            // decrease index for loop here
            $i = $i - 1;
            $A = (int) ((int)($num / $BCP) % $base);
            $ret = $ret . substr($alphabet, $A, 1);
            $num = $num - ($A * $BCP);
        }
        $ret = strrev($ret);
        return $ret;
    }


    /**
     * Igsn generation for method METHOD_IGSN_2021
     * @param $model Model to create igsn for
     * @return string IGSN number
     * @throws \Exception
     */
    protected function createIcdp2021Igsn($model, $objectTag) {
        $progPrefix = $this->defaultProgramPrefix;
        $expPrefix = $this->defaultExpeditionPrefix;
        $repPrefix = $this->defaultRepPrefix;
        $sitePrefix = $this->defaultSitePrefix;
        $holePrefix = $this->defaultHolePrefix;

        $expedition = $model->expedition;
        if ($expedition) {
            $expPrefix = $expedition->expedition;
            $program = $expedition->program;
            if ($program) {
                $progPrefix = $program->program;
            }
        }

        if (isset($model->site)) $sitePrefix = is_object($model->site) ? $model->site->site : $model->site;
        if (isset($model->hole)) $holePrefix = is_object($model->hole) ? $model->hole->hole : $model->hole;

        // Do not pad progPrefix
        $progPrefix = strtoupper($progPrefix);
        $expPrefix = str_pad($expPrefix, 4, "0", STR_PAD_LEFT);
        $sitePrefix = str_pad($sitePrefix, 2, "0", STR_PAD_LEFT);
        $holePrefix = str_pad($holePrefix, 1, " ", STR_PAD_RIGHT);
        $this->prefix = $progPrefix . $expPrefix . $repPrefix . $sitePrefix . $holePrefix . $objectTag;

        $igsnNumber = strrev(str_pad(strtoupper(base_convert ( "" . ($model->id + pow(36, 4)), 10, 36)), $this->variablePartLength, "0", STR_PAD_LEFT));
        $igsnNumber = $this->prefix . $igsnNumber;

        $this->saveIgsn($igsnNumber, $model);

        return $igsnNumber;
    }



    /**
     * Igsn generation for method METHOD_FIXED_PREFIX
     * @param $model Model to create igsn for
     * @return string IGSN number
     * @throws IgsnException
     */
    protected function createFixedPrefixIgsn ($model)
    {
        if (!$this->saveToDatabase) {
            throw new IgsnException ("Igsn::createIgsn() Method METHOD_FIXED_PREFIX does not work without activated database", IgsnException::CODE_SAVE_DISABLED, null, $model);
        }

        $query = DisIgsn::find()->where("igsn LIKE '" . $this->prefix . "%'")->orderBy(['igsn' => SORT_DESC]);
        // $sql = $query->createCommand()->getRawSql();
        $lastIgsn = $query->one();
        if ($lastIgsn) {
            $variablePart = substr($lastIgsn->igsn, strlen($this->prefix));
            $newNumber = (intval($variablePart) + 1);
        }
        else
            $newNumber = 1;
        $igsnNumber = str_pad((string)$newNumber, $this->variablePartLength, "0", STR_PAD_LEFT);
        $igsnNumber = $this->prefix . $igsnNumber;

        $this->saveIgsn($igsnNumber, $model);
        return $igsnNumber;
    }


    /**
     * Update the IGSN number of the given model
     * There must be a column 'igsn' in the model
     * @param $model
     * @param string $objectTag Optional object tag to use for creating the ISGN number
     * @throws \yii\base\InvalidConfigException
     */
    protected function updateModelRecord ($model, $objectTag = "") {
        if (empty($model->igsn) || strlen($model->igsn) == 1) {
            $model->igsn = $this->createIgsn($model, $objectTag);
            $model->save();
        }
        else {
            $this->saveIgsn($model->igsn, $model);
        }
    }

    /**
     * Update the IGSN numbers of all records of the given model
     * @param $modelClass (i.e. "CoreCore")
     * @param integer $pass On pass 1 only records with existing IGSN numbers are updated,
     * on pass 2 only records without IGSN numbers are updated
     */
    public function updateModelRecords ($modelClass, $pass = 1)
    {
        $shortClass = \yii\helpers\StringHelper::basename($modelClass);
        if (strpos($modelClass, '\\') === false) $modelClass = 'app\\models\\' . $modelClass;

        // For ICDP methods the object tag is provided in the default value of the column in the model template
        $objectTag = "";
        $modelTemplate = \Yii::$app->templates->getModelTemplate($shortClass);
        if (!isset ($modelTemplate->columns['igsn'])) {
            echo "Model '" . $shortClass . "' does not have a column 'igsn'\n";
            return;
        }

        echo "Update " . ($pass == 1 ? "existing" : "missing") . " IGSN numbers of " . $shortClass . "\n";

        $templateColumn = $modelTemplate->columns['igsn'];
        if ($templateColumn->defaultValue > "" && strlen($templateColumn->defaultValue) == 1) {
            $objectTag = $templateColumn->defaultValue;
        }

        $condition = $pass == 1 ? ['>', 'LENGTH(igsn)', 1] : ['OR', ['IS', 'igsn', null], ['<=', 'LENGTH(igsn)', 1]];
        foreach (call_user_func([$modelClass, 'find'])->where($condition)->batch() as $models) {
            foreach ($models as $model) {
                $this->updateModelRecord($model, $objectTag);
                echo ".";
            }
            echo "\n";
        }
        echo "\n";
    }


    /**
     * Update the IGSN numbers of all records of all models
     */
    public function updateAllModels ()
    {
        echo "Update existing IGSN numbers on all models\n\n";
        foreach (\Yii::$app->templates->getModelTemplates() as $modelTemplate) {
            $this->updateModelRecords($modelTemplate->fullName, 1);
        }
        echo "Update missing IGSN numbers on all models\n\n";
        foreach (\Yii::$app->templates->getModelTemplates() as $modelTemplate) {
            $this->updateModelRecords($modelTemplate->fullName, 2);
        }

    }

}


/**
 * Exception class of IGSN creation errors
 * Class IgsnException
 * @package app\components
 */
class IgsnException extends \yii\base\Exception {
    const CODE_DUPLICATE_IGSN = 1;
    const CODE_IGSN_CHANGED = 2;
    const CODE_DATATABLE_MISSING = 20;
    const CODE_SAVE_DISABLED = 21;
    const CODE_ALGORITHM_ERROR = 22;
    const CODE_NOT_VALID = 23;

    public $model;

    public function __construct ($message = "", $code = 0, $previous = null, $model = null) {
        parent::__construct($message, $code, $previous);
        $this->model = $model;
    }

    /**
     * Get a description of the model containing of class name + record id
     * @return string
     */
    public function getModelDescription() {
        if ($this->model) {
            $modelClass = Igsn::getModelClassName($this->model);
            return $modelClass . ":" . $this->model->id;
        }
        else
            return "[no model given]";
    }

}

