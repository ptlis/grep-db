<?php declare(strict_types=1);

/**
 * @copyright   (c) 2017-present brian ridley
 * @author      brian ridley <ptlis@ptlis.net>
 * @license     http://opensource.org/licenses/MIT MIT
 */

namespace ptlis\GrepDb\Metadata\MySQL;

/**
 * DTO storing server metadata.
 */
final class ServerMetadata
{
    /** @var string */
    private $host;

    /** @var DatabaseMetadata[] */
    private $databaseMetadataList;


    /**
     * @param string $host
     * @param DatabaseMetadata[] $databaseMetadataList
     */
    public function __construct(
        string $host,
        array $databaseMetadataList
    ) {
        $this->host = $host;

        foreach ($databaseMetadataList as $databaseMetadata) {
            $this->databaseMetadataList[$databaseMetadata->getDatabaseName()] = $databaseMetadata;
        }
    }

    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * Returns all database metadata.
     *
     * @return DatabaseMetadata[]
     */
    public function getAllDatabaseMetadata(): array
    {
        return $this->databaseMetadataList;
    }

    /**
     * Returns database metadata for the specified database.
     */
    public function getDatabaseMetadata(string $databaseName): DatabaseMetadata
    {
        if (!array_key_exists($databaseName, $this->databaseMetadataList)) {
            throw new \RuntimeException('Server "' . $this->host . '" doesn\'t contain database "' . $databaseName . '"');
        }

        return $this->databaseMetadataList[$databaseName];
    }
}