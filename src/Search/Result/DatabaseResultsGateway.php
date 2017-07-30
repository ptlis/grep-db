<?php

namespace ptlis\GrepDb\Search\Result;

use Doctrine\DBAL\Connection;
use ptlis\GrepDb\Metadata\DatabaseMetadata;

/**
 * Gateway used to retrieve search results for a database.
 */
final class DatabaseResultsGateway
{
    /** @var Connection */
    private $connection;

    /** @var DatabaseMetadata */
    private $databaseMetadata;

    /** @var string */
    private $searchTerm;

    /** @var int */
    private $batchSize;


    /**
     * @param Connection $connection
     * @param DatabaseMetadata $databaseMetadata
     * @param string $searchTerm
     * @param int $batchSize
     */
    public function __construct(
        Connection $connection,
        DatabaseMetadata $databaseMetadata,
        $searchTerm,
        $batchSize = 1000
    ) {
        $this->connection = $connection;
        $this->databaseMetadata = $databaseMetadata;
        $this->searchTerm = $searchTerm;
        $this->batchSize = $batchSize;
    }

    /**
     * @return DatabaseMetadata
     */
    public function getDatabaseMetadata()
    {
        return $this->databaseMetadata;
    }

    /**
     * Find tables containing rows matching the search string.
     *
     * @return TableResultGateway[]
     */
    public function getMatchingTables()
    {
        $tableResultList = [];
        foreach ($this->databaseMetadata->getAllTableMetadata() as $tableMetadata) {
            $tableResultGateway = new TableResultGateway(
                $this->connection,
                $tableMetadata,
                $this->searchTerm,
                $this->batchSize
            );

            // Only return table result gateway if a match is found
            if (
                $tableMetadata->hasStringTypeColumn()
                && $tableResultGateway->getMatchingCount() > 0
            ) {
                $tableResultList[] = $tableResultGateway;
            }
        }

        return $tableResultList;
    }

    /**
     * Get the number of tables with rows matching the search term.
     *
     * @return int
     */
    public function getMatchingTableCount()
    {
        return count($this->getMatchingTables());
    }

    /**
     * Get the total number of rows across all tables matching the search term.
     *
     * @return int
     */
    public function getMatchingRowCount()
    {
        $count = 0;
        foreach ($this->getMatchingTables() as $tableResultGateway) {
            $count += $tableResultGateway->getMatchingCount();
        }
        return $count;
    }
}
