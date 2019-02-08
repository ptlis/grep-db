<?php declare(strict_types=1);

/**
 * @copyright   (c) 2017-present brian ridley
 * @author      brian ridley <ptlis@ptlis.net>
 * @license     http://opensource.org/licenses/MIT MIT
 */

namespace ptlis\GrepDb\Search\Result;

use ptlis\GrepDb\Metadata\MySQL\ColumnMetadata;

/**
 * DTO representing a matched field.
 */
final class FieldSearchResult
{
    /** @var ColumnMetadata */
    private $column;

    /** @var string */
    private $value;


    public function __construct(
        ColumnMetadata $column,
        string $value
    ) {
        $this->column = $column;
        $this->value = $value;
    }

    public function getMetadata(): ColumnMetadata
    {
        return $this->column;
    }

    public function getValue(): string
    {
        return $this->value;
    }
}