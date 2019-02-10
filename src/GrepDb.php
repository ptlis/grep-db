<?php declare(strict_types=1);

/**
 * @copyright   (c) 2017-present brian ridley
 * @author      brian ridley <ptlis@ptlis.net>
 * @license     http://opensource.org/licenses/MIT MIT
 */

namespace ptlis\GrepDb;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use ptlis\GrepDb\Replace\Replace;
use ptlis\GrepDb\Replace\Result\RowReplaceResult;
use ptlis\GrepDb\Search\Result\RowSearchResult;
use ptlis\GrepDb\Search\Search;

/**
 * Perform a search or a search & replace on the database.
 */
final class GrepDb
{
    /** @var Connection */
    private $connection;

    /** @var Search */
    private $search;

    /** @var Replace */
    private $replace;


    public function __construct(
        string $username,
        string $password,
        string $host,
        int $port = 3306
    ) {
        $this->connection = DriverManager::getConnection([
            'user' => $username,
            'password' => $password,
            'host' => $host,
            'port' => $port,
            'driver' => 'pdo_mysql'
        ]);
        $this->search = new Search();
        $this->replace = new Replace();
    }

    /**
     * Performs search on the specified database and table.
     *
     * @param string $databaseName
     * @param string $tableName
     * @param string $searchTerm
     * @return \Generator|RowSearchResult[]
     */
    public function searchTable(
        string $databaseName,
        string $tableName,
        string $searchTerm
    ): \Generator {
        $resultList = $this->search->searchTable($this->connection, $databaseName, $tableName, $searchTerm);
        foreach ($resultList as $result) {
            yield $result;
        }
    }

    /**
     * Performs search on all tables in the specified database.
     *
     * @param string $databaseName
     * @param string $searchTerm
     * @return \Generator|RowSearchResult[]
     */
    public function searchDatabase(
        string $databaseName,
        string $searchTerm
    ): \Generator {
        $resultList = $this->search->searchDatabase($this->connection, $databaseName, $searchTerm);
        foreach ($resultList as $result) {
            yield $result;
        }
    }

    /**
     * Performs search on all available databases.
     *
     * @param string $searchTerm
     * @return \Generator|RowSearchResult[]
     */
    public function searchServer(
        string $searchTerm
    ): \Generator {
        $resultList = $this->search->searchServer($this->connection, $searchTerm);
        foreach ($resultList as $result) {
            yield $result;
        }
    }

    /**
     * Performs search and replacement on the specified database and table.
     *
     * @param string $databaseName
     * @param string $tableName
     * @param string $searchTerm
     * @param string $replaceTerm
     * @return \Generator|RowReplaceResult[]
     */
    public function replaceTable(
        string $databaseName,
        string $tableName,
        string $searchTerm,
        string $replaceTerm
    ): \Generator {
        $resultList = $this->replace->replaceTable(
            $this->connection,
            $databaseName,
            $tableName,
            $searchTerm,
            $replaceTerm
        );
        foreach ($resultList as $result) {
            yield $result;
        }
    }

    /**
     * Performs search and replacement on all tables in the specified database.
     *
     * @param string $databaseName
     * @param string $searchTerm
     * @param string $replaceTerm
     * @return \Generator|RowReplaceResult[]
     */
    public function replaceDatabase(
        string $databaseName,
        string $searchTerm,
        string $replaceTerm
    ): \Generator {
        $resultList = $this->replace->replaceDatabase($this->connection, $databaseName, $searchTerm, $replaceTerm);
        foreach ($resultList as $result) {
            yield $result;
        }
    }
}