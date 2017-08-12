<?php

namespace ptlis\GrepDb\Replace\Result;

use ptlis\GrepDb\Metadata\TableMetadata;

/**
 * Result of table replacements.
 */
final class TableReplaceResult
{
    /** @var TableMetadata */
    private $metadata;

    /** @var int */
    private $rowsReplacedCount;

    /** @var int */
    private $columnsReplacedCount;

    /** @var string[] */
    private $errorList;

    /** @var bool */
    private $complete;


    /**
     * @param TableMetadata $metadata
     * @param int $rowsReplacedCount
     * @param int $columnsReplacedCount
     * @param string[] $errorList
     * @param bool $complete
     */
    public function __construct(
        TableMetadata $metadata,
        $rowsReplacedCount,
        $columnsReplacedCount,
        array $errorList,
        $complete
    ) {
        $this->metadata = $metadata;
        $this->rowsReplacedCount = $rowsReplacedCount;
        $this->columnsReplacedCount = $columnsReplacedCount;
        $this->errorList = $errorList;
        $this->complete = $complete;
    }

    /**
     * @return TableMetadata
     */
    public function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * @return int
     */
    public function getRowsReplacedCount()
    {
        return $this->rowsReplacedCount;
    }

    /**
     * @return int
     */
    public function getColumnsReplacedCount()
    {
        return $this->columnsReplacedCount;
    }

    /**
     * @return string[]
     */
    public function getErrorList()
    {
        return $this->errorList;
    }

    /**
     * @return bool
     */
    public function isComplete()
    {
        return $this->complete;
    }
}
