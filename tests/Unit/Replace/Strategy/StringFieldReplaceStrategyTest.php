<?php declare(strict_types=1);

/**
 * @copyright   (c) 2017-present brian ridley
 * @author      brian ridley <ptlis@ptlis.net>
 * @license     http://opensource.org/licenses/MIT MIT
 */

namespace ptlis\GrepDb\Test\Unit\Replace\Strategy;

use PHPUnit\Framework\TestCase;
use ptlis\GrepDb\Metadata\MySQL\ColumnMetadata;
use ptlis\GrepDb\Replace\Strategy\StringFieldReplaceStrategy;

final class StringFieldReplaceStrategyTest extends TestCase
{
    public function testCanReplace(): void
    {
        $replaceStrategy = new StringFieldReplaceStrategy();

        $this->assertTrue($replaceStrategy->canReplace('foo', 'foo bar baz bat'));
    }

    public function testCannotReplace(): void
    {
        $replaceStrategy = new StringFieldReplaceStrategy();

        $this->assertFalse($replaceStrategy->canReplace('qux', 'foo bar baz bat'));
    }

    public function testReplaceOneSuccess(): void
    {
        $replaceStrategy = new StringFieldReplaceStrategy();

        $fieldResult = $replaceStrategy->replace($this->getColumnMetadata(), 'foo', 'qux', 'foo bar baz bat');

        $this->assertEquals('qux bar baz bat', $fieldResult->getNewValue());
        $this->assertEquals(1, $fieldResult->getReplacedCount());
        $this->assertEquals([], $fieldResult->getErrorList());
    }

    public function testReplaceMultipleSuccess(): void
    {
        $replaceStrategy = new StringFieldReplaceStrategy();

        $fieldResult = $replaceStrategy->replace($this->getColumnMetadata(), 'foo', 'qux', 'foo bar baz bat foo');

        $this->assertEquals('qux bar baz bat qux', $fieldResult->getNewValue());
        $this->assertEquals(2, $fieldResult->getReplacedCount());
        $this->assertEquals([], $fieldResult->getErrorList());
    }

    public function testReplaceFailure(): void
    {
        $replaceStrategy = new StringFieldReplaceStrategy();

        $fieldResult = $replaceStrategy->replace($this->getColumnMetadata(), 'qux', 'foo', 'foo bar baz bat');

        $this->assertEquals('foo bar baz bat', $fieldResult->getNewValue());
        $this->assertEquals(0, $fieldResult->getReplacedCount());
        $this->assertEquals(['Search term "qux" not found in subject "foo bar baz bat"'], $fieldResult->getErrorList());
    }

    private function getColumnMetadata(): ColumnMetadata {
        return new ColumnMetadata(
            'my_database',
            'my_table',
            'my_column',
            'VARCHAR(255)',
            255,
            false,
            true,
            false
        );
    }
}