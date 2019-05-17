<?php declare(strict_types=1);

/**
 * @copyright   (c) 2017-present brian ridley
 * @author      brian ridley <ptlis@ptlis.net>
 * @license     http://opensource.org/licenses/MIT MIT
 */

namespace ptlis\GrepDb\Metadata\MySQL\Parser;

/**
 * A token from a mySQL dump file.
 */
final class Token
{
    public const KEYWORD = 'keyword';
    public const MYSQL_QUOTED_STRING = 'mysql-quoted-string';
    public const PARENTHESIS_OPEN = 'open-parenthesis';
    public const PARENTHESIS_CLOSE = 'close-parenthesis';
    public const DATA_TYPE = 'data-type';
    public const COMMA_SEPARATOR = 'comma-separator';
    public const KEY_VALUE = 'key-value';
    public const VALUE_NUMBER = 'value-number';
    public const VALUE_STRING = 'value-string';
    public const VALUE_NULL = 'value-null';

    /** @var string The token type */
    private $type;

    /** @var string The raw value. */
    private $value;

    public function __construct(string $type, string $value)
    {
        $this->type = $type;
        $this->value = $value;
    }

    /**
     * The token type, should be one of the class constants.
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Get the raw value.
     */
    public function getValue(): string
    {
        return $this->value;
    }
}
