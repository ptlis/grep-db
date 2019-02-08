<?php declare(strict_types=1);

/**
 * @copyright   (c) 2017-present brian ridley
 * @author      brian ridley <ptlis@ptlis.net>
 * @license     http://opensource.org/licenses/MIT MIT
 */

namespace ptlis\GrepDb\Replace\Strategy;

use ptlis\GrepDb\Metadata\MySQL\ColumnMetadata;
use ptlis\GrepDb\Replace\Result\FieldReplaceResult;

/**
 * Interface that replacement strategies must implement.
 */
interface FieldReplaceStrategy
{
    /**
     * Returns true if the strategy can perform a replacement on the subject.
     */
    public function canReplace(string $searchTerm, string $subject): bool;

    /**
     * Replaces all instances of the search term with the replacement term.
     */
    public function replace(
        ColumnMetadata $columnMetadata,
        string $searchTerm,
        string $replaceTerm,
        string $subject
    ): FieldReplaceResult;
}