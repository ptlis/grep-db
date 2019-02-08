<?php declare(strict_types=1);

/**
 * @copyright   (c) 2017-present brian ridley
 * @author      brian ridley <ptlis@ptlis.net>
 * @license     http://opensource.org/licenses/MIT MIT
 */

namespace ptlis\GrepDb\Search\Result;

use ptlis\GrepDb\Metadata\MySQL\ColumnMetadata;
use ptlis\GrepDb\Metadata\MySQL\TableMetadata;

/**
 * DTO representing a row with matched fields.
 */
final class RowSearchResult
{
    /** @var FieldSearchResult[] */
    private $fieldMatchList = [];

    /** @var null|ColumnMetadata */
    private $primaryKeyColumn;

    /** @var mixed|null */
    private $primaryKeyValue;

    /** @var TableMetadata */
    private $tableMetadata;


    /**
     * @param TableMetadata $tableMetadata
     * @param FieldSearchResult[] $fieldMatchList
     * @param ColumnMetadata|null $primaryKeyColumn
     * @param mixed|null $primaryKeyValue
     */
    public function __construct(
        TableMetadata $tableMetadata,
        array $fieldMatchList,
        ?ColumnMetadata $primaryKeyColumn = null,
        $primaryKeyValue = null
    ) {
        $this->tableMetadata = $tableMetadata;
        foreach ($fieldMatchList as $fieldMatch) {
            $this->fieldMatchList[$fieldMatch->getMetadata()->getColumnName()] = $fieldMatch;
        }
        $this->primaryKeyColumn = $primaryKeyColumn;
        $this->primaryKeyValue = $primaryKeyValue;
    }

    public function getTableMetadata(): TableMetadata
    {
        return $this->tableMetadata;
    }

    /**
     * Get array of matching columns in this row.
     *
     * @return FieldSearchResult[]
     */
    public function getMatchingFields(): array
    {
        return $this->fieldMatchList;
    }

    /**
     * Returns true if the column result exists.
     */
    public function hasColumnResult(string $columnName): bool
    {
        return array_key_exists($columnName, $this->fieldMatchList);
    }

    /**
     * Returns the column result matching the passed name.
     */
    public function getColumnResult(string $columnName): FieldSearchResult
    {
        if (!$this->hasColumnResult($columnName)) {
            throw new \RuntimeException('Could not find changed column named "' . $columnName . '"');
        }

        return $this->fieldMatchList[$columnName];
    }

    /**
     * Returns the primary key column's metadata.
     *
     * @throws \RuntimeException If the result doesn't have a primary key
     */
    public function getPrimaryKeyColumn(): ?ColumnMetadata
    {
        return $this->primaryKeyColumn;
    }

    /**
     * Returns the primary key value.
     *
     * @throws \RuntimeException If the result doesn't have a primary key
     * @return mixed|null
     */
    public function getPrimaryKeyValue()
    {
        return $this->primaryKeyValue;
    }
}