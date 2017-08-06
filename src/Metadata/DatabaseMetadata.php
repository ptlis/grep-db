<?php

namespace ptlis\GrepDb\Metadata;

use Doctrine\DBAL\Connection;

/**
 * Simple DTO storing database metadata.
 */
final class DatabaseMetadata
{
    /** @var Connection */
    private $connection;

    /** @var MetadataFactory */
    private $factory;

    /** @var string */
    private $name;


    /**
     * @param Connection $connection
     * @param MetadataFactory $factory
     * @param string $name
     */
    public function __construct(
        Connection $connection,
        MetadataFactory $factory,
        $name
    ) {
        $this->connection = $connection;
        $this->factory = $factory;
        $this->name = $name;
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
        return $this->factory->buildTableMetadata($this->connection, $this->name, $tableName);
    }

    /**
     * Get the metadata for all tables.
     *
     * @return TableMetadata[]
     */
    public function getAllTableMetadata()
    {
        $tableMetadataList = [];
        foreach ($this->factory->getTableNames($this->connection, $this->name) as $tableName) {
            $tableMetadataList[] = $this->getTableMetadata($tableName);
        }
        return $tableMetadataList;
    }
}
