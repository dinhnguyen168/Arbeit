<?php
/**
 * Created by PhpStorm.
 * User: alali
 * Date: 10.07.2019
 * Time: 11:16
 */

namespace app\behaviors;

use yii\base\Behavior;
use yii\db\ActiveRecord;

/**
 * JsonFieldBehavior automatically converts the specified attributes from JSON string to array and back.
 *
 * To use JsonFieldBehavior, insert the following code to your ActiveRecord class:
 *
 * ```php
 * public function behaviors()
 * {
 *    return [
 *        [
 *            'class'  => \app\behaviors\JsonFieldBehavior::class,
 *            'fields' => ['foo_data', 'bar_data'],
 *        ],
 *    ];
 * ```
 */
class JsonFieldBehavior extends Behavior
{
    /**
     * @var string[] List of json field names
     */
    public $fields = [];
    /**
     * @var int JSON constants can be combined to form options for json_encode()
     *
     * The behaviour of these constants is described on the JSON constants page below.
     * http://php.net/manual/en/json.constants.php
     */
    public $jsonOptions = JSON_UNESCAPED_UNICODE;
    /**
     * @var array|string The default value for attribute.
     * This value by default will be stored in the database if the field value is empty.
     * Ignored if [[skipEmpty]] is enabled.
     */
    public $defaultValue = '[]';
    /**
     * @var bool Whether to skip a field if it's empty
     */
    public $skipEmpty = true;
    /**
     * @var bool Decode JSON into an array or object.
     * When TRUE, returned objects will be converted into associative arrays.
     */
    public $asArray = true;
    /** @inheritdoc */
    public function events()
    {
        return [
            ActiveRecord::EVENT_AFTER_FIND    => 'strToArray',
            ActiveRecord::EVENT_AFTER_INSERT  => 'strToArray',
            ActiveRecord::EVENT_AFTER_UPDATE  => 'strToArray',
            ActiveRecord::EVENT_BEFORE_INSERT => 'arrayToStr',
            ActiveRecord::EVENT_BEFORE_UPDATE => 'arrayToStr',
        ];
    }
    /**
     * Convert JSON string to array
     */
    public function strToArray()
    {
        foreach ($this->fields as $field) {
            $this->owner->$field = json_decode($this->owner->$field ?: '{}', $this->asArray);
        }
    }
    /**
     * Convert array to JSON string
     */
    public function arrayToStr()
    {
        foreach ($this->fields as $field) {
            $value = $this->owner->$field;
            if ($this->skipEmpty && $value === null) {
                continue;
            }
            if (empty($value)) {
                $value = is_string($this->defaultValue) ? $this->defaultValue : json_encode($this->defaultValue, $this->jsonOptions);
            } else {
                $value = json_encode($value, $this->jsonOptions);
            }
            $this->owner->$field = $value;
        }
    }
}