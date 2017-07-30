<?php

namespace ptlis\GrepDb;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use ptlis\GrepDb\Metadata\DatabaseMetadata;
use ptlis\GrepDb\Metadata\MetadataFactory;

/**
 * Perform a search or a search & replace on the database.
 */
final class GrepDb
{
    /** @var Connection */
    private $connection;

    /** @var DatabaseMetadata */
    private $databaseMetadata;


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
        $this->connection = DriverManager::getConnection([
            'dbname' => $databaseName,
            'user' => $username,
            'password' => $password,
            'host' => $host,
            'port' => $port,
            'driver' => 'pdo_mysql'
        ]);

        $factory = new MetadataFactory();

        $this->databaseMetadata = $factory->buildDatabaseMetadata($this->connection, $databaseName);

        foreach ($this->databaseMetadata->getAllTableMetadata() as $tableMetadata) {
            if ($tableMetadata->hasStringTypeColumn()) {
                echo $tableMetadata->getName() . ':' . PHP_EOL;

                foreach ($tableMetadata->getAllColumnMetadata() as $columnMetadata) {
                    if ($columnMetadata->isStringType()) {
                        echo '  txt: ' . $columnMetadata->getName() . PHP_EOL;
                    } else if ($columnMetadata->isPrimaryKey()) {
                        echo '  pk:  ' . $columnMetadata->getName() . PHP_EOL;
                    }
                }
            }
        }

    }
}
