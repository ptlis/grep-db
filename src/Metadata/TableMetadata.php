<?php

namespace ptlis\GrepDb\Metadata;

/**
 * Simple DTO storing table metadata.
 */
final class TableMetadata
{
    /** @var string */
    private $name;

    /** @var string */
    private $engine;

    /** @var string */
    private $collation;

    /** @var int */
    private $rowCount;

    /** @var string */
    private $charset;

    /** @var ColumnMetadata[] */
    private $columnMetadataList = [];


    /**
     * @param string $name
     * @param string $engine
     * @param string $collation
     * @param int $rowCount
     * @param string $charset
     * @param ColumnMetadata[] $columnMetadataList
     */
    public function __construct(
        $name,
        $engine,
        $collation,
        $rowCount,
        $charset,
        array $columnMetadataList
    ) {
        $this->name = $name;
        $this->engine = $engine;
        $this->collation = $collation;
        $this->rowCount = $rowCount;
        $this->charset = $charset;

        foreach ($columnMetadataList as $columnMetadata) {
            $this->columnMetadataList[$columnMetadata->getName()] = $columnMetadata;
        }
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getEngine()
    {
        return $this->engine;
    }

    /**
     * @return string
     */
    public function getCollation()
    {
        return $this->collation;
    }

    /**
     * @return int
     */
    public function getRowCount()
    {
        return $this->rowCount;
    }

    /**
     * @return string
     */
    public function getCharset()
    {
        return $this->charset;
    }

    /**
     * Get the metadata for a single column.
     *
     * @param string $columnName
     * @return ColumnMetadata
     */
    public function getColumnMetadata($columnName)
    {
        if (!array_key_exists($columnName, $this->columnMetadataList)) {
            throw new \RuntimeException('Table "' . $this->name . '" doesn\'t contain column named "' . $columnName . '"');
        }

        return $this->columnMetadataList[$columnName];
    }

    /**
     * Get the metadata for all columns.
     *
     * @return ColumnMetadata[]
     */
    public function getAllColumnMetadata()
    {
        return $this->columnMetadataList;
    }

    /**
     * Returns true if the table has at least one column that is a string type.
     *
     * @return bool
     */
    public function hasStringTypeColumn()
    {
        $hasStringType = false;
        foreach ($this->columnMetadataList as $columnMetadata) {
            $hasStringType = $hasStringType || $columnMetadata->isStringType();
        }
        return $hasStringType;
    }

    /**
     * Returns true if the table has a primary key.
     *
     * @return bool
     */
    public function hasPrimaryKey()
    {
        $hasPrimaryKey = false;
        foreach ($this->columnMetadataList as $columnMetadata) {
            $hasPrimaryKey = $hasPrimaryKey || $columnMetadata->isPrimaryKey();
        }
        return $hasPrimaryKey;
    }

    /**
     * Returns the primary key column metadata.
     *
     * @return ColumnMetadata
     * @throws \RuntimeException
     */
    public function getPrimaryKey()
    {
        if (!$this->hasPrimaryKey()) {
            throw new \RuntimeException('No primary key exists on ' . $this->name);
        }

        $primaryKey = null;
        foreach ($this->columnMetadataList as $columnMetadata) {
            if ($columnMetadata->isPrimaryKey()) {
                $primaryKey = $columnMetadata;
                break;
            }
        }
        return $primaryKey;
    }
}
