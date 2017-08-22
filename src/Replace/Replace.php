<?php

namespace ptlis\GrepDb\Replace;

use Doctrine\DBAL\Connection;
use ptlis\GrepDb\Metadata\TableMetadata;
use ptlis\GrepDb\Replace\ReplacementStrategy\ReplacementStrategy;
use ptlis\GrepDb\Replace\ReplacementStrategy\SerializedReplace;
use ptlis\GrepDb\Replace\ReplacementStrategy\StringReplace;
use ptlis\GrepDb\Replace\Result\DatabaseReplaceResult;
use ptlis\GrepDb\Replace\Result\TableReplaceResult;
use ptlis\GrepDb\Search\Result\DatabaseResultGateway;
use ptlis\GrepDb\Search\Result\TableResultGateway;

/**
 * Class through which database replacements are executed.
 */
final class Replace
{
    /** @var Connection */
    private $connection;

    /** @var int */
    private $batchSize;

    /** @var ReplacementStrategy[] */
    private $replacementStrategyList;


    /**
     * @param Connection $connection
     * @param ReplacementStrategy[] $replacementStrategyList
     * @param int $batchSize
     */
    public function __construct(
        Connection $connection,
        array $replacementStrategyList = [],
        $batchSize = 100
    ) {
        $this->connection = $connection;
        $this->batchSize = $batchSize;

        if (count($replacementStrategyList)) {
            $this->replacementStrategyList = $replacementStrategyList;
        } else {
            $this->replacementStrategyList = [
                new SerializedReplace(),
                new StringReplace()
            ];
        }
    }

    /**
     * Perform replacements across the database.
     *
     * @param DatabaseResultGateway $databaseResultsGateway
     * @param string $replaceTerm
     * @param bool $incrementalReturn Set to true to get intermediate values via generator
     * @return \Generator|TableReplaceResult[]
     */
    public function replaceDatabase(
        DatabaseResultGateway $databaseResultsGateway,
        $replaceTerm,
        $incrementalReturn
    ) {
        $tableResultList = [];

        foreach ($databaseResultsGateway->getMatchingTables() as $tableResultGateway) {

            // Run generator to incrementally replace results
            $generator = $this->replaceTable($tableResultGateway, $replaceTerm, $incrementalReturn);
            foreach ($generator as $tableReplaceResult) {
                $tableResultList[$tableReplaceResult->getMetadata()->getTableName()] = $tableReplaceResult;

                yield new DatabaseReplaceResult(
                    $databaseResultsGateway->getMetadata(),
                    $tableResultList,
                    false
                );
            }
        }

        yield new DatabaseReplaceResult(
            $databaseResultsGateway->getMetadata(),
            $tableResultList,
            true
        );
    }

    /**
     * Perform replacements on a single table.
     *
     * @param TableResultGateway $tableResultGateway
     * @param string $replaceTerm
     * @param bool $incrementalReturn Set to true to get intermediate values via generator.
     * @return \Generator|TableReplaceResult[]
     */
    public function replaceTable(
        TableResultGateway $tableResultGateway,
        $replaceTerm,
        $incrementalReturn
    ) {
        $this->setCharset($tableResultGateway->getMetadata());

        $this->connection->query('START TRANSACTION');

        $columnCount = 0;
        $rowCount = 0;
        $errorList = [];
        foreach ($tableResultGateway->getMatchingRows() as $matchingRow) {
            $rowCount++;

            $replacementData = [];
            foreach ($matchingRow->getMatchingColumns() as $matchingColumn) {
                $afterReplace = $this->replace(
                    $tableResultGateway->getSearchTerm(),
                    $replaceTerm,
                    $matchingColumn->getValue()
                );

                // After replacement the data is too long, we must truncate :(
                if (strlen($afterReplace) > $matchingColumn->getMetadata()->getMaxLength()) {
                    if ($matchingRow->hasPrimaryKey()) {
                        $errorList[] = 'Error: Truncating column named ' . $matchingColumn->getMetadata()->getName() . ', value "' . $matchingRow->getPrimaryKeyValue() . '"" in table ' . $tableResultGateway->getMetadata()->getTableName();
                    } else {
                        $errorList[] = 'Error: Truncating column with original value "' . $matchingColumn->getValue() . '"';
                    }

                    $replacementData[$matchingColumn->getMetadata()->getName()] = $matchingColumn->getValue();
                } else {
                    $replacementData[$matchingColumn->getMetadata()->getName()] = $afterReplace;
                }
            }

            $queryBuilder = $this->connection->createQueryBuilder();

            // Update using primary key
            if ($matchingRow->hasPrimaryKey()) {
                $queryBuilder
                    ->update(
                        $tableResultGateway->getMetadata()->getTableName(), 'subject'
                    )
                    ->where('subject.' . $matchingRow->getPrimaryKeyColumn()->getName() . ' = :key')
                    ->setParameters($replacementData)
                    ->setParameter('key', $matchingRow->getPrimaryKeyValue());

                foreach ($replacementData as $columnName => $columnValue) {
                    $columnCount++;
                    $queryBuilder->set('subject.' . $columnName, ':' . $columnName);
                }

            // If there is no primary key then use the matched columns & values
            // TODO: Implement!
            } else {
                var_dump('no pk');die();
            }

            $queryBuilder->execute();

            if (0 === ($rowCount % $this->batchSize)) {
                $this->connection->query('COMMIT');
                $this->connection->query('START TRANSACTION');

                if ($incrementalReturn) {
                    yield new TableReplaceResult(
                        $tableResultGateway->getMetadata(),
                        $rowCount,
                        $columnCount,
                        $errorList,
                        false
                    );
                }
            }
        }

        if (0 !== ($rowCount % $this->batchSize)) {
            $this->connection->query('COMMIT');
        }

        yield new TableReplaceResult(
            $tableResultGateway->getMetadata(),
            $rowCount,
            $columnCount,
            $errorList,
            true
        );
    }

    /**
     * Set the correct charset & collation for the table.
     *
     * @param TableMetadata $metadata
     */
    private function setCharset(
        TableMetadata $metadata
    ) {
        $this->connection
            ->query('SET NAMES \'' . $metadata->getCharset() . '\' COLLATE \'' . $metadata->getCollation() . '\'')
            ->execute();
    }

    /**
     * Perform a search and replace on the subject.
     *
     * This is achieved by iterating through the replacement strategies and find one that can perform the replacement,
     * and then using that replacement strategy.
     *
     * @param string $searchTerm
     * @param string $replaceTerm
     * @param string $subject
     * @return string
     */
    private function replace(
        $searchTerm,
        $replaceTerm,
        $subject
    ) {
        $hasReplaced = false;
        $afterReplace = '';
        foreach ($this->replacementStrategyList as $replacementStrategy) {
            if ($replacementStrategy->canReplace($subject)) {
                $hasReplaced = true;
                $afterReplace = $replacementStrategy->replace($searchTerm, $replaceTerm, $subject);
                break;
            }
        }

        if (!$hasReplaced) {
            throw new \RuntimeException('Error trying to replace "' . $searchTerm . '" with "' . $replaceTerm . '"');
        }

        return $afterReplace;
    }
}
