<?php declare(strict_types=1);

/**
 * @copyright   (c) 2017-present brian ridley
 * @author      brian ridley <ptlis@ptlis.net>
 * @license     http://opensource.org/licenses/MIT MIT
 */

namespace ptlis\GrepDb\Metadata\MySQL\Parser;

use ptlis\GrepDb\Metadata\MySQL\ColumnMetadata;
use ptlis\GrepDb\Metadata\MySQL\TableMetadata;

final class Parser
{
    /** @var Tokenizer */
    private $tokenizer;

    public function __construct(
        Tokenizer $tokenizer
    ) {
        $this->tokenizer = $tokenizer;
    }

    /**
     * @return TableMetadata[]
     */
    public function parseAllTableMetadata(string $filePath): \Generator
    {
        /** @var TokenBundle $tokenBundle */
        foreach ($this->tokenizer->tokenize($filePath) as $tokenBundle) {
            if ($this->isCreateTableStatement($tokenBundle)) {
                yield $this->parseSingleTableMetadata($filePath, $tokenBundle);
            }
        }
    }

    private function parseSingleTableMetadata(string $filePath, TokenBundle $tokenBundle): TableMetadata
    {
        $tokenList = $tokenBundle->getTokens();
        $tableName = $tokenList[2]->getValue();

        // Figure out where close offset is
        $closeOffset = -1;
        for ($i = count($tokenList) - 1; $i >= 0; $i--) {
            if (Token::PARENTHESIS_CLOSE === $tokenList[$i]->getType()) {
                $closeOffset = $i;
                break;
            }
        }

        // Read table metadata
        $engine = 'DEFAULT';
        $collation = 'DEFAULT';
        $charset = 'DEFAULT';
        for ($i = $closeOffset; $i < count($tokenList); $i++) {
            $token = $tokenList[$i];
            if (Token::KEY_VALUE === $token->getType()) {
                $parts = explode('=', $token->getValue());

                if ('ENGINE' === strtoupper($parts[0])) {
                    $engine = $parts[1];
                }

                if ('CHARSET' === strtoupper($parts[0])) {
                    $charset = $parts[1];
                }

                if ('COLLATION' === strtoupper($parts[0]) || 'COLLATE' === strtoupper($parts[0])) {
                    $collation = $parts[1];
                }
            }
        }

        // Read column definition tokens
        $columnMetadataTokenList = array_slice($tokenList, 4, count($tokenList) - 4 - (count($tokenList) - $closeOffset));

        return new TableMetadata(
            $filePath,
            $tableName,
            $engine,
            $collation,
            $charset,
            -1,
            $this->parseAllColumnMetadata($filePath, $tableName, $columnMetadataTokenList)
        );
    }

    /**
     * @param Token[] $allColumnsTokenList
     * @return ColumnMetadata[]
     */
    private function parseAllColumnMetadata(string $filePath, string $tableName, array $allColumnsTokenList): array
    {
        $columnTokenListList = $this->splitOnCommas($allColumnsTokenList);
        $primaryKeyList = $this->getPrimaryKeys($columnTokenListList);
        $indexedColumnList = $this->getIndexedColumns($columnTokenListList);
        $columnList = [];
        foreach ($columnTokenListList as $columnTokenList) {
            if (count($columnTokenList) > 0 && Token::MYSQL_QUOTED_STRING === $columnTokenList[0]->getType()) {
                $columnList[] = $this->parseColumnMetadata($filePath, $tableName, $columnTokenList, $primaryKeyList, $indexedColumnList);
            }
        }
        return $columnList;
    }

    /**
     * @param Token[] $columnTokenList
     * @return ColumnMetadata
     */
    private function parseColumnMetadata(
        string $filePath,
        string $tableName,
        array $columnTokenList,
        array $primaryKeyList,
        array $indexedColumnList
    ): ColumnMetadata {
        $columnName = $columnTokenList[0]->getValue();
        $columnType = $columnTokenList[1]->getValue();

        return new ColumnMetadata(
            $filePath,
            $tableName,
            $columnName,
            $columnType,
            $this->getMaxLength($columnType),
            in_array($columnName, $primaryKeyList),
            !$this->isNotNull($columnTokenList),
            in_array($columnName, $indexedColumnList) || in_array($columnName, $primaryKeyList)
        );
    }

    private function getMaxLength(string $columnType): ?int
    {
        $maxLength = null;
        // TODO: This is incomplete & needs to handle all column types that have max lengths
        switch (true) {
            case 'VARCHAR' === strtoupper(substr($columnType, 0, 7)):
                $maxLength = intval(substr(substr($columnType, 8), 0, -1));
                break;

            case 'TEXT' === strtoupper($columnType):
            case 'BLOB' === strtoupper($columnType):
                $maxLength = 65535;
                break;
        }
        return $maxLength;
    }

