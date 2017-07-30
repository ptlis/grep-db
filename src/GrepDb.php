<?php

namespace ptlis\GrepDb;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use ptlis\GrepDb\Metadata\DatabaseMetadata;
use ptlis\GrepDb\Metadata\MetadataFactory;
use ptlis\GrepDb\Search\Result\TableResultGateway;
use ptlis\GrepDb\Search\Search;

/**
 * Perform a search or a search & replace on the database.
 */
final class GrepDb
{
    /** @var DatabaseMetadata */
    private $databaseMetadata;

    /** @var Search */
    private $search;


    /**
     * @param string $username
     * @param string $password
     * @param string $host
     * @param string $databaseName
     * @param int $port
     * @throws \PDOException
     */
    public function __construct(
        $username,
        $password,
        $host,
        $databaseName,
        $port = 3306
    ) {
        $connection = DriverManager::getConnection([
            'dbname' => $databaseName,
            'user' => $username,
            'password' => $password,
            'host' => $host,
            'port' => $port,
            'driver' => 'pdo_mysql'
        ]);

        $this->search = new Search($connection);

        $factory = new MetadataFactory();

        $this->databaseMetadata = $factory->buildDatabaseMetadata($connection, $databaseName);
    }


    /**
     * Returns a TableResultGateway through which results can be retrieved.
     *
     * @param string $table
     * @param string $searchTerm
     * @return TableResultGateway
     */
    public function searchTable($table, $searchTerm)
    {
        return $this->search->searchTable($this->databaseMetadata->getTableMetadata($table), $searchTerm);
    }
}
