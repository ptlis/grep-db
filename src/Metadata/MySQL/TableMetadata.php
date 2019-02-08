<?php declare(strict_types=1);

/**
 * @copyright   (c) 2017-present brian ridley
 * @author      brian ridley <ptlis@ptlis.net>
 * @license     http://opensource.org/licenses/MIT MIT
 */

namespace ptlis\GrepDb\Metadata\MySQL;

/**
 * DTO storing table metadata.
 */
final class TableMetadata
{
    /** @var string */
    private $databaseName;

    /** @var string */
    private $tableName;

    /** @var string */
    private $engine;

    /** @var string */
    private $collation;

    /** @var string */
    private $charset;

    /** @var int */
    private $rowCount;

    /** @var ColumnMetadata[] */
    private $columnMetadataList = [];


    /**
     * @param string $databaseName
     * @param string $tableName
     * @param string $engine
     * @param string $collation
     * @param string $charset
     * @param int $rowCount
     * @param ColumnMetadata[] $columnMetadataList
     */
    public function __construct(
        string $databaseName,
        string $tableName,
        string $engine,
        string $collation,
        string $charset,
        int $rowCount,
        array $columnMetadataList
    ) {
        $this->databaseName = $databaseName;
        $this->tableName = $tableName;
        $this->engine = $engine;
        $this->collation = $collation;
        $this->rowCount = $rowCount;
        $this->charset = $charset;

        foreach ($columnMetadataList as $columnMetadata) {
            $this->columnMetadataList[$columnMetadata->getColumnName()] = $columnMetadata;
        }
    }

    public function getDatabaseName(): string
    {
        return $this->databaseName;
    }

    public function getTableName(): string
    {
        return $this->tableName;
    }

    public function getEngine(): string
    {
        return $this->engine;
    }

    public function getCollation(): string
    {
        return $this->collation;
    }

    public function getCharset(): string
    {
        return $this->charset;
    }

    public function getRowCount(): int
    {
        return $this->rowCount;
    }

    /**
     * Get the metadata for a single column.
     *
     * @throws \RuntimeException when the column does not exist.
     */
    public function getColumnMetadata(string $columnName): ColumnMetadata
    {
        if (!array_key_exists($columnName, $this->columnMetadataList)) {
            throw new \RuntimeException('Table "' . $this->tableName . '" doesn\'t contain column "' . $columnName . '"');
        }

        return $this->columnMetadataList[$columnName];
    }

    /**
     * Get the metadata for all columns.
     *
     * @return ColumnMetadata[]
     */
    public function getAllColumnMetadata(): array
    {
        return $this->columnMetadataList;
    }

    /**
     * Returns true if the table has at least one column that is a string type.
     */
    public function hasStringTypeColumn(): bool
    {
        $hasStringType = false;
        foreach ($this->columnMetadataList as $columnMetadata) {
            $hasStringType = $hasStringType || $columnMetadata->isStringType();
        }
        return $hasStringType;
    }

    /**
     * Returns the primary key column metadata.
     */
    public function getPrimaryKeyMetadata(): ?ColumnMetadata
    {
        $filteredColumnList = array_filter($this->columnMetadataList, function (ColumnMetadata $columnMetadata) {
            return $columnMetadata->isPrimaryKey();
        });

        return (count($filteredColumnList)) ? current($filteredColumnList) : null;
    }
}