    /**
     * Splits an array of tokens on comma seperators.
     *
     * @param Token[] $tokenList
     * @return Token[][]
     */
    private function splitOnCommas(array $tokenList): array
    {
        $columnTokensList = [];
        $tokensAccumulator = [];
        for ($i = 0; $i < count($tokenList); $i++) {
            $token = $tokenList[$i];

            // Split on column unless the comma is preceded by a mysql-quoted string (in which case it's a compound key)
            if (
                Token::COMMA_SEPARATOR === $token->getType()
                && (
                    $i > 0 && $tokenList[$i - 1]->getType() !== Token::MYSQL_QUOTED_STRING
                )
            ) {
                $columnTokensList[] = $tokensAccumulator;
                $tokensAccumulator = [];
            } else {
                $tokensAccumulator[] = $token;
            }
        }

        if (count($tokensAccumulator) > 0) {
            $columnTokensList[] = $tokensAccumulator;
        }

        return $columnTokensList;
    }

    /**
     * Returns an array of primary keys.
     *
     * @param Token[][] $columnTokenListList
     * @return string[]
     */
    private function getPrimaryKeys(array $columnTokenListList): array
    {
        $primaryKeyList = [];
        foreach ($columnTokenListList as $columnTokenList) {
            if ($this->isPrimaryKeyTokens($columnTokenList)) {
                $primaryKeyList = array_merge($primaryKeyList, $this->getMysqlQuotedStringValues($columnTokenList));
            }
        }
        return $primaryKeyList;
    }

    /**
     * Returns an array of indexed columns.
     *
     * @param Token[][] $columnTokenListList
     * @return string[]
     */
    private function getIndexedColumns(array $columnTokenListList): array
    {
        $indexedColumnList = [];
        foreach ($columnTokenListList as $columnTokenList) {
            if ($this->isUniqueTokens($columnTokenList)) {
                $indexedColumnList = array_merge(
                    $indexedColumnList,
                    $this->getMysqlQuotedStringValues(array_slice($columnTokenList, 3))
                );
            } else if ($this->isIndexTokens($columnTokenList)) {
                $indexedColumnList = array_merge(
                    $indexedColumnList,
                    $this->getMysqlQuotedStringValues(array_slice($columnTokenList, 2))
                );
            }
        }
        return array_unique($indexedColumnList);
    }

    /**
     * Returns all values of tokens that are mySQL quoted strings.
     *
     * @param Token[] $columnTokenList
     * @return string[]
     */
    private function getMysqlQuotedStringValues(array $columnTokenList): array
    {
        $quotedStringList = [];
        foreach ($columnTokenList as $columnToken) {
            if (Token::MYSQL_QUOTED_STRING === $columnToken->getType()) {
                $quotedStringList[] = $columnToken->getValue();
            }
        }
        return $quotedStringList;
    }

    private function isNotNull(array $columnTokenList): bool
    {
        return (
            (
                count($columnTokenList) > 3
                && $columnTokenList[2]->matches(Token::KEYWORD, 'NOT')
                && $columnTokenList[3]->matches(Token::KEYWORD, 'NULL')
            ) || (
                count($columnTokenList) > 4
                && $columnTokenList[2]->matches(Token::KEYWORD, 'UNSIGNED')
                && $columnTokenList[3]->matches(Token::KEYWORD, 'NOT')
                && $columnTokenList[4]->matches(Token::KEYWORD, 'NULL')
            )
        );
    }

    /**
     * Returns true if the token list defines a primary key.
     *
     * @param Token[] $columnTokenList
     */
    private function isPrimaryKeyTokens(array $columnTokenList): bool
    {
        return (
            count($columnTokenList) > 1
            && $columnTokenList[0]->matches(Token::KEYWORD, 'PRIMARY')
            && $columnTokenList[1]->matches(Token::KEYWORD, 'KEY')
        );
    }

    /**
     * Returns true if the token list defines a unique column.
     *
     * @param Token[] $columnTokenList
     */
    private function isUniqueTokens(array $columnTokenList): bool
    {
        return (
            count($columnTokenList) > 1
            && $columnTokenList[0]->matches(Token::KEYWORD, 'UNIQUE')
            && $columnTokenList[1]->matches(Token::KEYWORD, 'KEY')
        );
    }

    /**
     * Returns true if the token list defines an index.
     *
     * @param Token[] $columnTokenList
     */
    private function isIndexTokens(array $columnTokenList): bool
    {
        return (
            count($columnTokenList) > 0
            && $columnTokenList[0]->matches(Token::KEYWORD, 'KEY')
        );
    }

    /**
     * Returns true if the token list is a create table statement.
     */
    private function isCreateTableStatement(TokenBundle $tokenBundle): bool
    {
        $tokenList = $tokenBundle->getTokens();
        return (
            count($tokenList) > 1
            && $tokenList[0]->matches(Token::KEYWORD, 'CREATE')
            && $tokenList[1]->matches(Token::KEYWORD, 'TABLE')
        );
    }
}
