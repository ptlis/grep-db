<?php declare(strict_types=1);

/**
 * @copyright   (c) 2017-present brian ridley
 * @author      brian ridley <ptlis@ptlis.net>
 * @license     http://opensource.org/licenses/MIT MIT
 */

namespace ptlis\GrepDb\Search\Strategy;

use Doctrine\DBAL\Connection;
use ptlis\GrepDb\Metadata\MySQL\TableMetadata;
use ptlis\GrepDb\Search\Result\RowSearchResult;

/**
 * Interface that search strategies must implement.
 */
interface TableSearchStrategy
{
    /**
     * Gets the number of matches for $searchTerm in the provided table.
     */
    public function getCount(
        Connection $connection,
        TableMetadata $tableMetadata,
        string $searchTerm
    ): int;

    /**
     * Perform a search for the specified term, returning a generator providing RowResults.
     *
     * @return \Generator|RowSearchResult[]
     */
    public function getMatches(
        Connection $connection,
        TableMetadata $tableMetadata,
        string $searchTerm
    ): \Generator;
}