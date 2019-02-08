<?php declare(strict_types=1);

/**
 * @copyright   (c) 2017-present brian ridley
 * @author      brian ridley <ptlis@ptlis.net>
 * @license     http://opensource.org/licenses/MIT MIT
 */

namespace ptlis\GrepDb\Replace;

use Doctrine\DBAL\Connection;
use ptlis\GrepDb\Metadata\MySQL\ColumnMetadata;
use ptlis\GrepDb\Metadata\MySQL\MetadataFactory;
use ptlis\GrepDb\Replace\Result\FieldReplaceResult;
use ptlis\GrepDb\Replace\Result\RowReplaceResult;
use ptlis\GrepDb\Replace\Strategy\FieldReplaceStrategy;
use ptlis\GrepDb\Replace\Strategy\SerializedFieldReplaceStrategy;
use ptlis\GrepDb\Replace\Strategy\StringFieldReplaceStrategy;
use ptlis\GrepDb\Search\Result\RowSearchResult;
use ptlis\GrepDb\Search\Search;

/**
 * Performs search & replacement.
 */
final class Replace
{
    /** @var FieldReplaceStrategy[] */
    private $replacementStrategyList;


    /**
     * @param FieldReplaceStrategy[] $replacementStrategyList
     */
    public function __construct(
        array $replacementStrategyList = []
    ) {
        if (!count($replacementStrategyList)) {
            $replacementStrategyList = [
                new StringFieldReplaceStrategy(),
                new SerializedFieldReplaceStrategy()
            ];
        }
        $this->replacementStrategyList = $replacementStrategyList;
    }

    /**
     * Performs a search on all tables in the the provided database, batching queries to the specified batch size.
     *
     * @return \Generator|RowReplaceResult[]
     */
    public function replaceDatabase(
        Connection $connection,
        string $databaseName,
        string $searchTerm,
        string $replaceTerm,
        int $batchSize = 100
    ): \Generator {
        $databaseMetadata = (new MetadataFactory())->getDatabaseMetadata($connection, $databaseName);

        foreach ($databaseMetadata->getAllTableMetadata() as $tableMetadata) {
            $rowResultList = $this->replaceTable(
                $connection,
                $databaseName,
                $tableMetadata->getTableName(),
                $searchTerm,
                $replaceTerm,
                $batchSize
            );

            foreach ($rowResultList as $rowResult) {
                yield $rowResult;
            }
        }
    }

    /**
     * Performs a search on the provided table, batching queries to the specified batch size.
     *
     * @return \Generator|RowReplaceResult[]
     */
    public function replaceTable(
        Connection $connection,
        string $databaseName,
        string $tableName,
        string $searchTerm,
        string $replaceTerm,
        int $batchSize = 100
    ): \Generator {
        $connection->query('START TRANSACTION');

        $rowCount = 0;

        $rowSearchResultList = (new Search())->searchTable($connection, $databaseName, $tableName, $searchTerm);
        foreach ($rowSearchResultList as $rowSearchResult) {
            $rowCount++;

            $rowReplaceResult = $this->replaceFields($rowSearchResult, $searchTerm, $replaceTerm);

            $queryBuilder = $connection->createQueryBuilder();

            // Build key => value mapping of replacement data
            $replacementData = [];
            foreach ($rowReplaceResult->getFieldResultList() as $fieldReplacementResult) {
                $replacementData[$fieldReplacementResult->getColumnMetadata()->getColumnName()] = $fieldReplacementResult->getNewValue();
            }

            $queryBuilder
                ->update(
                    '`' . $rowSearchResult->getTableMetadata()->getDatabaseName() . '`.`' . $rowSearchResult->getTableMetadata()->getTableName() . '`', 'subject'
                )
                ->setParameters($replacementData)
                ->setParameter('key', $rowSearchResult->getPrimaryKeyValue());

            foreach (array_keys($replacementData) as $columnName) {
                $queryBuilder->set('subject.' . $columnName, ':' . $columnName);
            }

            // Update using primary key
            if (null !== $rowSearchResult->getPrimaryKeyColumn()) {
                $queryBuilder->where('subject.' . $rowSearchResult->getPrimaryKeyColumn()->getColumnName() . ' = :key');

            // Update using original values
            } else {
                $whereCount = 0;
                foreach ($rowReplaceResult->getFieldResultList() as $fieldReplaceResult) {
                    $columnName = $fieldReplaceResult->getColumnMetadata()->getColumnName();
                    $whereClause = 'subject.' . $columnName . ' = :old_' . $columnName;

                    if (0 === $whereCount) {
                        $queryBuilder->where($whereClause);
                    } else {
                        $queryBuilder->andWhere($whereClause);
                    }

                    $queryBuilder->setParameter('old_' . $columnName, $fieldReplaceResult->getOldValue());
                }
            }

            // TODO: handle exception
            $queryBuilder->execute();

            if (0 === ($rowCount % $batchSize)) {
                $connection->query('COMMIT');
                $connection->query('START TRANSACTION');
            }

            yield $rowReplaceResult;
        }

        if (0 !== ($rowCount % $batchSize)) {
            $connection->query('COMMIT');
        }
    }

