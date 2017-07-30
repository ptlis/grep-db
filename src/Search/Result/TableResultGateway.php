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

    /** @var int */
    private $batchSize;


    /**
     * @param Connection $connection
     * @param TableMetadata $tableMetadata
     * @param string $searchTerm
     * @param int $batchSize
     */
    public function __construct(
        Connection $connection,
        TableMetadata $tableMetadata,
        $searchTerm,
        $batchSize = 1000
    ) {
        $this->connection = $connection;
        $this->tableMetadata = $tableMetadata;
        $this->searchTerm = $searchTerm;
        $this->batchSize = $batchSize;
    }

    /**
     * Get number of rows matching the search term.
     *
     * @return int
     */
    public function getMatchingCount()
    {
        // Build query except WHERE clause
        $queryBuilder = $this->connection
            ->createQueryBuilder()
            ->select('COUNT(*) AS count')
            ->from($this->tableMetadata->getName())
            ->setParameter('search_term', '%' . $this->searchTerm . '%');

        // Build the where clauses
        $this->addWhereClauses($queryBuilder, $this->getSearchableColumnNames());

        $statement = $queryBuilder->execute();
        return $statement->fetchColumn(0);
    }

    /**
     * @return TableMetadata
     */
    public function getTableMetadata()
    {
        return $this->tableMetadata;
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
                    [$pkColumnMetadata->getName()],
                    $columnNameList
                )
            )
            ->from($this->tableMetadata->getName())
            ->setParameter('search_term', '%' . $this->searchTerm . '%');

        // Build the where clauses
        $this->addWhereClauses($queryBuilder, $columnNameList);

        // Run queries in batches
        for ($i = 0; $i < $this->getMatchingCount(); $i += $this->batchSize) {
            $queryBuilder
                ->setFirstResult($i)
                ->setMaxResults($this->batchSize);

            $statement = $queryBuilder->execute();

            foreach ($statement->fetchAll(\PDO::FETCH_ASSOC) as $row) {

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
