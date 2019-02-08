<?php declare(strict_types=1);

/**
 * @copyright   (c) 2017-present brian ridley
 * @author      brian ridley <ptlis@ptlis.net>
 * @license     http://opensource.org/licenses/MIT MIT
 */

namespace ptlis\GrepDb\Search;

use Doctrine\DBAL\Connection;
use ptlis\GrepDb\Metadata\MySQL\MetadataFactory;
use ptlis\GrepDb\Search\Result\RowSearchResult;
use ptlis\GrepDb\Search\Strategy\StringMatchTableSearchStrategy;

/**
 * Provides search.
 */
final class Search
{
    /**
     * Performs a search across databases on a server, batching queries to the specified batch size.
     *
     * @param Connection $connection
     * @param string $searchTerm
     * @return \Generator|RowSearchResult[]
     */
    public function searchServer(
        Connection $connection,
        string $searchTerm
    ): \Generator {
        $serverMetadata = (new MetadataFactory())->getServerMetadata($connection);

        foreach ($serverMetadata->getAllDatabaseMetadata() as $databaseMetadata) {
            $resultList = $this->searchDatabase(
                $connection,
                $databaseMetadata->getDatabaseName(),
                $searchTerm
            );

            foreach ($resultList as $result) {
                yield $result;
            }
        }
    }

    /**
     * Performs a search across tables in a database, batching queries to the specified batch size.
     *
     * @param Connection $connection
     * @param string $databaseName
     * @param string $searchTerm
     * @return \Generator|RowSearchResult[]
     */
    public function searchDatabase(
        Connection $connection,
        string $databaseName,
        string $searchTerm
    ): \Generator {
        $databaseMetadata = (new MetadataFactory())->getDatabaseMetadata($connection, $databaseName);

        foreach ($databaseMetadata->getAllTableMetadata() as $tableMetadata) {
            $resultList = $this->searchTable(
                $connection,
                $tableMetadata->getDatabaseName(),
                $tableMetadata->getTableName(),
                $searchTerm
            );

            foreach ($resultList as $result) {
                yield $result;
            }
        }
    }

    /**
     * Performs a search on the provided table, batching queries to the specified batch size.
     *
     * @param Connection $connection
     * @param string $databaseName
     * @param string $tableName
     * @param string $searchTerm
     * @return \Generator|RowSearchResult[]
     */
    public function searchTable(
        Connection $connection,
        string $databaseName,
        string $tableName,
        string $searchTerm
    ): \Generator {
        $tableMetadata = (new MetadataFactory())->getTableMetadata($connection, $databaseName, $tableName);

        if (!$tableMetadata->hasStringTypeColumn()) {
            return;
        }

        $rowResultList = (new StringMatchTableSearchStrategy())->getMatches(
            $connection,
            $tableMetadata,
            $searchTerm
        );

        // Yield batch tracking rows returned
        foreach ($rowResultList as $rowResult) {
            yield $rowResult;
        }
    }
}