    private function replaceFields(
        RowSearchResult $rowSearchResult,
        string $searchTerm,
        string $replaceTerm
    ): RowReplaceResult {
        $fieldReplaceResultList = [];
        $errorList = [];
        foreach ($rowSearchResult->getMatchingFields() as $fieldSearchResult) {
            $fieldReplaceResult = $this->replace($fieldSearchResult->getMetadata(), $searchTerm, $replaceTerm, $fieldSearchResult->getValue());

            // Avoid truncation if the replacement string is longer than the source
            if (strlen($fieldReplaceResult->getNewValue()) > $fieldSearchResult->getMetadata()->getMaxLength()) {
                // Tailor error message depending on whether or not there is a primary key
                if (null !== $rowSearchResult->getTableMetadata()->getPrimaryKeyMetadata()) {
                    $errorList[] = 'Length of new value (' . strlen($fieldReplaceResult->getNewValue()) . ') exceeds max length (' . $fieldSearchResult->getMetadata()->getMaxLength() . ') for column "' . $fieldSearchResult->getMetadata()->getTableName() . '.' . $fieldSearchResult->getMetadata()->getColumnName() . '" with primary key "' . $rowSearchResult->getPrimaryKeyValue() . '"';
                } else {
                    $errorList[] = 'Length of new value (' . strlen($fieldReplaceResult->getNewValue()) . ') exceeds max length (' . $fieldSearchResult->getMetadata()->getMaxLength() . ') for column "' . $fieldSearchResult->getMetadata()->getTableName() . '.' . $fieldSearchResult->getMetadata()->getColumnName() . '" of table "' . $fieldSearchResult->getMetadata()->getColumnName() . '", original value was "' . $fieldSearchResult->getValue() . '"';
                }
            }

            $fieldReplaceResultList[] = $fieldReplaceResult;
        }

        return new RowReplaceResult($rowSearchResult, $fieldReplaceResultList, $errorList);
    }

    /**
     * Perform the string replacement on the field.
     *
     * @throws \RuntimeException If the replacement fails.
     */
    private function replace(
        ColumnMetadata $columnMetadata,
        string $searchTerm,
        string $replaceTerm,
        string $subject
    ): FieldReplaceResult {
        $fieldReplaced = null;
        foreach ($this->replacementStrategyList as $replacementStrategy) {
            if ($replacementStrategy->canReplace($searchTerm, $subject)) {
                $fieldReplaced = $replacementStrategy->replace(
                    $columnMetadata,
                    $searchTerm,
                    $replaceTerm,
                    $subject
                );
                break;
            }
        }

        if (is_null($fieldReplaced)) {
            throw new \RuntimeException('Error trying to replace "' . $searchTerm . '" with "' . $replaceTerm . '"');
        }

        return $fieldReplaced;
    }
}