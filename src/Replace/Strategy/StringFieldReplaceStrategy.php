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
 * Perform replacement on a simple string.
 */
final class StringFieldReplaceStrategy implements FieldReplaceStrategy
{
    /**
     * @inheritdoc
     */
    public function canReplace(string $searchTerm, string $subject): bool
    {
        return substr_count($subject, $searchTerm) > 0;
    }

    /**
     * @inheritdoc
     */
    public function replace(
        ColumnMetadata $columnMetadata,
        string $searchTerm,
        string $replaceTerm,
        string $subject
    ): FieldReplaceResult {
        $matchCount = substr_count($subject, $searchTerm);
        $errorList = [];
        if (0 === $matchCount) {
            $errorList[] = 'Search term "' . $searchTerm . '" not found in subject "' . $subject . '"';
        }

        return new FieldReplaceResult(
            $columnMetadata,
            $matchCount,
            $errorList,
            $subject,
            str_replace($searchTerm, $replaceTerm, $subject)
        );
    }
}