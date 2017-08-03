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
            ->select('COUNT(DISTINCT ' . $this->getPrimaryKeyColumnMetadata()->getName() . ') AS count')
            ->execute();

        return intval($statement->fetchColumn(0));
    }

    /**
     * {@inheritdoc}
     */
    public function getMatches($searchTerm)
    {
        $pkColumnMetadata = $this->getPrimaryKeyColumnMetadata();

        $statement = $this
            ->buildBaseQuery($searchTerm)
            ->select(
                array_merge(
                    ['DISTINCT ' . $pkColumnMetadata->getName()],
                    $this->getSearchableColumnNames()
                )
            )
            ->execute();

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

            if ($pkColumnMetadata) {
                $rowResult = new RowResult($matchColumnList, $pkColumnMetadata, $row[$pkColumnMetadata->getName()]);
            } else {
                $rowResult = new RowResult($matchColumnList);
            }

            yield $rowResult;
        }
    }
}
