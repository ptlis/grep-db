<?php

namespace ptlis\GrepDb;

use Doctrine\DBAL\DriverManager;
use ptlis\GrepDb\Metadata\MetadataFactory;
use ptlis\GrepDb\Metadata\ServerMetadata;
use ptlis\GrepDb\Replace\Replace;
use ptlis\GrepDb\Replace\Result\DatabaseReplaceResult;
use ptlis\GrepDb\Replace\Result\TableReplaceResult;
use ptlis\GrepDb\Search\Result\DatabaseResultGateway;
use ptlis\GrepDb\Search\Result\TableResultGateway;
use ptlis\GrepDb\Search\Search;

/**
 * Perform a search or a search & replace on the database.
 */
final class GrepDb
{
    /** @var ServerMetadata */
    private $serverMetadata;

    /** @var Search */
    private $search;

    /** @var Replace */
    private $replace;


    /**
     * @param string $username
     * @param string $password
     * @param string $host
     * @param int $port
     * @throws \PDOException
     */
    public function __construct(
        $username,
        $password,
        $host,
        $port = 3306
    ) {
        $connection = DriverManager::getConnection([
            'user' => $username,
            'password' => $password,
            'host' => $host,
            'port' => $port,
            'driver' => 'pdo_mysql'
        ]);

        $this->search = new Search($connection);
        $this->replace = new Replace($connection);

        $this->serverMetadata = (new MetadataFactory())->buildServerMetadata($host, $connection);
    }

    /**
     * @return ServerMetadata
     */
    public function getServerMetadata()
    {
        return $this->serverMetadata;
    }

    /**
     * Returns a TableResultGateway through which results can be retrieved.
     *
     * @param string $databaseName
     * @param string $tableName
     * @param string $searchTerm
     * @param int $offset
     * @param int $limit
     * @return TableResultGateway
     */
    public function searchTable(
        $databaseName,
        $tableName,
        $searchTerm,
        $offset = -1,
        $limit = -1
    ) {
        $databaseMetadata = $this->serverMetadata->getDatabaseMetadata($databaseName);
        return $this->search->searchTable($databaseMetadata->getTableMetadata($tableName), $searchTerm, $offset, $limit);
    }

    /**
     * Returns a DatabaseResultsGateway through which results can be retrieved.
     *
     * @param string $databaseName
     * @param string $searchTerm
     * @param string[] $tableNames
     * @param int $offset
     * @param int $limit
     * @return DatabaseResultGateway
     */
    public function searchDatabase(
        $databaseName,
        $searchTerm,
        array $tableNames = [],
        $offset = -1,
        $limit = -1
    ) {
        $databaseMetadata = $this->serverMetadata->getDatabaseMetadata($databaseName);

        // If no table names were specified then try to search all tables
        if (!count($tableNames)) {
            foreach ($databaseMetadata->getTableNames() as $tableName) {
                if ($databaseMetadata->getTableMetadata($tableName)->hasStringTypeColumn()) {
                    $tableNames[] = $tableName;
                }
            }
        }
        return $this->search->searchDatabase($databaseMetadata, $searchTerm, $tableNames, $offset, $limit);
    }

    /**
     * Performs a search and replace on the specified table.
     *
     * @param string $databaseName
     * @param string $tableName
     * @param string $searchTerm
     * @param string $replaceTerm
     * @param bool $incrementalReturn Set to true to get intermediate values via generator
     * @return \Generator|TableReplaceResult[]
     */
    public function replaceTable($databaseName, $tableName, $searchTerm, $replaceTerm, $incrementalReturn = false)
    {
        $tableResultGateway = $this->searchTable($databaseName, $tableName, $searchTerm);
        return $this->replace->replaceTable($tableResultGateway, $replaceTerm, $incrementalReturn);
    }

    /**
     * Performs a search and replace on all tables in the database.
     *
     * @param string $databaseName
     * @param string $searchTerm
     * @param string $replaceTerm
     * @param bool $incrementalReturn Set to true to get intermediate values via generator
     * @return \Generator|DatabaseReplaceResult[]
     */
    public function replaceDatabase($databaseName, $searchTerm, $replaceTerm, $incrementalReturn = false)
    {
        $databaseResultGateway = $this->searchDatabase($databaseName, $searchTerm);
        return $this->replace->replaceDatabase($databaseResultGateway, $replaceTerm, $incrementalReturn);
    }
}
