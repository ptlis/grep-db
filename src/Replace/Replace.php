<?php

namespace ptlis\GrepDb\Replace;

use Doctrine\DBAL\Connection;
use ptlis\GrepDb\Replace\Strategy\ReplacementStrategy;
use ptlis\GrepDb\Replace\Strategy\SerializedReplace;
use ptlis\GrepDb\Replace\Strategy\StringReplace;
use ptlis\GrepDb\Search\Result\DatabaseResultGateway;
use ptlis\GrepDb\Search\Result\RowResult;
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
        echo 'Table: ' . $tableResultGateway->getMetadata()->getName() . PHP_EOL;

        $rowCount = 0;
        $rowBatch = [];
        foreach ($tableResultGateway->getMatchingRows() as $matchingRow) {
            $rowCount++;
            $rowBatch[] = $matchingRow;

            if (0 === ($rowCount % $this->batchSize)) {
                $this->batchUpdateRows($tableResultGateway, $rowBatch, $replaceTerm);
                $rowBatch = [];
            }

            if (0 === ($rowCount % $this->batchSize)) {
                echo 'Completed ' . $rowCount . ' rows' . PHP_EOL;
            }
        }

        if (0 !== ($rowCount % $this->batchSize)) {
            $this->batchUpdateRows($tableResultGateway, $rowBatch, $replaceTerm);
        }

        echo 'Completed ' . $rowCount . ' rows' . PHP_EOL;
    }

    /**
     * Batch update records.
     *
     * This is just awful, but way more efficient than the alternative...
     *
     * @param TableResultGateway $tableResultGateway
     * @param RowResult[] $rowResultList
     * @param string $replaceTerm
     */
    private function batchUpdateRows(
        TableResultGateway $tableResultGateway,
        array $rowResultList,
        $replaceTerm
    ) {
        $columnNameList = $this->findColumnsWithReplacements($rowResultList);

        // Set correct charset & collation for this table
        $charset = $tableResultGateway->getMetadata()->getCharset();
        $collation = $tableResultGateway->getMetadata()->getCollation();
        $this->connection
            ->query('SET NAMES \'' . $charset . '\' COLLATE \'' . $collation . '\'')
            ->execute();

        // Replace with when of primary key
        if ($tableResultGateway->getMetadata()->hasPrimaryKey()) {

            $query = 'UPDATE  `' . $tableResultGateway->getMetadata()->getName() . '` SET' . PHP_EOL;

            $rowIdList = [];

            // Build case/when block for each column
            $firstRun = true;
            foreach ($columnNameList as $columnName) {
                if ($firstRun) {
                    $firstRun = false;
                } else {
                    $query .= ',' . PHP_EOL;
                }
                $query .= '  ' . $columnName . ' = (CASE' . PHP_EOL;

                // Build 'when' statement for each row
                foreach ($rowResultList as $rowResult) {
                    $primaryKey = $rowResult->getPrimaryKeyValue();
                    $rowIdList[$primaryKey] = true;

                    // Replace string in column
                    if ($rowResult->hasColumnResult($columnName)) {
                        $columnValue = $rowResult->getColumnResult($columnName)->getValue();
                        $replaced = $this->replace($tableResultGateway->getSearchTerm(), $replaceTerm, $columnValue);
                        $query .= '    WHEN ' . $primaryKey . ' THEN ' . $this->connection->quote($replaced) . PHP_EOL;

                    // Don't touch the column
                    } else {
                        $query .= '    WHEN ' . $primaryKey . ' THEN ' . $columnName . PHP_EOL;
                    }
                }

                $query .= '    ELSE `' . $columnName . '`' . PHP_EOL;
                $query .= '  END)';
            }

            $whereIn = array_map(
                function ($id) {
                    return $this->connection->query($id);
                },
                array_keys($rowIdList)
            );

            $primaryKeyName = $tableResultGateway->getMetadata()->getPrimaryKey()->getName();
            $query .= PHP_EOL . 'WHERE `' . $primaryKeyName . '` in (' . $whereIn . ')';

            // Run the update
            $this->connection->query($query)->execute();

        } else {
            // TODO: Implement
            throw new \RuntimeException('Cannot perform replacement without primary key');
        }
    }

    /**
     * Find all columns that are to be updated.
     *
     * @param RowResult[] $rowResultList
     * @return string[] An array of column names to replace
     */
    private function findColumnsWithReplacements(
        array $rowResultList
    ) {
        $columnNames = [];
        foreach ($rowResultList as $rowResult) {
            foreach ($rowResult->getMatchingColumns() as $matchingColumn) {
                $columnNames[$matchingColumn->getColumnMetadata()->getName()] = 1;
            }
        }

        return array_keys($columnNames);
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
