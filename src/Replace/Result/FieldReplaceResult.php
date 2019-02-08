<?php declare(strict_types=1);

/**
 * @copyright   (c) 2017-present brian ridley
 * @author      brian ridley <ptlis@ptlis.net>
 * @license     http://opensource.org/licenses/MIT MIT
 */

namespace ptlis\GrepDb\Replace\Result;

use ptlis\GrepDb\Metadata\MySQL\ColumnMetadata;

/**
 * Result of field replacements.
 */
final class FieldReplaceResult
{
    /** @var ColumnMetadata */
    private $columnMetadata;

    /** @var int */
    private $replacedCount;

    /** @var string[] */
    private $errorList;

    /** @var string */
    private $oldValue;

    /** @var string */
    private $newValue;


    /**
     * @param ColumnMetadata $columnMetadata
     * @param int $replacedCount
     * @param string[] $errorList
     * @param string $newValue
     */
    public function __construct(
        ColumnMetadata $columnMetadata,
        int $replacedCount,
        array $errorList,
        string $oldValue,
        string $newValue
    ) {
        $this->columnMetadata = $columnMetadata;
        $this->replacedCount = $replacedCount;
        $this->errorList = $errorList;
        $this->oldValue = $oldValue;
        $this->newValue = $newValue;
    }

    /**
     * Get the number of replacements in this field.
     */
    public function getReplacedCount(): int
    {
        return $this->replacedCount;
    }

    /**
     * @return string[]
     */
    public function getErrorList(): array
    {
        return $this->errorList;
    }

    public function getOldValue(): string
    {
        return $this->oldValue;
    }

    public function getNewValue(): string
    {
        return $this->newValue;
    }

    public function getColumnMetadata(): ColumnMetadata
    {
        return $this->columnMetadata;
    }
}