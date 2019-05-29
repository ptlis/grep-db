<?php declare(strict_types=1);

/**
 * @copyright   (c) 2017-present brian ridley
 * @author      brian ridley <ptlis@ptlis.net>
 * @license     http://opensource.org/licenses/MIT MIT
 */

namespace ptlis\GrepDb\Metadata\MySQL\Parser;

/**
 * Tokenizer for mySQL dump files.
 */
final class Tokenizer
{
    private const DEFAULT_DELIMITER = ';';

    private const KEYWORDS = [
        'AUTO_INCREMENT',
        'CREATE',
        'DEFAULT',
        'DROP',
        'EXISTS',
        'IF',
        'INSERT',
        'INTO',
        'KEY',
        'LOCK',
        'NOT',
        'NULL',
        'PRIMARY',
        'TABLE',
        'TABLES',
        'UNIQUE',
        'UNLOCK',
        'VALUES',
        'WRITE'
    ];

    private const DATA_TYPES = [
        'BIT',
        'TINYINT',
        'BOOL',
        'BOOLEAN',
        'SMALLINT',
        'MEDIUMINT',
        'INT',
        'BIGINT',
        'DECIMAL',
        'FLOAT',
        'DATE',
        'DATETIME',
        'TIMESTAMP',
        'TIME',
        'YEAR',
        'CHAR',
        'VARCHAR',
        'BINARY',
        'VARBINARY',
        'TINYBLOB',
        'TINYTEXT',
        'BLOB',
        'TEXT',
        'MEDIUMBLOB',
        'MEDIUMTEXT',
        'LONGBLOB',
        'LONGTEXT',
        'ENUM',
        'SET',
        'DOUBLE'
    ];

    /**
     * Tokenize the specified SQL file.
     *
     * Note: Only structural components are tokenized; comments and whitespace are stripped.
     *
     * @return Token[][]
     */
    public function tokenize(string $filePath): \Generator
    {
        $fileHandle = fopen($filePath, 'r');
        if (false === $fileHandle) {
            throw new \RuntimeException('Could not open SQL file "' . $filePath . '"');
        }

        $delimiter = self::DEFAULT_DELIMITER;
        $stringAccumulator = '';

        // Read file a character at a time
        while (($char = fgetc($fileHandle)) !== false) {

            // Ignore prefixed line breaks
            if (strlen($stringAccumulator) > 0 || strlen($stringAccumulator) == 0 && "\n" !== $char) {
                $stringAccumulator .= $char;
            }

            switch (true) {
                // Single-line comment that begin at start of line
                // TODO: Handle inline in statement
                case '--' === $stringAccumulator:
                case '#' === $stringAccumulator:
                    $this->skipToEndOfLine($fileHandle);
                    $stringAccumulator = '';
                    break;

                // Multi-line comments that begin at start of line
                // TODO: Handle inline in statement
                case '/*' === $stringAccumulator:
                    $this->skipToAfterClosingComment($fileHandle, $delimiter);
                    $stringAccumulator = '';
                    break;

                // We're not in a comment but we have data; we're in a statement
                case strlen($stringAccumulator) > 2:
                    yield $this->parseStatement($stringAccumulator, $fileHandle, $delimiter);
                    $stringAccumulator = '';
                    break;
            }
        }
    }

    /**
     * Tokenizes a mySQL statement into an array of tokens.
     *
     * @return Token[]
     */
    private function parseStatement(string $accumulator, $fileHandle, $delimiter): array
    {
        /** @var Token[] $tokens */
        $tokens = [];
        $statementComplete = false;

        while (($char = fgetc($fileHandle)) !== false) {
            switch (true) {
                // Ignore line breaks
                case "\n" === $char:
                    // Do nothing
                    break;

                // Delimiter, end of statement
                case $delimiter === substr($accumulator . $char, -(strlen($delimiter))):
                    $accumulator .= $char;
                    $statementString = substr($accumulator, 0, strlen($accumulator) - strlen($delimiter));
                    if (strlen($statementString)) {
                        $tokens = array_merge(
                            $tokens,
                            $this->parseTypesAndKeywords($statementString)
                        );
                    }
                    $statementComplete = true;
                    $accumulator = '';
                    break;

                // Numerical value (e.g. in an INSERT statement)
                case 0 === strlen($accumulator) && is_numeric($char):
                    $tokens = array_merge($tokens, $this->readNumber($fileHandle, $char));
                    break;

                // String value (e.g. in an INSERT statement)
                case 0 === strlen($accumulator) && in_array($char, ['"', '\'']):
                    $tokens[] = $this->readString($fileHandle, $char);
                    break;

                // NULL value (e.g. in an INSERT statement)
                case count($tokens) > 0 && 'INSERT' === $tokens[0]->getValue() && 0 === strlen($accumulator) && 'N' === $char:
                    $accumulator = $char;
                    $accumulator .= fgetc($fileHandle);
                    $accumulator .= fgetc($fileHandle);
                    $accumulator .= fgetc($fileHandle);
                    if ('NULL' !== $accumulator) {
                        throw new \RuntimeException('Something has gone wrong, please submit a bug report to https://github.com/ptlis/grep-db/issues with an example schema');
                    }
                    $accumulator = '';
                    $tokens[] = new Token(Token::VALUE_NULL, 'NULL');
                    break;

                // mySQL quoted string
                case '`' === $char:
                    $tokens[] = $this->readMysqlQuotedString($fileHandle);
                    $accumulator = '';
                    break;

                // Trailing comma
                case 0 === strlen($accumulator) && ',' === $char;
                case ',' === $accumulator && ' ' === $char:
                    $tokens[] = new Token(Token::COMMA_SEPARATOR, ',');
                    break;

                // End of mySQL keyword or data type
                case strlen($accumulator) > 1 && ' ' === $char:
                    $tokens = array_merge($tokens, $this->parseTypesAndKeywords($accumulator));
                    $accumulator = '';
                    break;

                // Open parenthesis
                case 0 === strlen($accumulator) && '(' === $char:
                    $tokens[] = new Token(Token::PARENTHESIS_OPEN, $char);
                    break;

                // Close parenthesis
                case 0 === strlen($accumulator) && ')' === $char:
                    $tokens[] = new Token(Token::PARENTHESIS_CLOSE, $char);
                    break;

                // Ignore spaces, otherwise accumulate
                case ' ' !== $char:
                    $accumulator .= $char;
                    break;
            }

            if ($statementComplete) {
                break;
            }
        }

        return $tokens;
    }

