<?php declare(strict_types=1);

/**
 * @copyright   (c) 2017-present brian ridley
 * @author      brian ridley <ptlis@ptlis.net>
 * @license     http://opensource.org/licenses/MIT MIT
 */

namespace ptlis\GrepDb\Metadata\MySQL;

/**
 * DTO storing column metadata.
 *
 * @todo Track unique keys seperately from indices
 */
final class ColumnMetadata
{
    /** @var string */
    private $databaseName;

    /** @var string */
    private $tableName;

    /** @var string */
    private $columnName;

    /** @var string */
    private $type;

    /** @var int|null */
    private $maxLength;

    /** @var bool */
    private $primaryKey;

    /** @var bool */
    private $nullable;

    /** @var bool */
    private $indexed;


    public function __construct(
        string $databaseName,
        string $tableName,
        string $columnName,
        string $type,
        ?int $maxLength,
        bool $primaryKey,
        bool $nullable,
        bool $indexed
    ) {
        $this->databaseName = $databaseName;
        $this->tableName = $tableName;
        $this->columnName = $columnName;
        $this->type = $type;
        $this->maxLength = $maxLength;
        $this->primaryKey = $primaryKey;
        $this->nullable = $nullable;
        $this->indexed = $indexed;
    }

    public function getDatabaseName(): string
    {
        return $this->databaseName;
    }

    public function getTableName(): string
    {
        return $this->tableName;
    }

    public function getColumnName(): string
    {
        return $this->columnName;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getMaxLength(): ?int
    {
        return $this->maxLength;
    }

    public function isPrimaryKey(): bool
    {
        return $this->primaryKey;
    }

    public function isNullable(): bool
    {
        return $this->nullable;
    }

    public function isIndexed(): bool
    {
        return $this->indexed;
    }

    /**
     * Returns true if the column is a string type.
     *
     * List validated against https://dev.mysql.com/doc/refman/5.7/en/string-types.html
     */
    public function isStringType()
    {
        $stringTypeList = [
            'char',
            'varchar',
            'tinyblob',
            'blob',
            'mediumblob',
            'longblob',
            'tinytext',
            'text',
            'mediumtext',
            'longtext'
        ];
        $isString = false;
        $type = trim(strtolower($this->getType()));
        foreach ($stringTypeList as $stringType) {
            // Column type has string type prefix (e.g. VARCHAR(100), TEXT)
            if (substr($type, 0, strlen($stringType)) === $stringType) {
                $isString = true;
            }
        }
        return $isString;
    }
}
