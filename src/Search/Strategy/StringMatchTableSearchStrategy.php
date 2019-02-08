<?php declare(strict_types=1);

/**
 * @copyright   (c) 2017-present brian ridley
 * @author      brian ridley <ptlis@ptlis.net>
 * @license     http://opensource.org/licenses/MIT MIT
 */

namespace ptlis\GrepDb\Search\Strategy;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use ptlis\GrepDb\Metadata\MySQL\TableMetadata;
use ptlis\GrepDb\Search\Result\FieldSearchResult;
use ptlis\GrepDb\Search\Result\RowSearchResult;

/**
 * Search strategy for simple string searches.
 */
final class StringMatchTableSearchStrategy implements TableSearchStrategy
{
    /**
     * @inheritdoc
     */
    public function getCount(
        Connection $connection,
        TableMetadata $tableMetadata,
        string $searchTerm
    ): int {
        $statement = $this
            ->buildBaseQuery($connection, $tableMetadata, $searchTerm)
            ->select('COUNT(*) AS count')
            ->execute();

        return intval($statement->fetchColumn(0));
    }

    /**
     * @inheritdoc
     */
    public function getMatches(
        Connection $connection,
        TableMetadata $tableMetadata,
        string $searchTerm
    ): \Generator {

        // Build lookup list (string columns and primary key if present)
        $pkColumnMetadata = $tableMetadata->getPrimaryKeyMetadata();
        $lookupColumnsList = $this->getSearchableColumnNames($tableMetadata);
        if ($pkColumnMetadata) {
            $lookupColumnsList[] = $pkColumnMetadata->getColumnName();
        }

        $queryBuilder = $this
            ->buildBaseQuery($connection, $tableMetadata, $searchTerm)
            ->select(array_map(
                function (string $columnName) {
                    return '`' . $columnName . '`';
                },
                $lookupColumnsList
            ));

        $statement = $queryBuilder->execute();

        // Read data one row at a time, building and yielding a RowResult. This lets us deal with large tables without
        // a ballooning memory requirement
        while ($row = $statement->fetch(\PDO::FETCH_ASSOC)) {

            // Build a list of columns that have matches
            $fieldMatchList = [];
            foreach ($row as $columnName => $value) {
                if (null !== $value && false !== stristr($value, $searchTerm)) {
                    $fieldMatchList[] = new FieldSearchResult(
                        $tableMetadata->getColumnMetadata($columnName),
                        $row[$columnName]
                    );
                }
            }

            // Handle presence or absence of primary key
            yield new RowSearchResult(
                $tableMetadata,
                $fieldMatchList,
                $pkColumnMetadata,
                $pkColumnMetadata ? $row[$pkColumnMetadata->getColumnName()] : null
            );
        }
    }

    /**
     * Build the base query (everything but the SELECT).
     */
    private function buildBaseQuery(
        Connection $connection,
        TableMetadata $tableMetadata,
        string $searchTerm
    ): QueryBuilder {

        // Build query except WHERE clause
        $queryBuilder = $connection
            ->createQueryBuilder()
            ->select('COUNT(*) AS count')
            ->from('`' . $tableMetadata->getDatabaseName() . '`.`' . $tableMetadata->getTableName() . '`')
            ->setParameter('search_term', '%' . $searchTerm . '%');

        foreach ($this->getSearchableColumnNames($tableMetadata) as $index => $columnName) {
            $clause = '`' . $columnName . '` LIKE :search_term';
            if (0 === $index) {
                $queryBuilder->where($clause);
            } else {
                $queryBuilder->orWhere($clause);
            }
        }

        return $queryBuilder;
    }

    /**
     * Returns an array of searchable columns (columns that store strings).
     *
     * @param TableMetadata $tableMetadata
     * @return string[]
     */
    private function getSearchableColumnNames(TableMetadata $tableMetadata): array
    {
        $columnNameList = [];
        foreach ($tableMetadata->getAllColumnMetadata() as $columnMetadata) {
            if ($columnMetadata->isStringType()) {
                $columnNameList[] = $columnMetadata->getColumnName();
            }
        }
        return $columnNameList;
    }
}