    /**
     * Reads a quoted string until the matching end quote is met.
     */
    private function readString($fileHandle, string $quoteType): Token
    {
        $accumulator = '';
        while (($char = fgetc($fileHandle)) !== false) {

            // Read until closing quote
            if (
                $quoteType === $char
                && (
                    !strlen($accumulator)
                    || (strlen($accumulator) && '\\' !== substr($accumulator, -1))
                )
            ) {
                break;
            }

            $accumulator .= $char;
        }

        return new Token(Token::VALUE_STRING, $accumulator);
    }

    /**
     * Read a number (both floats and integers).
     *
     * @return Token[]
     */
    private function readNumber($fileHandle, string $accumulator): array
    {
        $tokens = [];

        while (($char = fgetc($fileHandle)) !== false) {
            if (',' === $char) {
                $tokens[] = new Token(Token::VALUE_NUMBER, $accumulator);
                $tokens[] = new Token(Token::COMMA_SEPARATOR, ',');
                break;
            }
            if (')' === $char) {
                $tokens[] = new Token(Token::VALUE_NUMBER, $accumulator);
                $tokens[] = new Token(Token::PARENTHESIS_CLOSE, ')');
                break;
            }

            $accumulator .= $char;
        }

        return $tokens;
    }

    /**
     * Read a mysql-quoted string (i.e. table or column name wrapped in backticks)
     */
    private function readMysqlQuotedString($fileHandle): Token
    {
        $string = '';
        while (($char = fgetc($fileHandle)) !== false) {
            if ('`' === $char) {
                break;
            }

            $string .= $char;
        }

        return new Token(Token::MYSQL_QUOTED_STRING, $string);
    }

    /**
     * Parses mysql datatypes and keywords.
     *
     * @return Token[]
     */
    private function parseTypesAndKeywords(string $accumulator): array
    {
        $tokens = [];

        // Handle preceding comma
        if (',' === substr($accumulator, 0, 1)) {
            $accumulator = substr($accumulator, 1);
            $tokens[] = new Token(Token::COMMA_SEPARATOR, ',');
        }

        // Find out if this contains a trailing comma
        $trailingComma = false;
        if (',' === substr($accumulator, -1)) {
            $trailingComma = true;
            $accumulator = substr($accumulator, 0, -1);
        }

        if ($this->isMySqlDataType($accumulator)) {
            $tokens[] = new Token(Token::DATA_TYPE, $accumulator);

        } else if ($this->isKeyValue($accumulator)) {
            $tokens[] = new Token(Token::KEY_VALUE, $accumulator);

        } else {
            $tokens[] = $this->getKeywordToken($accumulator);
        }

        // Trailing comma
        if ($trailingComma) {
            $tokens[] = new Token(Token::COMMA_SEPARATOR, ',');
        }

        return $tokens;
    }

    /**
     * Returns true if the passed value is a mySQL data type.
     */
    private function isMySqlDataType(string $possibleType): bool
    {
        $parts = explode('(', $possibleType);

        return in_array(strtoupper($parts[0]), self::DATA_TYPES);
    }

    /**
     * Returns true if the passed value is a key-value pair (i.e. table options)
     */
    private function isKeyValue(string $possibleKeyValue): bool
    {
        $parts = explode('=', $possibleKeyValue);

        return 2 === count($parts);
    }

    /**
     * Builds a token for a mySQL keyword.
     */
    private function getKeywordToken(string $keyword): Token
    {
        if (!in_array(strtoupper($keyword), self::KEYWORDS)) {
            throw new \RuntimeException('Unknown keyword "' . $keyword . '" encountered');
        }

        return new Token(Token::KEYWORD, strtoupper($keyword));
    }

    /**
     * Move the file pointer to the start of the next line. Used to process single-line comments.
     */
    private function skipToEndOfLine($fileHandle): void
    {
        while (($char = fgetc($fileHandle)) !== false) {
            if ("\n" === $char) {
                break;
            }
        }
    }

    /**
     * Move the file pointer to the after the closing component of a multi-line comment. Used to process multi-line
     * comments.
     */
    private function skipToAfterClosingComment($fileHandle, string $delimiter): void
    {
        $expectDelimiter = false;

        $accumulator = '';
        while (($char = fgetc($fileHandle)) !== false) {
            $accumulator .= $char;

            // Figure out if we're in a mySQL executable comment, or a optimizer hint
            if (1 === strlen($accumulator)) {
                $expectDelimiter = in_array($accumulator, ['!', '+']);
            }

            if (strlen($accumulator) >= 2 && '*/' === substr($accumulator, -2)) {
                break;
            }
        }

        // When a delimiter is expected we must skip over that too
        if ($expectDelimiter) {
            $charsRead = 0;
            while (($char = fgetc($fileHandle)) !== false) {
                $charsRead++;

                if ($charsRead === strlen($delimiter)) {
                    break;
                }
            }
        }
    }
}
