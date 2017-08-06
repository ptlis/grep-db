<?php

namespace ptlis\GrepDb\Metadata;

/**
 * DTO storing RDBMS server metadata.
 */
final class ServerMetadata
{
    /** @var string */
    private $host;

    /** @var DatabaseMetadata[] */
    private $databaseMetadataList = [];


    /**
     * @param string $host
     * @param DatabaseMetadata[] $databaseMetadataList
     */
    public function __construct(
        $host,
        array $databaseMetadataList
    ) {
        $this->host = $host;
        foreach ($databaseMetadataList as $databaseMetadata) {
            $this->databaseMetadataList[$databaseMetadata->getName()] = $databaseMetadata;
        }
    }

    /**
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * Returns all database metadata.
     *
     * @return DatabaseMetadata[]
     */
    public function getAllDatabaseMetadata()
    {
        return $this->databaseMetadataList;
    }

    /**
     * Returns database metadata for the specified database.
     *
     * @param string $databaseName
     * @return DatabaseMetadata
     */
    public function getDatabaseMetadata($databaseName)
    {
        if (!array_key_exists($databaseName, $this->databaseMetadataList)) {
            throw new \RuntimeException('RDBMS Server at "' . $this->host . '" doesn\'t contain database named "' . $databaseName . '"');
        }
        return $this->databaseMetadataList[$databaseName];
    }
}
