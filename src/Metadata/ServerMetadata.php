<?php

namespace ptlis\GrepDb\Metadata;

use Doctrine\DBAL\Connection;

/**
 * DTO storing RDBMS server metadata.
 */
final class ServerMetadata
{
    /** @var Connection */
    private $connection;

    /** @var MetadataFactory */
    private $factory;

    /** @var string */
    private $host;


    /**
     * @param Connection $connection
     * @param string $host
     * @param MetadataFactory $factory
     */
    public function __construct(
        Connection $connection,
        MetadataFactory $factory,
        $host
    ) {
        $this->connection = $connection;
        $this->factory = $factory;
        $this->host = $host;
    }

    /**
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * Return an array of database names.
     *
     * @return string[]
     */
    public function getDatabaseNames()
    {
        return $this->factory->getDatabaseNames($this->connection);
    }

    /**
     * Returns all database metadata.
     *
     * @return DatabaseMetadata[]
     */
    public function getAllDatabaseMetadata()
    {
        $databaseMetadataList = [];
        foreach ($this->getDatabaseNames() as $databaseName) {
            $databaseMetadataList[] = $this->getDatabaseMetadata($databaseName);
        }
        return $databaseMetadataList;
    }

    /**
     * Returns database metadata for the specified database.
     *
     * @param string $databaseName
     * @return DatabaseMetadata
     */
    public function getDatabaseMetadata($databaseName)
    {
        return $this->factory->buildDatabaseMetadata($this->connection, $databaseName);
    }
}
