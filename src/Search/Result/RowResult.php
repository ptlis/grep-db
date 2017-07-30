<?php

namespace ptlis\GrepDb\Search\Result;

use ptlis\GrepDb\Metadata\ColumnMetadata;

/**
 * Simple DTO representing a single search result.
 */
final class RowResult
{
    /** @var array|ColumnResult[] */
    private $matchingColumnList = [];

    /** @var null|ColumnMetadata */
    private $primaryKeyColumn = null;

    /** @var int|null */
    private $primaryKeyValue;


    /**
     * @param ColumnResult[] $matchingColumnList
     * @param ColumnMetadata|null $primaryKeyColumn
     * @param int|null $primaryKeyValue
     */
    public function __construct(
        array $matchingColumnList,
        ColumnMetadata $primaryKeyColumn = null,
        $primaryKeyValue = null
    ) {
        foreach ($matchingColumnList as $matchingColumn) {
            $this->matchingColumnList[$matchingColumn->getColumnMetadata()->getName()] = $matchingColumn;
        }
        $this->primaryKeyColumn = $primaryKeyColumn;
        $this->primaryKeyValue = $primaryKeyValue;
    }

    /**
     * Get array of matching columns in this row.
     *
     * @return ColumnResult[]
     */
    public function getMatchingColumns()
    {
        return $this->matchingColumnList;
    }

    /**
     * Returns true if the column result exists.
     *
     * @param string $columnName
     * @return bool
     */
    public function hasColumnResult($columnName)
    {
        return array_key_exists($columnName, $this->matchingColumnList);
    }

    /**
     * Returns the column result matching the passed name.
     *
     * @param string $columnName
     * @return ColumnResult
     */
    public function getColumnResult($columnName)
    {
        if (!$this->hasColumnResult($columnName)) {
            throw new \RuntimeException('Could not find changed column named "' . $columnName . '"');
        }

        return $this->matchingColumnList[$columnName];
    }

    /**
     * Returns true if the row has a primary key.
     *
     * @return bool
     */
    public function hasPrimaryKey()
    {
        return !is_null($this->primaryKeyColumn);
    }

    /**
     * Returns the primary key column's metadata.
     *
     * @return null|ColumnMetadata
     * @throws \RuntimeException If the result doesn't have a primary key
     */
    public function getPrimaryKeyColumn()
    {
        if (!$this->hasPrimaryKey()) {
            throw new \RuntimeException('Search result row doesn\'t have a primary key');
        }

        return $this->primaryKeyColumn;
    }

    /**
     * Returns the primary key value.
     *
     * @return int|null
     * @throws \RuntimeException If the result doesn't have a primary key
     */
    public function getPrimaryKeyValue()
    {
        if (!$this->hasPrimaryKey()) {
            throw new \RuntimeException('Search result row doesn\'t have a primary key');
        }

        return $this->primaryKeyValue;
    }
}
