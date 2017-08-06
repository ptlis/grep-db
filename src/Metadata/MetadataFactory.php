<?php

namespace ptlis\GrepDb\Metadata;

use Doctrine\DBAL\Connection;

/**
 * Factory that builds server, database & table metadata.
 */
final class MetadataFactory
{
    /**
     * Return an array of database names.
     *
     * @param Connection $connection
     * @return string[]
     */
    public function getDatabaseNames(
        Connection $connection
    ) {
        // Internal mySQL database to ignore
        $excludeDatabases = [
            'information_schema',
            'performance_schema',
            'sys',
            'mysql'
        ];

        $databaseNameList = [];
        $statement = $connection->query('SHOW DATABASES');
        while ($databaseName = $statement->fetchColumn(0)) {
            if (!in_array($databaseName, $excludeDatabases)) {
                $databaseNameList[] = $databaseName;
            }
        }

        return $databaseNameList;
    }

    /**
     * Return an array of table names in the specified database.
     *
     * @param Connection $connection
     * @param string $databaseName
     * @return string[]
     */
    public function getTableNames(
        Connection $connection,
        $databaseName
    ) {
        $connection->query('USE ' . $databaseName);

        $statement = $connection
            ->createQueryBuilder()
            ->select([
                'tables.TABLE_NAME AS name'
            ])
            ->from('information_schema.TABLES', 'tables')
            ->where('TABLE_SCHEMA = :schema')
            ->setParameter('schema', $databaseName)
            ->execute();

        $tableNameList = [];
        while ($tableName = $statement->fetchColumn(0)) {
            $tableNameList[] = $tableName;
        }

        return $tableNameList;
    }

    /**
     * Returns a ServerMetadata instance.
     *
     * @param string $host
     * @param Connection $connection
     * @return ServerMetadata
     */
    public function buildServerMetadata(
        $host,
        Connection $connection
    ) {
        return new ServerMetadata($connection, $this, $host);
    }

    /**
     * Returns a DatabaseMetadata instance.
     *
     * @param Connection $connection
     * @param string $databaseName
     * @return DatabaseMetadata
     */
    public function buildDatabaseMetadata(
        Connection $connection,
        $databaseName
    ) {
        return new DatabaseMetadata($connection, $this, $databaseName);
    }

    /**
     * Returns a TableMetadata instance.
     *
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
