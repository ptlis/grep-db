<?php

namespace ptlis\GrepDb\Search\Result\SearchStrategy;
use ptlis\GrepDb\Search\Result\FieldResult;
use ptlis\GrepDb\Search\Result\RowResult;

/**
 * Search strategy for simple string searches.
 */
final class StringMatchTableSearch extends AbstractTableSearch
{
    /**
     * {@inheritdoc}
     */
    public function getCount($searchTerm)
    {
        $statement = $this
            ->buildBaseQuery($searchTerm)
            ->select('COUNT(*) AS count')
            ->execute();

        return intval($statement->fetchColumn(0));
    }

    /**
     * {@inheritdoc}
     */
    public function getMatches($searchTerm, $offset, $limit)
    {
        // Lookup primary key & any searchable columns
        $lookupColumns = $this->getSearchableColumnNames();
        if ($this->getPrimaryKeyColumnMetadata()) {
            $lookupColumns = array_merge(
                [
                    $this->getPrimaryKeyColumnMetadata()->getName()
                ],
                $lookupColumns
            );
        }

        $queryBuilder = $this
            ->buildBaseQuery($searchTerm)
            ->select($lookupColumns);

        // Only apply pagination if the offset & limit are sane
        if ($offset >= 0 && $limit > 0) {
            $queryBuilder
                ->setFirstResult($offset)
                ->setMaxResults($limit);
        }

        $statement = $queryBuilder->execute();

        // Read data one row at a time, building and yielding a RowResult. This lets us deal with large tables without
        // a ballooning memory requirement
        while ($row = $statement->fetch(\PDO::FETCH_ASSOC)) {

            $matchColumnList = [];
            foreach ($row as $columnName => $value) {
                if (false !== stristr($value, $searchTerm)) {
                    // Not every cell in a column has a result.
                    $matchColumnList[] = new FieldResult(
                        $this->tableMetadata->getColumnMetadata($columnName),
                        $row[$columnName]
                    );
                }
            }

            $pkColumnMetadata = $this->getPrimaryKeyColumnMetadata();
            if ($pkColumnMetadata) {
                $rowResult = new RowResult($matchColumnList, $pkColumnMetadata, $row[$pkColumnMetadata->getName()]);
            } else {
                $rowResult = new RowResult($matchColumnList);
            }

            yield $rowResult;
        }
    }
}
