<?php

namespace ptlis\GrepDb\Search\Result;

use ptlis\GrepDb\Metadata\ColumnMetadata;

/**
 * Simple DTO representing a single search result.
 */
final class FieldResult
{
    /** @var ColumnMetadata */
    private $column;

    /** @var string */
    private $value;


    /**
     * @param ColumnMetadata $column
     * @param string $value
     */
    public function __construct(
        ColumnMetadata $column,
        $value
    ) {
        $this->column = $column;
        $this->value = $value;
    }

    /**
     * @return ColumnMetadata
     */
    public function getMetadata()
    {
        return $this->column;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }
}
