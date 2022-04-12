<?php
/**
 * Created by PhpStorm.
 * User: alali
 * Date: 03.09.2018
 * Time: 14:17
 */

namespace app\migrations;

use yii\db\ColumnSchemaBuilder;
use yii\db\Migration as BaseMigration;

class Migration extends BaseMigration
{
    /**
     * @var string
     */
    protected $tableOptions;
    protected $restrict = 'RESTRICT';
    protected $cascade = 'CASCADE';
    protected $dbType;
    protected $booleanType = 'tinyint';
    protected $doubleType = 'double';
    protected $dateTime = 'dateTime';
    protected $showIndexesStatement = "SHOW INDEXES FROM [TableNamePlaceHolder]";
    protected $showTableStatusStatement = "SHOW TABLE STATUS WHERE Name=:tableName";

    public function getRestrict(): string
    {
        return $this->restrict;
    }


    public function getCascade(): string
    {
        return $this->cascade;
    }

    public function getBooleanType($asMYSQLFunction = false) :string
    {
        if ($asMYSQLFunction && $this->db->driverName == 'mysql') {
            return strtoupper($this->booleanType). "(1)";
        }
        return $this->booleanType;
    }

    public function getDoubleType() :string
    {
        return $this->doubleType;
    }

    public function getDateTimeType() :string
    {
        return $this->dateTime;
    }

    public function getShowIndexesStatement($table) :string
    {
        return str_replace('[TableNamePlaceHolder]', $table, $this->showIndexesStatement);
    }

    public function getShowTableStatusStatement() : string
    {
        return $this->showTableStatusStatement;
    }

    public function getFKOnUpdate($table, $foreignTable)
    {
        $onUpdate = "CASCADE";
        if($this->dbType == 'sqlsrv') {
            if ($table == $foreignTable) {
                $onUpdate = "NO ACTION";
            }

            $sql = "SELECT
    C.CONSTRAINT_NAME,
    PK.TABLE_NAME,
    CCU.COLUMN_NAME,
    FK.TABLE_NAME,
    CU.COLUMN_NAME,
    C.UPDATE_RULE,
    C.DELETE_RULE
FROM
    INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS C INNER JOIN
    INFORMATION_SCHEMA.TABLE_CONSTRAINTS FK ON C.CONSTRAINT_NAME = FK.CONSTRAINT_NAME INNER JOIN
    INFORMATION_SCHEMA.TABLE_CONSTRAINTS PK ON C.UNIQUE_CONSTRAINT_NAME = PK.CONSTRAINT_NAME INNER JOIN
    INFORMATION_SCHEMA.KEY_COLUMN_USAGE CU ON C.CONSTRAINT_NAME = CU.CONSTRAINT_NAME INNER JOIN
    INFORMATION_SCHEMA.CONSTRAINT_COLUMN_USAGE CCU ON PK.CONSTRAINT_NAME = CCU.CONSTRAINT_NAME
WHERE
    ((C.UPDATE_RULE = 'CASCADE') OR (C.DELETE_RULE = 'CASCADE')) AND
    (FK.CONSTRAINT_TYPE = 'FOREIGN KEY')
ORDER BY
    PK.TABLE_NAME, 
    FK.TABLE_NAME;";
            $fks = \Yii::$app->db->createCommand($sql)->queryAll();
            foreach ($fks as $fk) {
                if ($fk['TABLE_NAME'] == $table && $fk['UPDATE_RULE'] == 'CASCADE') {
                    $onUpdate = 'NO ACTION';
                    break;
                }
            }

        }
        return $onUpdate;
    }

    public function getDbRegEXStatement($fullColumnName, $value)
    {
        if($this->dbType == 'sqlsrv') {
            return "dbo.IsMatch($fullColumnName, '$value') = 1";
        }
        return ['regexp', $fullColumnName, $value];
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        switch ($this->db->driverName) {
            case 'mysql':
                $this->tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci ENGINE=InnoDB';
                $this->dbType = 'mysql';
                break;
            case 'sqlsrv':
                $this->tableOptions = '';
                $this->dbType = 'sqlsrv';
                $this->restrict = 'NO ACTION';
                $this->cascade = 'NO ACTION';
                $this->booleanType = 'bit';
                $this->doubleType = 'float';
                $this->dateTime = 'datetime2';
                $this->showIndexesStatement = "SELECT OBJECT_NAME(a.object_id) AS 'TABLE',
 	CASE WHEN a.is_primary_key LIKE 1 THEN 'PRIMARY' ELSE a.name END AS Key_name,
 	CASE WHEN a.is_unique LIKE 1 THEN 0 ELSE 1 END AS Non_unique,
 	COL_NAME(b.object_id,b.column_id) AS Column_name
FROM
 	sys.indexes AS a
INNER JOIN
 	sys.index_columns AS b
   ON a.object_id = b.object_id AND a.index_id = b.index_id
WHERE
   a.is_hypothetical = 0 AND
 	a.object_id = OBJECT_ID('[TableNamePlaceHolder]');";
                $this->showTableStatusStatement = "select t3.value as 'Comment' from sysobjects t inner join sys.tables t2 on t2.object_id = t.id left join sys.extended_properties t3 on t3.major_id=t.id and t3.name='MS_Description' and t3.minor_id=0 where t.name=:tableName";
                break;
            default:
                throw new \RuntimeException("Your database (" . $this->db->driverName . ") is not supported!");
        }
    }

    /**
     * Creates a datetime2 column.
     * @param int $precision column value precision. First parameter passed to the column type, e.g. DATETIME2(precision).
     * This parameter will be ignored if not supported by the DBMS.
     * @return ColumnSchemaBuilder the column instance which can be further customized.
     * @since 2.0.6
     */
    public function dateTime2($precision = null)
    {
        return $this->getDb()->getSchema()->createColumnSchemaBuilder(\app\components\schema\mssql\Schema::TYPE_DATETIME2, $precision);
    }
}