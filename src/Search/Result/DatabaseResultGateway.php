<?php

namespace ptlis\GrepDb\Search\Result;

use Doctrine\DBAL\Connection;
use ptlis\GrepDb\Metadata\DatabaseMetadata;

/**
 * Gateway used to retrieve search results for a database.
 */
final class DatabaseResultGateway
{
    /** @var Connection */
    private $connection;

    /** @var DatabaseMetadata */
    private $databaseMetadata;

    /** @var string */
    private $searchTerm;

    /** @var string[] */
    private $tableNames;

    /** @var int */
    private $offset;

    /** @var int */
    private $limit;


    /**
     * @param Connection $connection
     * @param DatabaseMetadata $databaseMetadata
     * @param string $searchTerm
     * @param string[] $tableNames
     * @param int $offset
     * @param int $limit
     */
    public function __construct(
        Connection $connection,
        DatabaseMetadata $databaseMetadata,
        $searchTerm,
        array $tableNames,
        $offset = -1,
        $limit = -1
    ) {
        $this->connection = $connection;
        $this->databaseMetadata = $databaseMetadata;
        $this->searchTerm = $searchTerm;
        $this->tableNames = $tableNames;
        $this->offset = $offset;
        $this->limit = $limit;
    }

    /**
     * @return DatabaseMetadata
     */
    public function getMetadata()
    {
        return $this->databaseMetadata;
    }

    /**
     * Returns the search term.
     *
     * @return string
     */
    public function getSearchTerm()
    {
        return $this->searchTerm;
    }

    /**
     * Find tables containing rows matching the search string.
     *
     * @return TableResultGateway[]
     */
    public function getMatchingTables()
    {
        $runningCount = 0;

        $tableResultList = [];
        foreach ($this->tableNames as $tableName) {
            $tableMetadata = $this->databaseMetadata->getTableMetadata($tableName);
            $tableResultGateway = new TableResultGateway($this->connection, $tableMetadata, $this->searchTerm);

            // Call once as we re-use this value several times, and we don't want to hit the db every time.
            $tableCount = $tableResultGateway->getMatchingCount();

            // Do nothing if there were no matches
            if (0 === $tableCount) {
                continue;
            }

            // An offset & limit was specified
            if ($this->offset >= 0 && $this->limit > 1) {
                $rangeStart = $this->offset;
                $rangeEnd = $this->offset + $this->limit;
                $tableOffset = 0;
                $tableLimit = 0;
                $hasResults = false;

                /**
                 * The rows found in the table sit within the bounds specified by the range:
                 *
                 *                      running count     run + table count
                 *                            |                  |
                 * +-----------------------+--+------------------+----+----------------------------+
                 * |                       |                          |                            |
                 * 0                     start                       end                     database total
                 */
                if ($runningCount >= $rangeStart && $runningCount + $tableCount <= $rangeEnd) {
                    $tableOffset = 0;
                    $tableLimit = $tableCount;
                    $hasResults = true;

                /**
                 * The range's start and end sit within a single table's results:
                 *
                 *         running count                                 run + table count
                 *               |                                              |
                 * +-------------+---------+--------------------------+---------+------------------+
                 * |                       |                          |                            |
                 * 0                     start                       end                     database total
                 */
                } else if ($runningCount <= $rangeStart && $runningCount + $tableCount >= $rangeEnd) {
                    $tableOffset = $rangeStart - $runningCount;
                    $tableLimit = $this->limit;
                    $hasResults = true;

                /**
                 * The range start begins part-way through a tables results, but there aren't enough remaining to
                 * exceed the range end:
                 *
                 *         running count         run + table count
                 *               |                      |
                 * +-------------+---------+------------+-------------+----------------------------+
                 * |                       |                          |                            |
                 * 0                     start                       end                     database total
                 */
                } else if ($runningCount <= $rangeStart && $runningCount + $tableCount <= $rangeEnd) {
                    $tableOffset = $rangeStart - $runningCount;
                    $tableLimit = ($runningCount + $tableCount) - $rangeStart;
                    $hasResults = true;

                /**
                 * The running count sits within the range, but the run + table count sit after the end of the range
                 *
                 *                                running count         run + table count
                 *                                      |                      |
                 * +-----------------------+------------+-------------+--------+-------------------+
                 * |                       |                          |                            |
                 * 0                     start                       end                     database total
                 */
                } else if ($runningCount >= $rangeStart && $runningCount + $tableCount >= $rangeEnd) {
                    $tableOffset = 0;
                    $tableLimit = $rangeEnd - $runningCount;
                    $hasResults = true;
                }

                // The table has results within the specified range
                if ($hasResults) {

                    $tableResultList[] = new TableResultGateway(
                        $this->connection,
                        $tableMetadata,
                        $this->searchTerm,
                        $tableOffset,
                        $tableLimit
                    );
                }

                $runningCount += $tableCount;

                // Break early if we've found the required range
                if ($runningCount > $rangeEnd) {
                    break;
                }


            } else {
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
