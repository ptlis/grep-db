<?php

namespace ptlis\GrepDb\Search;

use Doctrine\DBAL\Connection;
use ptlis\GrepDb\Metadata\DatabaseMetadata;
use ptlis\GrepDb\Metadata\TableMetadata;
use ptlis\GrepDb\Search\Result\DatabaseResultGateway;
use ptlis\GrepDb\Search\Result\TableResultGateway;

/**
 * Class through which database searching is executed.
 */
final class Search
{
    /** @var Connection */
    private $connection;


    /**
     * @param Connection $connection
     */
    public function __construct(
        Connection $connection
    ) {
        $this->connection = $connection;
    }

    /**
     * Returns a DatabaseResultGateway through which results can be retrieved.
     *
     * @param DatabaseMetadata $databaseMetadata
     * @param string $searchTerm
     * @param string[] $tableNames
     * @param int $offset
     * @param int $limit
     * @return DatabaseResultGateway
     */
    public function searchDatabase(
        DatabaseMetadata $databaseMetadata,
        $searchTerm,
        array $tableNames = [],
        $offset = -1,
        $limit = -1
    ) {
        return new DatabaseResultGateway($this->connection, $databaseMetadata, $searchTerm, $tableNames, $offset, $limit);
    }

    /**
     * Returns a TableResultGateway through which results can be retrieved.
     *
     * @param TableMetadata $tableMetadata
     * @param string $searchTerm
     * @param int $offset
     * @param int $limit
     * @return TableResultGateway
     */
    public function searchTable(
        TableMetadata $tableMetadata,
        $searchTerm,
        $offset = -1,
        $limit = -1
    ) {
        return new TableResultGateway($this->connection, $tableMetadata, $searchTerm, $offset, $limit);
    }
}
