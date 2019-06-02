<?php declare(strict_types=1);

/**
 * @copyright   (c) 2017-present brian ridley
 * @author      brian ridley <ptlis@ptlis.net>
 * @license     http://opensource.org/licenses/MIT MIT
 */

namespace ptlis\GrepDb\Metadata\MySQL\Parser;

/**
 * Tokenizer for mySQL dump files.
 *
 * @todo Handle different newlines (e.g. windows' \r\n, unix's \n)
 */
final class Tokenizer
{
    private const DEFAULT_DELIMITER = ';';

    private const KEYWORDS = [
        'AUTO_INCREMENT',
        'COLLATE',
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
        'UNSIGNED',
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

    private const VARIABLE_TYPES = [
        'GLOBAL',
        'LOCAL',
        'SESSION'
    ];

    /**
     * Tokenize the specified SQL file.
     *
     * Note: Only structural components are tokenized; comments and whitespace are stripped.
     *
     * @return TokenBundle[]
     */
    public function tokenize(string $filePath): \Generator
    {
        $fileHandle = fopen($filePath, 'r');
        if (false === $fileHandle) {
            throw new \RuntimeException('Could not open SQL file "' . $filePath . '"');
        }

        $delimiter = self::DEFAULT_DELIMITER;
        $stringAccumulator = '';
        $rawData = '';

        // Read file a character at a time
        while (($char = fgetc($fileHandle)) !== false) {
            $rawData .= $char;

            // Ignore prefixed line breaks
            if (strlen($stringAccumulator) > 0 || strlen($stringAccumulator) == 0 && "\n" !== $char) {
                $stringAccumulator .= $char;
            }

            switch (true) {
                // Single-line comment that begin at start of line
                // TODO: Handle inline in statement
                case '--' === $stringAccumulator:
                case '#' === $stringAccumulator:
                    $this->skipToEndOfLine($rawData, $fileHandle);
                    yield new TokenBundle($rawData);
                    $stringAccumulator = '';
                    $rawData = '';
                    break;

                // Multi-line comments that begin at start of line
                // TODO: Handle inline in statement
                case '/*' === $stringAccumulator:
                    $this->skipToAfterClosingComment($rawData, $fileHandle, $delimiter);
                    yield new TokenBundle($rawData);
                    $stringAccumulator = '';
                    $rawData = '';
                    break;

                // We're not in a comment but we have data; we're in a statement
                case strlen($stringAccumulator) > 2:
                    yield $this->parseStatement($rawData, $stringAccumulator, $fileHandle, $delimiter);
                    $stringAccumulator = '';
                    $rawData = '';
                    break;
            }
        }
    }

    /**
     * Tokenizes a mySQL statement into an array of tokens.
     */
    private function parseStatement(string $rawData, string $accumulator, $fileHandle, $delimiter): TokenBundle
    {
        /** @var Token[] $tokenList */
        $tokenList = [];
        $statementComplete = false;

        while (($char = fgetc($fileHandle)) !== false) {
            $rawData .= $char;

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
                        $tokenList = array_merge(
                            $tokenList,
                            $this->parseTypesAndKeywords($statementString)
                        );
                    }
                    $statementComplete = true;
                    $accumulator = '';
                    break;

                // Numerical value (e.g. in an INSERT statement)
                case 0 === strlen($accumulator) && is_numeric($char):
                    $tokenList = array_merge($tokenList, $this->readNumber($rawData, $fileHandle, $char));
                    break;

                // String value (e.g. in an INSERT statement)
                case 0 === strlen($accumulator) && in_array($char, ['"', '\'']):
                    $tokenList[] = $this->readQuotedString($rawData, $fileHandle, $char);
                    break;

                // NULL value (e.g. in an INSERT statement)
                case count($tokenList) > 0 && 'INSERT' === $tokenList[0]->getValue() && 0 === strlen($accumulator) && 'N' === $char:
                    $accumulator = $char;
                    $accumulator .= fgetc($fileHandle);
                    $accumulator .= fgetc($fileHandle);
                    $accumulator .= fgetc($fileHandle);
                    if ('NULL' !== $accumulator) {
                        throw new \RuntimeException('Something has gone wrong, please submit a bug report to https://github.com/ptlis/grep-db/issues with an example schema');
                    }
                    $rawData .= 'ULL';
                    $accumulator = '';
                    $tokenList[] = new Token(Token::VALUE_NULL, 'NULL');
                    break;

                // mySQL quoted string
                case '`' === $char:
                    $tokenList[] = $this->readMysqlQuotedString($rawData, $fileHandle);
                    $accumulator = '';
                    break;

                // Trailing comma
                case 0 === strlen($accumulator) && ',' === $char;
                case ',' === $accumulator && ' ' === $char:
                    $tokenList[] = new Token(Token::COMMA_SEPARATOR, ',');
                    break;

                // End of mySQL keyword or data type
                case strlen($accumulator) > 1 && ' ' === $char:
                    if ('SET' === $accumulator) {
                        $tokenList = array_merge($tokenList, $this->parseVariableAssignment($rawData, $fileHandle));
                        $statementComplete = true;
                    } else {
                        $tokenList = array_merge($tokenList, $this->parseTypesAndKeywords($accumulator));

                        // Collate statement is always followed by a collation
                        if ('COLLATE' === $tokenList[count($tokenList) - 1]->getValue()) {
                            $tokenList = array_merge($tokenList, $this->readCollation($rawData, $fileHandle));
                        }
                    }
                    $accumulator = '';
                    break;

                // Open parenthesis
                case 0 === strlen($accumulator) && '(' === $char:
                    $tokenList[] = new Token(Token::PARENTHESIS_OPEN, $char);
                    break;

                // Close parenthesis
                case 0 === strlen($accumulator) && ')' === $char:
                    $tokenList[] = new Token(Token::PARENTHESIS_CLOSE, $char);
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

        return new TokenBundle($rawData, $tokenList);
    }

    /**
     * Read the collation of a column.
     *
     * @return Token[]
     */
    private function readCollation(string &$rawData, $fileHandle): array
    {
        $tokenList = [];
        $accumulator = '';
        while (($char = fgetc($fileHandle)) !== false) {
            $rawData .= $char;
            if (' ' === $char) {
                $tokenList[] = new Token(Token::MYSQL_COLLATION, $accumulator);
                break;

            } else if (',' === $char) {
                $tokenList[] = new Token(Token::MYSQL_COLLATION, $accumulator);
                $tokenList[] = new Token(Token::COMMA_SEPARATOR, ',');
                break;

            } else {
                $accumulator .= $char;
            }
        }
        return $tokenList;
    }

    /**
     * Reads a quoted string until the matching end quote is met.
     */
    private function readQuotedString(string &$rawData, $fileHandle, string $quoteType): Token
    {
        $accumulator = '';
        while (($char = fgetc($fileHandle)) !== false) {
            $rawData .= $char;

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
    private function readNumber(string &$rawData, $fileHandle, string $accumulator): array
    {
        $tokenList = [];

        while (($char = fgetc($fileHandle)) !== false) {
            $rawData .= $char;
            if (',' === $char) {
                $tokenList[] = new Token(Token::VALUE_NUMBER, $accumulator);
                $tokenList[] = new Token(Token::COMMA_SEPARATOR, ',');
                break;
            }
            if (')' === $char) {
                $tokenList[] = new Token(Token::VALUE_NUMBER, $accumulator);
                $tokenList[] = new Token(Token::PARENTHESIS_CLOSE, ')');
                break;
            }

            $accumulator .= $char;
        }

        return $tokenList;
    }

    /**
     * Read a mysql-quoted string (i.e. table or column name wrapped in backticks)
     */
    private function readMysqlQuotedString(string &$rawData, $fileHandle): Token
    {
        $string = '';
        while (($char = fgetc($fileHandle)) !== false) {
            $rawData .= $char;
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
        $tokenList = [];

        // Handle preceding comma
        if (',' === substr($accumulator, 0, 1)) {
            $accumulator = substr($accumulator, 1);
            $tokenList[] = new Token(Token::COMMA_SEPARATOR, ',');
        }

        // Find out if this contains a trailing comma
        $trailingComma = false;
        if (',' === substr($accumulator, -1)) {
            $trailingComma = true;
            $accumulator = substr($accumulator, 0, -1);
        }

        if ($this->isMySqlDataType($accumulator)) {
            $tokenList[] = new Token(Token::DATA_TYPE, $accumulator);

        } else if ($this->isKeyValue($accumulator)) {
            $tokenList[] = new Token(Token::KEY_VALUE, $accumulator);

        } else {
            $tokenList[] = $this->getKeywordToken($accumulator);
        }

        // Trailing comma
        if ($trailingComma) {
            $tokenList[] = new Token(Token::COMMA_SEPARATOR, ',');
        }

        return $tokenList;
    }

    /**
     * Parse a variable assignment statement.
     *
     * Note: This won't parse arbitrary SET statements, but will work with those present in mySQL dump files.
     *
     * @return Token[]
     */
    private function parseVariableAssignment(string &$rawData, $fileHandle): array
    {
        $tokenList = [
            new Token(Token::KEYWORD, 'SET')
        ];

        $accumulator = '';
        $statementComplete = false;
        while (($char = fgetc($fileHandle)) !== false) {
            $rawData .= $char;
            switch ($char) {
                case ' ':
                    if (strlen($accumulator)) {
                        $tokenList[] = $this->parseVariableComponent($accumulator);
                        $accumulator = '';
                    }
                    break;

                case ',':
                    if (strlen($accumulator)) {
                        $tokenList[] = $this->parseVariableComponent($accumulator);
                        $accumulator = '';
                    }
                    $tokenList[] = new Token(Token::COMMA_SEPARATOR, ',');
                    break;

                case '=':
                    if (strlen($accumulator)) {
                        $tokenList[] = $this->parseVariableComponent($accumulator);
                        $accumulator = '';
                    }
                    $tokenList[] = new Token(Token::MYSQL_VARIABLE_ASSIGNMENT, '=');
                    break;

                case ';':
                    if (strlen($accumulator)) {
                        $tokenList[] = $this->parseVariableComponent($accumulator);
                        $accumulator = '';
                    }
                    $statementComplete = true;
                    break;

                default:
                    $accumulator .= $char;
                    break;
            }

            if ($statementComplete) {
                break;
            }
        }

        return $tokenList;
    }

    private function parseVariableComponent(string $component): Token
    {
        $token = null;

        // One of GLOBAL, SESSION or LOCAL
        if (in_array(strtoupper($component), self::VARIABLE_TYPES)) {
            $token = new Token(Token::MYSQL_VARIABLE_TYPE, strtoupper($component));

        // Default value
        } else if ('DEFAULT' === $component) {
            $token = new Token(Token::KEYWORD, 'DEFAULT');

        // Numeric value
        } else if (is_numeric($component)) {
            $token = new Token(Token::VALUE_NUMBER, $component);

        // String value (single quotes)
        } else if ("'" === $component[0] && "'" === $component[strlen($component) - 1]) {
            $token = new Token(Token::VALUE_STRING, trim($component, "'"));

        // String value (double quotes)
        } else if ('"' === $component[0] && '"' === $component[strlen($component) - 1]) {
            $token = new Token(Token::VALUE_STRING, trim($component, '"'));

        // Variable identifier
        } else {
            $token = new Token(Token::MYSQL_VARIABLE, $component);
        }

        return $token;
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
    private function skipToEndOfLine(string &$rawData, $fileHandle): void
    {
        while (($char = fgetc($fileHandle)) !== false) {
            $rawData .= $char;
            if ("\n" === $char) {
                break;
            }
        }
    }

    /**
     * Move the file pointer to the after the closing component of a multi-line comment. Used to process multi-line
     * comments.
     */
    private function skipToAfterClosingComment(string &$rawData, $fileHandle, string $delimiter): void
    {
        $expectDelimiter = false;

        $accumulator = '';
        while (($char = fgetc($fileHandle)) !== false) {
            $rawData .= $char;
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
                $rawData .= $char;
                $charsRead++;

                if ($charsRead === strlen($delimiter)) {
                    break;
                }
            }
        }
    }
}
