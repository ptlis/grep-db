<?php

namespace ptlis\GrepDb\Replace;

use Doctrine\DBAL\Connection;
use ptlis\GrepDb\Replace\ReplacementStrategy\ReplacementStrategy;
use ptlis\GrepDb\Replace\ReplacementStrategy\SerializedReplace;
use ptlis\GrepDb\Replace\ReplacementStrategy\StringReplace;
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
     * @param int $batchSize
     * @param ReplacementStrategy[] $replacementStrategyList
     */
    public function __construct(
        Connection $connection,
        $batchSize = 100,
        array $replacementStrategyList = []
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
     */
    public function replaceDatabase(
        DatabaseResultGateway $databaseResultsGateway,
        $replaceTerm
    ) {
        foreach ($databaseResultsGateway->getMatchingTables() as $tableResultGateway) {
            $this->replaceTable($tableResultGateway, $replaceTerm);
        }
    }

    /**
     * Perform replacements on a single table.
     *
     * @param TableResultGateway $tableResultGateway
     * @param string $replaceTerm
     */
    public function replaceTable(
        TableResultGateway $tableResultGateway,
        $replaceTerm
    ) {
        echo 'Table: ' . $tableResultGateway->getMetadata()->getTableName() . PHP_EOL;

        $this->connection->query('START TRANSACTION');

        $rowCount = 0;
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
                    $afterReplace = substr($afterReplace, 0, $matchingColumn->getMetadata()->getMaxLength());
                    // TODO: Properly track this!
                    if ($matchingRow->hasPrimaryKey()) {
                        echo 'Error: Truncating column named ' . $matchingColumn->getMetadata()->getName() . ', value ' . $matchingRow->getPrimaryKeyValue() . ' in table ' . $tableResultGateway->getMetadata()->getTableName() . PHP_EOL;
                    } else {
                        echo 'Error: Truncating column with original value "' . $matchingColumn->getValue() . '"'.PHP_EOL;
                    }
                }

                $replacementData[$matchingColumn->getMetadata()->getName()] = $afterReplace;
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
                    $queryBuilder->set('subject.' . $columnName, ':' . $columnName);
                }

                // If there is no primary key then use the matched columns & values
            } else {
                var_dump('no pk');die();
            }

            $queryBuilder->execute();

            if (0 === ($rowCount % $this->batchSize)) {
                echo 'Completed ' . $rowCount . ' rows' . PHP_EOL;
                $this->connection->query('COMMIT');
                $this->connection->query('START TRANSACTION');
            }
        }

        if (0 !== ($rowCount % $this->batchSize)) {
            $this->connection->query('COMMIT');
        }

        echo 'Completed ' . $rowCount . ' rows' . PHP_EOL;
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
