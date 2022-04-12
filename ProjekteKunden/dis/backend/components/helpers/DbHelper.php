<?php
namespace app\components\helpers;

class DbHelper
{
    /*
     * Check the link for more in info: https://github.com/yiisoft/yii2/issues/8420
     * A method to get an unBuffered Query PDO
     * When Using ,pass it to the $db parameters of batch()/each()
     * It Will establish a new Connection to mysql which PDO::MYSQL_ATTR_USE_BUFFERED_QUERY is false
     * ,and Only Used for batch()/each(),Other query could be done as normal during batch()/each()
     * $unbuffered_db = DbHelper::getUnbufferedMysqlDb($ModelClass::getDb());
     * foreach($activeQuery->batch(100,$unbuffered_db) as $models){
     *     #models
     *}
     * useful when dealing with huge mount of records
     */
    public static function getUnbufferedMysqlDb($db, $db_identifier = null)
    {
        $db_string = '';
        if (is_string($db)) { //TO SUPPORT the $db of Component Definition ID passed in string  ,for example $db='db'
            $db_string = $db;
            if (empty($db_identifier)) {
                $db_identifier = $db;
            }
            $db = \Yii::$app->get($db); // Convert string Component Definition ID to a Component
        }
        if (!($db instanceof \yii\db\Connection) || !strstr($db->getDriverName(), 'mysql')) { //Safe Check
            return null;
        };
        if (empty($db_identifier)) { //Generate a New String Component Definition ID if $db_identifier is not Provided
            $db_identifier = md5(sprintf("%s%s%s%s", $db->dsn, $db->username, $db->password,
                var_export($db->attributes, true)));
        }
        $db_identifier = 'unbuffered_' . $db_identifier;
        if (!\Yii::$app->has($db_identifier)) {
            if ($db_string) {
                $_unbuffered_db = \Yii::$app->getComponents()[$db_string];//Clone a Configuration
                $_unbuffered_db['attributes'][\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY] = false;
            } else {
                $_ = clone $db;
                $_->close(); //Ensure that it is not an active Connection Because PDO can not be serialize
                /** @var  $_unbuffered_db \yii\db\Connection */
                $_unbuffered_db = unserialize(serialize($_)); //Clone a Expensive Object //deep copy for safe
                $_unbuffered_db->attributes[\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY] = false;
            }
            \Yii::$app->setComponents([$db_identifier => $_unbuffered_db]);
        }

        return \Yii::$app->get($db_identifier);
    }

    /*
     ** PDO returns integer columns as strings by default
     * We disable that only for the current request
     */
    public static function handlePdoPreparedStatements() {
        switch (\Yii::$app->db->driverName) {
            case "mysql":
                $pdo = \Yii::$app->db->getMasterPdo();
                if ($pdo->getAttribute(\PDO::ATTR_EMULATE_PREPARES)) {
                    \Yii::$app->on(\yii\base\Application::EVENT_AFTER_REQUEST, function ($event) {
                        $pdo = \Yii::$app->db->getMasterPdo();
                        $pdo->setAttribute(\PDO::ATTR_EMULATE_PREPARES, 1);
                    });
                }
                $pdo->setAttribute(\PDO::ATTR_EMULATE_PREPARES, 0);
                break;
            case "sqlsrv":
                break;
        }
    }
}