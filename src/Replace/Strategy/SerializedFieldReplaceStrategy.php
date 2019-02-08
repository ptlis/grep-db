<?php declare(strict_types=1);

/**
 * @copyright   (c) 2017-present brian ridley
 * @author      brian ridley <ptlis@ptlis.net>
 * @license     http://opensource.org/licenses/MIT MIT
 */

namespace ptlis\GrepDb\Replace\Strategy;

use ptlis\GrepDb\Metadata\MySQL\ColumnMetadata;
use ptlis\GrepDb\Replace\Result\FieldReplaceResult;
use ptlis\SerializedDataEditor\Editor;

/**
 * Perform replacement on a PHP-serialized string.
 */
final class SerializedFieldReplaceStrategy implements FieldReplaceStrategy
{
    /**
     * @inheritdoc
     */
    public function canReplace(string $searchTerm, string $subject): bool
    {
        $editor = new Editor();

        try {
            return $editor->containsCount($subject, $searchTerm) > 0;
        } catch (\Throwable $e) {
            return false;
        }
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
        $editor = new Editor();

        try {
            $matchCount = $editor->containsCount($subject, $searchTerm);
            $errorList = [];

            if (0 === $matchCount) {
                $errorList[] = 'Search term "' . $searchTerm . '" not found in subject "' . $subject . '"';
            }

            return new FieldReplaceResult(
                $columnMetadata,
                $matchCount,
                $errorList,
                $subject,
                $editor->replace($subject, $searchTerm, $replaceTerm)
            );
        } catch (\Throwable $e) {
            return new FieldReplaceResult(
                $columnMetadata,
                0,
                ['Failed to deserialize field'],
                $subject,
                $subject
            );
        }
    }
}