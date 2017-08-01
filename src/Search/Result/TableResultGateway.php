<?php

namespace ptlis\GrepDb\Search\Result;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use ptlis\GrepDb\Metadata\ColumnMetadata;
use ptlis\GrepDb\Metadata\TableMetadata;

/**
 * Gateway used to retrieve search results for a table.
 *
 * Batches queries, returns them via yeild
 */
final class TableResultGateway
{
    /** @var Connection */
    private $connection;

    /** @var TableMetadata */
    private $tableMetadata;

    /** @var string */
    private $searchTerm;


    /**
     * @param Connection $connection
     * @param TableMetadata $tableMetadata
     * @param string $searchTerm
     */
    public function __construct(
        Connection $connection,
        TableMetadata $tableMetadata,
        $searchTerm
    ) {
        $this->connection = $connection;
        $this->tableMetadata = $tableMetadata;
        $this->searchTerm = $searchTerm;
    }

    /**
     * @return TableMetadata
     */
    public function getMetadata()
    {
        return $this->tableMetadata;
    }

    /**
     * Returns the search term.
     *
     * @return string
     */
    public function getSearchTerm()
    {
        return $this->searchTerm;
    }

    /**
     * Get number of rows matching the search term.
     *
     * @return int
     */
    public function getMatchingCount()
    {
        $pkColumnMetadata = $this->getPrimaryKeyColumnMetadata();

        // Build query except WHERE clause
        $queryBuilder = $this->connection
            ->createQueryBuilder()
            ->select('COUNT(DISTINCT ' . $pkColumnMetadata->getName() . ') AS count')
            ->from($this->tableMetadata->getName())
            ->setParameter('search_term', '%' . $this->searchTerm . '%');

        // Build the where clauses
        $this->addWhereClauses($queryBuilder, $this->getSearchableColumnNames());
        $statement = $queryBuilder->execute();

        return intval($statement->fetchColumn(0));
    }

    /**
     * Get rows matching the search term.
     *
     * @return \Generator|RowResult[]
     */
    public function getMatchingRows()
    {
        // Build a list of relevant column names and store the primary key column (if present)
        $columnNameList = $this->getSearchableColumnNames();
        $pkColumnMetadata = $this->getPrimaryKeyColumnMetadata();

        // Build query except WHERE clause
        $queryBuilder = $this->connection
            ->createQueryBuilder()
            ->select(
                array_merge(
                    ['DISTINCT ' . $pkColumnMetadata->getName()],
                    $columnNameList
                )
            )
            ->from($this->tableMetadata->getName())
            ->setParameter('search_term', '%' . $this->searchTerm . '%');

        // Build the where clauses
        $this->addWhereClauses($queryBuilder, $columnNameList);

        // Read data one row at a time, building and yielding a RowResult. This lets us deal with large tables without
        // a ballooning memory requirement
        $statement = $queryBuilder->execute();
        while ($row = $statement->fetch(\PDO::FETCH_ASSOC)) {

            $matchColumnList = [];
            foreach ($row as $columnName => $value) {
                if (false !== stristr($value, $this->searchTerm)) {
                    $matchColumnList[] = new ColumnResult(
                        $this->tableMetadata->getColumnMetadata($columnName),
                        $row[$columnName]
                    );
                }
            }

            if ($pkColumnMetadata) {
                $rowResult = new RowResult($matchColumnList, $pkColumnMetadata, $row[$pkColumnMetadata->getName()]);
            } else {
                $rowResult = new RowResult($matchColumnList);
            }

            yield $rowResult;
        }
    }

    /**
     * Returns an array of relevant column names
     *
     * @return string[]
     */
    private function getSearchableColumnNames()
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

    /**
     * Get the column metadata for the primary key (if present). Returns null if the table doesn't have a primary key.
     *
     * @return null|ColumnMetadata
     */
    private function getPrimaryKeyColumnMetadata()
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
     * Adds the where clauses to the query builder.
     *
     * @param QueryBuilder $queryBuilder
     * @param string[] $columnNameList
     */
    private function addWhereClauses(
        QueryBuilder $queryBuilder,
        $columnNameList
    ) {
        foreach ($columnNameList as $index => $columnName) {
            $clause = $columnName . ' LIKE :search_term';
            if (0 === $index) {
                $queryBuilder->where($clause);
            } else {
                $queryBuilder->orWhere($clause);
            }
        }
    }
}
