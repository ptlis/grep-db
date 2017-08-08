<?php

namespace ptlis\GrepDb\Search\Result;

use Doctrine\DBAL\Connection;
use ptlis\GrepDb\Metadata\TableMetadata;
use ptlis\GrepDb\Search\Result\SearchStrategy\StringMatchTableSearch;

/**
 * Gateway used to retrieve search results for a table.
 *
 * Batches queries, returns them via yield
 */
final class TableResultGateway
{
    /** @var TableMetadata */
    private $tableMetadata;

    /** @var string */
    private $searchTerm;

    /** @var int */
    private $offset;

    /** @var int */
    private $limit;

    /** @var StringMatchTableSearch */
    protected $searchStrategy;


    /**
     * @param Connection $connection
     * @param TableMetadata $tableMetadata
     * @param string $searchTerm
     * @param int $offset
     * @param int $limit
     */
    public function __construct(
        Connection $connection,
        TableMetadata $tableMetadata,
        $searchTerm,
        $offset = -1,
        $limit = -1
    ) {
        $this->tableMetadata = $tableMetadata;
        $this->searchTerm = $searchTerm;
        $this->offset = $offset;
        $this->limit = $limit;

        // TODO: Inject
        $this->searchStrategy = new StringMatchTableSearch($connection, $tableMetadata);
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
        return $this->searchStrategy->getCount($this->searchTerm);
    }

    /**
     * Return the maximum number of rows that would have been returned.
     *
     * @return int
     */
    public function getMaxRowsReturned()
    {
        $totalMatchCount = $this->getMatchingCount();

        if (-1 == $this->limit) {
            $max = $totalMatchCount;
        } else if ($this->limit > $totalMatchCount) {
            $max = $totalMatchCount;
        } else {
            $max = $this->limit;
        }

        return $max;
    }

    /**
     * Get rows matching the search term, within the specified range.
     *
     * @return \Generator|RowResult[]
     */
    public function getMatchingRows()
    {
        return $this->searchStrategy->getMatches($this->searchTerm, $this->offset, $this->limit);
    }
}
