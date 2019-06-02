<?php declare(strict_types=1);

/**
 * @copyright   (c) 2017-present brian ridley
 * @author      brian ridley <ptlis@ptlis.net>
 * @license     http://opensource.org/licenses/MIT MIT
 */

namespace ptlis\GrepDb\Metadata\MySQL\Parser;

/**
 * A bundle of tokens with the raw data that is associated with it.
 */
final class TokenBundle
{
    /** @var string */
    private $rawData;

    /** @var Token[] */
    private $tokens;

    /**
     * @param Token[] $tokens
     */
    public function __construct(
        string $rawData,
        array $tokens = []
    ) {
        $this->rawData = $rawData;
        $this->tokens = $tokens;
    }

    public function hasTokens(): bool
    {
        return count($this->tokens) > 0;
    }

    /**
     * @return Token[]
     */
    public function getTokens(): array
    {
        return $this->tokens;
    }

    public function __toString(): string
    {
        return $this->rawData;
    }
}
