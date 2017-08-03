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

    /** @var StringMatchTableSearch */
    protected $searchStrategy;


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
        $this->tableMetadata = $tableMetadata;
        $this->searchTerm = $searchTerm;

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
     * Get rows matching the search term.
     *
     * @return \Generator|RowResult[]
     */
    public function getMatchingRows()
    {
        return $this->searchStrategy->getMatches($this->searchTerm);
    }
}
