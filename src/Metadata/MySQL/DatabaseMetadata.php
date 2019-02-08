<?php declare(strict_types=1);

/**
 * @copyright   (c) 2017-present brian ridley
 * @author      brian ridley <ptlis@ptlis.net>
 * @license     http://opensource.org/licenses/MIT MIT
 */

namespace ptlis\GrepDb\Metadata\MySQL;

/**
 * DTO storing database metadata.
 */
final class DatabaseMetadata
{
    /** @var string */
    private $databaseName;

    /** @var TableMetadata[] */
    private $tableMetadataList = [];


    /**
     * @param string $databaseName
     * @param TableMetadata[] $tableMetadataList
     */
    public function __construct(
        string $databaseName,
        array $tableMetadataList
    ) {
        $this->databaseName = $databaseName;

        foreach ($tableMetadataList as $tableMetadata) {
            $this->tableMetadataList[$tableMetadata->getTableName()] = $tableMetadata;
        }
    }

    public function getDatabaseName(): string
    {
        return $this->databaseName;
    }

    /**
     * Get the metadata for a single table.
     */
    public function getTableMetadata(string $tableName): TableMetadata
    {
        if (!array_key_exists($tableName, $this->tableMetadataList)) {
            throw new \RuntimeException('Database "' . $this->databaseName . '" doesn\'t contain table "' . $tableName . '"');
        }

        return $this->tableMetadataList[$tableName];
    }

    /**
     * Get the metadata for all tables.
     *
     * @return TableMetadata[]
     */
    public function getAllTableMetadata(): array
    {
        return $this->tableMetadataList;
    }
}