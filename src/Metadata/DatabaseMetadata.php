<?php

namespace ptlis\GrepDb\Metadata;

/**
 * Simple DTO storing database metadata.
 */
final class DatabaseMetadata
{
    /** @var string */
    private $name;

    /** @var TableMetadata[] */
    private $tableMetadataList = [];


    /**
     * @param string $name
     * @param TableMetadata[] $tableMetadataList
     */
    public function __construct(
        $name,
        array $tableMetadataList
    ) {
        $this->name = $name;
        foreach ($tableMetadataList as $tableMetadata) {
            $this->tableMetadataList[$tableMetadata->getTableName()] = $tableMetadata;
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
     * Get the metadata for a single table.
     *
     * @param string $tableName
     * @return TableMetadata
     */
    public function getTableMetadata($tableName)
    {
        if (!array_key_exists($tableName, $this->tableMetadataList)) {
            throw new \RuntimeException('Database doesn\'t contain table named "' . $tableName . '"');
        }

        return $this->tableMetadataList[$tableName];
    }

    /**
     * Get the metadata for all tables.
     *
     * @return TableMetadata[]
     */
    public function getAllTableMetadata()
    {
        return $this->tableMetadataList;
    }
}
