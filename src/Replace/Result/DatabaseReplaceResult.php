<?php

namespace ptlis\GrepDb\Replace\Result;

use ptlis\GrepDb\Metadata\DatabaseMetadata;

/**
 * Result of database replacements.
 */
final class DatabaseReplaceResult
{
    /** @var DatabaseMetadata */
    private $metadata;

    /** @var TableReplaceResult[] */
    private $tableResultList;

    /** @var bool */
    private $complete;


    /**
     * @param DatabaseMetadata $metadata
     * @param TableReplaceResult[] $tableResultList
     * @param bool $complete
     */
    public function __construct(
        DatabaseMetadata $metadata,
        array $tableResultList,
        $complete
    ) {
        $this->metadata = $metadata;
        $this->tableResultList = $tableResultList;
        $this->complete = $complete;
    }

    /**
     * @return DatabaseMetadata
     */
    public function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * @return TableReplaceResult[]
     */
    public function getTableResultList()
    {
        return $this->tableResultList;
    }

    /**
     * @return bool
     */
    public function isComplete()
    {
        return $this->complete;
    }

    /**
     * @return int
     */
    public function getTableCount()
    {
        return count($this->tableResultList);
    }

    /**
     * @return int
     */
    public function getRowsReplacedCount()
    {
        return array_reduce(
            $this->tableResultList,
            function ($currentCount, TableReplaceResult $tableResult) {
                return $currentCount + $tableResult->getRowsReplacedCount();
            },
            0
        );
    }

    /**
     * @return string[]
     */
    public function getErrorList()
    {
        $errorList = [];
        foreach ($this->tableResultList as $tableReplaceResult) {
            $errorList = array_merge($errorList, $tableReplaceResult->getErrorList());
        }
        return $errorList;
    }
}
