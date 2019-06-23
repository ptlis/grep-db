<?php declare(strict_types=1);

/**
 * @copyright   (c) 2017-present brian ridley
 * @author      brian ridley <ptlis@ptlis.net>
 * @license     http://opensource.org/licenses/MIT MIT
 */

namespace ptlis\GrepDb\Metadata\MySQL\Parser;

/**
 * Wraps the logic to read a file line-by-line while exposing a character-by-character interface.
 *
 * This is a necessary file optimisation.
 */
final class FileReader
{
    /** @var resource */
    private $fileHandle;

    /** @var string|null */
    private $line = null;

    /** @var int */
    private $charIndex = 0;

    /** @var bool */
    private $readComplete = false;

    public function __construct(
        string $filePath
    ) {
        $this->fileHandle = fopen($filePath, 'r');
        if (false === $this->fileHandle) {
            throw new \RuntimeException('Could not open SQL file "' . $filePath . '"');
        }
    }

    /**
     * Returns a character from the file or false on read complete.
     *
     * @return string|bool
     */
    public function readChar()
    {
        // Return false on file read already being complete
        if ($this->readComplete) {
            return false;
        }

        // Read a new line from the file
        if (null === $this->line || $this->charIndex >= strlen($this->line)) {

            $line = fgets($this->fileHandle);
            // Track reaching end of file and return false
            if (false === $line) {
                $this->readComplete = true;
                return false;
            }

            $this->line = $line;
            $this->charIndex = 0;
        }

        $char = $this->line[$this->charIndex];
        $this->charIndex++;
        return $char;
    }
}
