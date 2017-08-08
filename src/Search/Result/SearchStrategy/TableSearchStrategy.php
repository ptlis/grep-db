<?php

namespace ptlis\GrepDb\Search\Result\SearchStrategy;

use ptlis\GrepDb\Search\Result\RowResult;

/**
 * Interface that search strategies must implement.
 */
interface TableSearchStrategy
{
    /**
     * Gets the number of matches for $searchTerm in the provided table.
     *
     * @param string $searchTerm
     * @return int
     */
    public function getCount($searchTerm);

    /**
     * Perform a search for the specified term, returning a generator providing RowResults.
     *
     * @param string $searchTerm
     * @param int $offset
     * @param int $limit
     * @return \Generator|RowResult[]
     */
    public function getMatches($searchTerm, $offset, $limit);
}
