<?php

namespace ptlis\GrepDb\Metadata;

/**
 * Simple DTO storing database metadata.
 */
final class DatabaseMetadata
{
    /** @var TableMetadata[] */
    private $tableMetadataList = [];


    /**
     * @param TableMetadata[] $tableMetadataList
     */
    public function __construct(
        array $tableMetadataList
    ) {
        foreach ($tableMetadataList as $tableMetadata) {
            $this->tableMetadataList[$tableMetadata->getName()] = $tableMetadata;
        }
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
