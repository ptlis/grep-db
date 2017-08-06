<?php

namespace ptlis\GrepDb\Search\Result\SearchStrategy;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use ptlis\GrepDb\Metadata\ColumnMetadata;
use ptlis\GrepDb\Metadata\TableMetadata;

/**
 * Abstract class implementing shared functionality for building a table search strategy.
 */
abstract class AbstractTableSearch implements TableSearchStrategy
{
    /** @var Connection */
    private $connection;

    /** @var TableMetadata */
    protected $tableMetadata;


    /**
     * @param Connection $connection
     * @param TableMetadata $tableMetadata
     */
    public function __construct(
        Connection $connection,
        TableMetadata $tableMetadata
    ) {
        $this->connection = $connection;
        $this->tableMetadata = $tableMetadata;
    }

    /**
     * Build the base query (everything but the SELECT).
     *
     * @param string $searchTerm
     * @return QueryBuilder
     */
    protected function buildBaseQuery($searchTerm)
    {
        $pkColumnMetadata = $this->getPrimaryKeyColumnMetadata();

        $this->connection->query('USE ' . $this->tableMetadata->getDatabaseName());

        // Build query except WHERE clause
        $queryBuilder = $this->connection
            ->createQueryBuilder()
            ->select('COUNT(DISTINCT ' . $pkColumnMetadata->getName() . ') AS count')
            ->from($this->tableMetadata->getTableName())
            ->setParameter('search_term', '%' . $searchTerm . '%');

        foreach ($this->getSearchableColumnNames() as $index => $columnName) {
            $clause = $columnName . ' LIKE :search_term';
            if (0 === $index) {
                $queryBuilder->where($clause);
            } else {
                $queryBuilder->orWhere($clause);
            }
        }

        return $queryBuilder;
    }

    /**
     * Get the column metadata for the primary key (if present). Returns null if the table doesn't have a primary key.
     *
     * @return null|ColumnMetadata
     */
    protected function getPrimaryKeyColumnMetadata()
    {
        $pkColumnMetadata = null;
        foreach ($this->tableMetadata->getAllColumnMetadata() as $columnMetadata) {
            if ($columnMetadata->isPrimaryKey()) {
                $pkColumnMetadata = $columnMetadata;
            }
        }
        return $pkColumnMetadata;
    }

    /**
     * Returns an array of relevant column names
     *
     * @return string[]
     */
    protected function getSearchableColumnNames()
    {
        $columnNameList = [];
        foreach ($this->tableMetadata->getAllColumnMetadata() as $columnMetadata) {

            // Column is string type, can be searched on
            if ($columnMetadata->isStringType()) {
                $columnNameList[] = $columnMetadata->getName();
            }
        }
        return $columnNameList;
    }
}
