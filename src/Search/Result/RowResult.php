<?php

namespace ptlis\GrepDb\Search\Result;

use ptlis\GrepDb\Metadata\ColumnMetadata;

/**
 * Simple DTO representing a single search result.
 */
final class RowResult
{
    /** @var array|ColumnResult[] */
    private $matchingColumnList;

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
        $this->matchingColumnList = $matchingColumnList;
        $this->primaryKeyColumn = $primaryKeyColumn;
        $this->primaryKeyValue = $primaryKeyValue;
    }

    /**
     * Get array of matching columns in this row.
     *
     * @return ColumnResult[]
     */
    public function getMatchingColumnList()
    {
        return $this->matchingColumnList;
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
