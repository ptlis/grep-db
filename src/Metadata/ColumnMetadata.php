<?php

namespace ptlis\GrepDb\Metadata;

/**
 * Simple DTO storing column metadata.
 */
final class ColumnMetadata
{
    /** @var string */
    private $name;

    /** @var string */
    private $type;

    /** @var int */
    private $maxLength;

    /** @var bool */
    private $primaryKey;


    /**
     * @param string $name
     * @param string $type
     * @param int $maxLength
     * @param bool $primaryKey
     */
    public function __construct(
        $name,
        $type,
        $maxLength,
        $primaryKey
    ) {
        $this->name = $name;
        $this->type = $type;
        $this->maxLength = $maxLength;
        $this->primaryKey = $primaryKey;
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
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return int
     */
    public function getMaxLength()
    {
        return $this->maxLength;
    }

    /**
     * @return bool
     */
    public function isPrimaryKey()
    {
        return $this->primaryKey;
    }

    /**
     * Returns true if the column is a string type.
     *
     * List validated against https://dev.mysql.com/doc/refman/5.7/en/string-types.html
     *
     * @return bool
     */
    public function isStringType()
    {
        return in_array(
            strtolower($this->type),
            [
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
            ]
        );
    }
}
