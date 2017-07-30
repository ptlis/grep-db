<?php


namespace ptlis\GrepDb\Metadata;


use Doctrine\DBAL\Connection;

final class MetadataFactory
{
    /**
     * @param Connection $connection
     * @param string $databaseName
     * @return DatabaseMetadata
     */
    public function buildDatabaseMetadata(Connection $connection, $databaseName)
    {
        // Get table information
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
            ->execute();

        $tableList = [];
        foreach ($tableStatement->fetchAll(\PDO::FETCH_ASSOC) as $row) {

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
                    'table_name' => $row['name']
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

            $tableList[] = new TableMetadata(
                $row['name'],
                $row['engine'],
                $row['collation'],
                $row['row_count'],
                $row['charset'],
                $columnList
            );
        }

        return new DatabaseMetadata($tableList);
    }
}
