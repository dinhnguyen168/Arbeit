<?php
namespace app\components\schema\mssql;

use yii\db\mssql\TableSchema;

class Schema extends \yii\db\mssql\Schema
{
    const TYPE_DATETIME2 = 'datetime2';

    /**
     * Collects the metadata of table columns.
     * @param TableSchema $table the table metadata
     * @return bool whether the table exists in the database
     */
    protected function findColumns($table)
    {
        $columnsTableName = 'INFORMATION_SCHEMA.COLUMNS';
        $whereSql = "[t1].[table_name] = " . $this->db->quoteValue($table->name);
        if ($table->catalogName !== null) {
            $columnsTableName = "{$table->catalogName}.{$columnsTableName}";
            $whereSql .= " AND [t1].[table_catalog] = '{$table->catalogName}'";
        }
        if ($table->schemaName !== null) {
            $whereSql .= " AND [t1].[table_schema] = '{$table->schemaName}'";
        }
        $columnsTableName = $this->quoteTableName($columnsTableName);

        $sql = <<<SQL
SELECT
 [t1].[column_name],
 [t1].[is_nullable],
 CASE WHEN [t1].[data_type] IN ('char','varchar','nchar','nvarchar','binary','varbinary') THEN
    CASE WHEN [t1].[character_maximum_length] = NULL OR [t1].[character_maximum_length] = -1 THEN
        [t1].[data_type]
    ELSE
        [t1].[data_type] + '(' + LTRIM(RTRIM(CONVERT(CHAR,[t1].[character_maximum_length]))) + ')'
    END
 ELSE
    [t1].[data_type]
 END AS 'data_type',
 [t1].[column_default],
 COLUMNPROPERTY(OBJECT_ID([t1].[table_schema] + '.' + [t1].[table_name]), [t1].[column_name], 'IsIdentity') AS is_identity,
 COLUMNPROPERTY(OBJECT_ID([t1].[table_schema] + '.' + [t1].[table_name]), [t1].[column_name], 'IsComputed') AS is_computed,
 (
    SELECT CONVERT(VARCHAR(MAX), [t2].[value])
		FROM [sys].[extended_properties] AS [t2]
		WHERE
			[t2].[class] = 1 AND
			[t2].[class_desc] = 'OBJECT_OR_COLUMN' AND
			[t2].[name] = 'MS_Description' AND
			[t2].[major_id] = OBJECT_ID([t1].[TABLE_SCHEMA] + '.' + [t1].[table_name]) AND
			[t2].[minor_id] = COLUMNPROPERTY(OBJECT_ID([t1].[TABLE_SCHEMA] + '.' + [t1].[TABLE_NAME]), [t1].[COLUMN_NAME], 'ColumnID')
 ) as comment
FROM {$columnsTableName} AS [t1]
WHERE {$whereSql}
SQL;

        try {
            $columns = $this->db->createCommand($sql)->queryAll();
            if (empty($columns)) {
                return false;
            }
        } catch (\Exception $e) {
            return false;
        }
        foreach ($columns as $column) {
            $column = $this->loadColumnSchema($column);
            foreach ($table->primaryKey as $primaryKey) {
                if (strcasecmp($column->name, $primaryKey) === 0) {
                    $column->isPrimaryKey = true;
                    break;
                }
            }
            if ($column->isPrimaryKey && $column->autoIncrement) {
                $table->sequenceName = '';
            }
            $table->columns[$column->name] = $column;
        }

        return true;
    }

    /**
     * Retrieving inserted data from a primary key request of type uniqueidentifier (for SQL Server 2005 or later)
     * {@inheritdoc}
     */
    /*public function insert($table, $columns)
    {
        $command = $this->db->createCommand()->insert($table, $columns);
        $sql = $command->rawSql;
        if (env('ALLOW_IDENTITY_INSERT') !== false
            && env('ALLOW_IDENTITY_INSERT') == '1'
            && isset($columns['id'])
            && $columns['id']
        )
        {
            $allowInsertIdentity = 'SET IDENTITY_INSERT {{%'.$table.'}} ON; ';
            $disallowInsertIdentity = ' ;SET IDENTITY_INSERT {{%'.$table.'}} OFF;';
            $sql = $allowInsertIdentity . $sql . $disallowInsertIdentity;
        }

        $command = $this->db->createCommand($sql);
        if (!$command->execute()) {
            return false;
        }

        $isVersion2005orLater = version_compare($this->db->getSchema()->getServerVersion(), '9', '>=');
        $inserted = $isVersion2005orLater ? $command->pdoStatement->fetch() : [];

        $tableSchema = $this->getTableSchema($table);
        $result = [];
        foreach ($tableSchema->primaryKey as $name) {
            // @see https://github.com/yiisoft/yii2/issues/13828 & https://github.com/yiisoft/yii2/issues/17474
            if (isset($inserted[$name])) {
                $result[$name] = $inserted[$name];
            } elseif ($tableSchema->columns[$name]->autoIncrement) {
                // for a version earlier than 2005
                $result[$name] = $this->getLastInsertID($tableSchema->sequenceName);
            } elseif (isset($columns[$name])) {
                $result[$name] = $columns[$name];
            } else {
                $result[$name] = $tableSchema->columns[$name]->defaultValue;
            }
        }

        return $result;
    }*/

    /**
     * @var array mapping from physical column types (keys) to abstract column types (values)
     */
    public $typeMap = [
        // exact numbers
        'bigint' => self::TYPE_BIGINT,
        'numeric' => self::TYPE_DECIMAL,
        'bit' => self::TYPE_SMALLINT,
        'smallint' => self::TYPE_SMALLINT,
        'decimal' => self::TYPE_DECIMAL,
        'smallmoney' => self::TYPE_MONEY,
        'int' => self::TYPE_INTEGER,
        'tinyint' => self::TYPE_TINYINT,
        'money' => self::TYPE_MONEY,
        // approximate numbers
        'float' => self::TYPE_FLOAT,
        'double' => self::TYPE_DOUBLE,
        'real' => self::TYPE_FLOAT,
        // date and time
        'date' => self::TYPE_DATE,
        'datetimeoffset' => self::TYPE_DATETIME,
        'datetime2' => self::TYPE_DATETIME2,
        'smalldatetime' => self::TYPE_DATETIME,
        'datetime' => self::TYPE_DATETIME,
        'time' => self::TYPE_TIME,
        // character strings
        'char' => self::TYPE_CHAR,
        'varchar' => self::TYPE_STRING,
        'text' => self::TYPE_TEXT,
        // unicode character strings
        'nchar' => self::TYPE_CHAR,
        'nvarchar' => self::TYPE_STRING,
        'ntext' => self::TYPE_TEXT,
        // binary strings
        'binary' => self::TYPE_BINARY,
        'varbinary' => self::TYPE_BINARY,
        'image' => self::TYPE_BINARY,
        // other data types
        // 'cursor' type cannot be used with tables
        'timestamp' => self::TYPE_TIMESTAMP,
        'hierarchyid' => self::TYPE_STRING,
        'uniqueidentifier' => self::TYPE_STRING,
        'sql_variant' => self::TYPE_STRING,
        'xml' => self::TYPE_STRING,
        'table' => self::TYPE_STRING,
    ];
}