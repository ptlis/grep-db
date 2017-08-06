<?php

namespace ptlis\GrepDb\Metadata;

use Doctrine\DBAL\Connection;

/**
 * Factory that builds server, database & table metadata.
 */
final class MetadataFactory
{
    /**
     * @param string $host
     * @param Connection $connection
     * @return ServerMetadata
     */
    public function buildServerMetadata(
        $host,
        Connection $connection
    ) {
        // Internal mySQL database to ignore
        $excludeDatabases = [
            'information_schema',
            'performance_schema',
            'sys',
            'mysql'
        ];

        $databaseMetadataList = [];
        $statement = $connection->query('SHOW DATABASES');
        while ($databaseName = $statement->fetchColumn(0)) {
            if (!in_array($databaseName, $excludeDatabases)) {
                $databaseMetadataList[] = $this->buildDatabaseMetadata($connection, $databaseName);
            }
        }

        return new ServerMetadata($host, $databaseMetadataList);
    }

    /**
     * @param Connection $connection
     * @param string $databaseName
     * @return DatabaseMetadata
     */
    public function buildDatabaseMetadata(
        Connection $connection,
        $databaseName
    ) {
        // Get table names
        $tableStatement = $connection
            ->createQueryBuilder()
            ->select([
                'tables.TABLE_NAME AS name'
            ])
            ->from('information_schema.TABLES', 'tables')
            ->execute();

        $tableList = [];
        foreach ($tableStatement->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $tableList[] = $this->buildTableMetadata($connection, $databaseName, $row['name']);
        }

        return new DatabaseMetadata($databaseName, $tableList);
    }

    /**
     * @param Connection $connection
     * @param string $databaseName
     * @param string $tableName
     * @return TableMetadata
     */
    public function buildTableMetadata(
        Connection $connection,
        $databaseName,
        $tableName
    ) {
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
                'columns.DATA_TYPE AS type',
                'columns.COLUMN_KEY AS column_key',
                'columns.CHARACTER_MAXIMUM_LENGTH AS max_length'
            ])
            ->from('information_schema.COLUMNS', 'columns')
            ->where('TABLE_SCHEMA = :schema')
            ->andWhere('TABLE_NAME = :table_name')
            ->setParameters([
                'schema' => $databaseName,
                'table_name' => $tableName
            ])
            ->execute();

        $columnList = [];
        foreach ($columnsStatement->fetchAll(\PDO::FETCH_ASSOC) as $columnsRow) {
            $columnList[] = new ColumnMetadata(
                $columnsRow['name'],
                $columnsRow['type'],
                $columnsRow['max_length'],
                'PRI' === $columnsRow['column_key']
            );
        }

        return new TableMetadata(
            $databaseName,
            $tableName,
            $tableRow['engine'],
            $tableRow['collation'],
            $tableRow['row_count'],
            $tableRow['charset'],
            $columnList
        );
    }
}
