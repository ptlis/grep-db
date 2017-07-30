<?php

namespace ptlis\GrepDb\Search;

use Doctrine\DBAL\Connection;
use ptlis\GrepDb\Metadata\DatabaseMetadata;
use ptlis\GrepDb\Metadata\TableMetadata;
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

    public function searchDatabase(
        DatabaseMetadata $databaseMetadata,
        $searchTerm
    ) {

    }

    /**
     * Return a TableResultGateway through which results can be retrieved.
     *
     * @param TableMetadata $tableMetadata
     * @param string $searchTerm
     * @return TableResultGateway
     */
    public function searchTable(
        TableMetadata $tableMetadata,
        $searchTerm
    ) {
        return new TableResultGateway(
            $this->connection,
            $tableMetadata,
            $searchTerm
        );
    }
}
