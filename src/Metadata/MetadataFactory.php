<?php


namespace ptlis\GrepDb\Metadata;


final class MetadataFactory
{
    /**
     * @param \PDO $connection
     * @param string $databaseName
     * @return DatabaseMetadata
     */
    public function buildDatabaseMetadata(\PDO $connection, $databaseName)
    {
        // Get table information
        $tablesQuery = '
            SELECT  tables.TABLE_NAME as name,
		            tables.ENGINE as engine,
                    tables.TABLE_COLLATION as collation,
                    tables.TABLE_ROWS as row_count,
                    charset.CHARACTER_SET_NAME as charset
            FROM information_schema.TABLES tables
            LEFT JOIN information_schema.COLLATION_CHARACTER_SET_APPLICABILITY charset
                ON tables.TABLE_COLLATION = charset.COLLATION_NAME
		';

        $tablesResult = $connection->query($tablesQuery);

        $tableList = [];
        foreach ($tablesResult->fetchAll(\PDO::FETCH_ASSOC) as $row) {

            // Get column information
            $columnsQuery = '
                SELECT  COLUMN_NAME as name,
                        DATA_TYPE as type,
                        COLUMN_KEY as column_key
                FROM    information_schema.COLUMNS
                WHERE   TABLE_SCHEMA = :schema
                AND     TABLE_NAME = :table_name
            ';

            $columnsStatement = $connection->prepare($columnsQuery);
            $columnsStatement->bindParam(':schema', $databaseName);
            $columnsStatement->bindParam(':table_name', $row['name']);
            $columnsStatement->execute();

            $columnList = [];
            foreach ($columnsStatement->fetchAll(\PDO::FETCH_ASSOC) as $columnsRow) {
                $columnList[] = new ColumnMetadata(
                    $columnsRow['name'],
                    $columnsRow['type'],
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
