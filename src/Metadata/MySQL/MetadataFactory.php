<?php declare(strict_types=1);

/**
 * @copyright   (c) 2017-present brian ridley
 * @author      brian ridley <ptlis@ptlis.net>
 * @license     http://opensource.org/licenses/MIT MIT
 */

namespace ptlis\GrepDb\Metadata\MySQL;

use Doctrine\DBAL\Connection;

/**
 * Factory that builds server, database & table metadata.
 */
final class MetadataFactory
{
    /**
     * Query the server and build server metadata DTO.
     */
    public function getServerMetadata(
        Connection $connection
    ) {
        // Attempt to list databases, ignoring internal databases
        try {
            $statement = $connection->query('SHOW DATABASES WHERE `Database` NOT IN ("information_schema", "performance_schema", "sys", "mysql");');
        } catch (\Throwable $e) {
            throw new \RuntimeException('Failed to list databases: ' . $e->getMessage());
        }

        $databaseMetadataList = [];
        /** @var string $databaseName */
        while ($databaseName = $statement->fetchColumn(0)) {
            $databaseMetadataList[] = $this->getDatabaseMetadata($connection, $databaseName);
        }

        return new ServerMetadata($connection->getHost(), $databaseMetadataList);
    }

    /**
     * Query the server and build database metadata DTO.
     */
    public function getDatabaseMetadata(
        Connection $connection,
        string $databaseName
    ): DatabaseMetadata {
        // Get a list of table names
        $tableNameStatement = $connection
            ->createQueryBuilder()
            ->select([
                'tables.TABLE_NAME AS name'
            ])
            ->from('information_schema.TABLES', 'tables')
            ->where('TABLE_SCHEMA = :schema')
            ->andWhere('TABLE_TYPE = "BASE TABLE"')
            ->setParameter('schema', $databaseName)
            ->execute();

        // Build table metadata
        $tableMetadataList = [];
        while ($tableName = $tableNameStatement->fetchColumn(0)) {
            $tableMetadataList[] = $this->getTableMetadata($connection, $databaseName, $tableName);
        }

        return new DatabaseMetadata($databaseName, $tableMetadataList);
    }

    /**
     * Query the server and build table metadata DTO.
     */
    public function getTableMetadata(
        Connection $connection,
        string $databaseName,
        string $tableName
    ): TableMetadata {
        // Get top-level table information
        $tableStatement = $connection
            ->createQueryBuilder()
            ->select([
                'tables.TABLE_NAME AS name',
                'tables.ENGINE AS engine',
                'tables.TABLE_COLLATION AS collation',
                'tables.TABLE_ROWS AS row_count',
                'charset.CHARACTER_SET_NAME AS charset'
            ])
            ->from('information_schema.TABLES', 'tables')
            ->leftJoin(
                'tables',
                'information_schema.COLLATION_CHARACTER_SET_APPLICABILITY',
                'charset',
                'tables.TABLE_COLLATION = charset.COLLATION_NAME'
            )
            ->where('TABLE_NAME = :table_name')
            ->setParameter('table_name', $tableName)
            ->execute();

        $tableRow = $tableStatement->fetch(\PDO::FETCH_ASSOC);

        // Get column information
        $columnsStatement = $connection
            ->createQueryBuilder()
            ->select([
                'columns.COLUMN_NAME AS name',
                'columns.COLUMN_TYPE AS type',
                'columns.CHARACTER_MAXIMUM_LENGTH AS max_length',
                '"PRI" = columns.COLUMN_KEY AS is_primary_key',
                '"YES" = columns.IS_NULLABLE AS is_nullable',
                '(
                    SELECT COUNT(*) 
                    FROM   INFORMATION_SCHEMA.STATISTICS 
                    WHERE  TABLE_SCHEMA = :schema 
                    AND    TABLE_NAME = :table_name 
                    AND    COLUMN_NAME = columns.COLUMN_NAME
                ) AS is_indexed'
            ])
            ->from('information_schema.COLUMNS', 'columns')
            ->where('TABLE_SCHEMA = :schema')
            ->andWhere('TABLE_NAME = :table_name')
            ->setParameters([
                'schema' => $databaseName,
                'table_name' => $tableName
            ])
            ->execute();

        // Build column metadata
        $columnMetadataList = [];
        foreach ($columnsStatement->fetchAll(\PDO::FETCH_ASSOC) as $columnsRow) {
            $columnMetadataList[] = new ColumnMetadata(
                $databaseName,
                $tableName,
                $columnsRow['name'],
                $columnsRow['type'],
                is_null($columnsRow['max_length']) ? null : intval($columnsRow['max_length']),
                boolval($columnsRow['is_primary_key']),
                boolval($columnsRow['is_nullable']),
                boolval($columnsRow['is_indexed'])
            );
        }

        return new TableMetadata(
            $databaseName,
            $tableName,
            $tableRow['engine'],
            $tableRow['collation'],
            $tableRow['charset'],
            intval($tableRow['row_count']),
            $columnMetadataList
        );
    }
}