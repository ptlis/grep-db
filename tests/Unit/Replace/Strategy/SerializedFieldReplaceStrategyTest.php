<?php declare(strict_types=1);

/**
 * @copyright   (c) 2017-present brian ridley
 * @author      brian ridley <ptlis@ptlis.net>
 * @license     http://opensource.org/licenses/MIT MIT
 */

namespace ptlis\GrepDb\Test\Unit\Replace\Strategy;

use PHPUnit\Framework\TestCase;
use ptlis\GrepDb\Metadata\MySQL\ColumnMetadata;
use ptlis\GrepDb\Replace\Strategy\SerializedFieldReplaceStrategy;

final class SerializedFieldReplaceStrategyTest extends TestCase
{

    public function testCanReplace(): void
    {
        $replaceStrategy = new SerializedFieldReplaceStrategy();

        $this->assertTrue($replaceStrategy->canReplace('bob', 'O:8:"stdClass":2:{s:3:"bar";s:24:"this string contains bob";s:3:"bat";i:1;}'));
    }

    public function testCannotReplaceNotSerialized(): void
    {
        $replaceStrategy = new SerializedFieldReplaceStrategy();

        $this->assertFalse($replaceStrategy->canReplace('bob', 'this string contains bob'));
    }

    public function testCannotReplaceStringNotPresent(): void
    {
        $replaceStrategy = new SerializedFieldReplaceStrategy();

        $this->assertFalse($replaceStrategy->canReplace('sally', 'O:8:"stdClass":2:{s:3:"bar";s:24:"this string contains bob";s:3:"bat";i:1;}'));
    }

    public function testReplaceNotSerializedFailure(): void
    {
        $replaceStrategy = new SerializedFieldReplaceStrategy();

        $fieldResult = $replaceStrategy->replace($this->getColumnMetadata(), 'foo', 'qux', 'foo bar baz bat');

        $this->assertEquals('foo bar baz bat', $fieldResult->getNewValue());
        $this->assertEquals(0, $fieldResult->getReplacedCount());
        $this->assertEquals(['Failed to deserialize field'], $fieldResult->getErrorList());
    }

    public function testReplaceStringOneSuccess(): void
    {
        $replaceStrategy = new SerializedFieldReplaceStrategy();

        $fieldResult = $replaceStrategy->replace($this->getColumnMetadata(), 'foo', 'qux', 's:15:"foo bar baz bat";');

        $this->assertEquals('s:15:"qux bar baz bat";', $fieldResult->getNewValue());
        $this->assertEquals(1, $fieldResult->getReplacedCount());
        $this->assertEquals([], $fieldResult->getErrorList());
    }

    public function testReplaceStringMultipleSuccess(): void
    {
        $replaceStrategy = new SerializedFieldReplaceStrategy();

        $fieldResult = $replaceStrategy->replace($this->getColumnMetadata(), 'foo', 'qux', 's:19:"foo bar baz bat foo";');

        $this->assertEquals('s:19:"qux bar baz bat qux";', $fieldResult->getNewValue());
        $this->assertEquals(2, $fieldResult->getReplacedCount());
        $this->assertEquals([], $fieldResult->getErrorList());
    }

    public function testReplaceStringFailure(): void
    {
        $replaceStrategy = new SerializedFieldReplaceStrategy();

        $fieldResult = $replaceStrategy->replace($this->getColumnMetadata(), 'test', 'qux', 's:19:"foo bar baz bat foo";');

        $this->assertEquals('s:19:"foo bar baz bat foo";', $fieldResult->getNewValue());
        $this->assertEquals(0, $fieldResult->getReplacedCount());
        $this->assertEquals(['Search term "test" not found in subject "s:19:"foo bar baz bat foo";"'], $fieldResult->getErrorList());
    }

    public function testReplaceArrayOneSuccess(): void
    {
        $replaceStrategy = new SerializedFieldReplaceStrategy();

        $fieldResult = $replaceStrategy->replace($this->getColumnMetadata(), 'foo', 'qux', 'a:4:{i:0;s:3:"foo";i:1;s:3:"bar";i:2;s:3:"baz";i:3;s:3:"bat";}');

        $this->assertEquals('a:4:{i:0;s:3:"qux";i:1;s:3:"bar";i:2;s:3:"baz";i:3;s:3:"bat";}', $fieldResult->getNewValue());
        $this->assertEquals(1, $fieldResult->getReplacedCount());
        $this->assertEquals([], $fieldResult->getErrorList());
    }

    public function testReplaceArrayMultipleSuccess(): void
    {
        $replaceStrategy = new SerializedFieldReplaceStrategy();

        $fieldResult = $replaceStrategy->replace($this->getColumnMetadata(), 'foo', 'qux', 'a:5:{i:0;s:3:"foo";i:1;s:3:"bar";i:2;s:3:"baz";i:3;s:3:"bat";i:4;s:6:"foobar";}');

        $this->assertEquals('a:5:{i:0;s:3:"qux";i:1;s:3:"bar";i:2;s:3:"baz";i:3;s:3:"bat";i:4;s:6:"quxbar";}', $fieldResult->getNewValue());
        $this->assertEquals(2, $fieldResult->getReplacedCount());
        $this->assertEquals([], $fieldResult->getErrorList());
    }

    public function testReplaceArrayFailure(): void
    {
        $replaceStrategy = new SerializedFieldReplaceStrategy();

        $fieldResult = $replaceStrategy->replace($this->getColumnMetadata(), 'test', 'qux', 'a:4:{i:0;s:3:"foo";i:1;s:3:"bar";i:2;s:3:"baz";i:3;s:3:"bat";}');

        $this->assertEquals('a:4:{i:0;s:3:"foo";i:1;s:3:"bar";i:2;s:3:"baz";i:3;s:3:"bat";}', $fieldResult->getNewValue());
        $this->assertEquals(0, $fieldResult->getReplacedCount());
        $this->assertEquals(['Search term "test" not found in subject "a:4:{i:0;s:3:"foo";i:1;s:3:"bar";i:2;s:3:"baz";i:3;s:3:"bat";}"'], $fieldResult->getErrorList());
    }

    public function testReplaceObjectOneSuccess(): void
    {
        $replaceStrategy = new SerializedFieldReplaceStrategy();

        $fieldResult = $replaceStrategy->replace($this->getColumnMetadata(), 'foo', 'qux', 'O:8:"stdClass":3:{s:3:"bar";s:24:"this string contains foo";s:3:"baz";s:17:"this is an object";s:3:"bat";i:1;}');

        $this->assertEquals('O:8:"stdClass":3:{s:3:"bar";s:24:"this string contains qux";s:3:"baz";s:17:"this is an object";s:3:"bat";i:1;}', $fieldResult->getNewValue());
        $this->assertEquals(1, $fieldResult->getReplacedCount());
        $this->assertEquals([], $fieldResult->getErrorList());
    }

    public function testReplaceObjectMultipleSuccess(): void
    {
        $replaceStrategy = new SerializedFieldReplaceStrategy();

        $fieldResult = $replaceStrategy->replace($this->getColumnMetadata(), 'bar', 'qux', 'O:6:"FooBar":3:{s:3:"foo";s:3:"bar";s:3:"baz";s:3:"bat";s:4:"test";s:3:"bar";}');

        $this->assertEquals('O:6:"FooBar":3:{s:3:"foo";s:3:"qux";s:3:"baz";s:3:"bat";s:4:"test";s:3:"qux";}', $fieldResult->getNewValue());
        $this->assertEquals(2, $fieldResult->getReplacedCount());
        $this->assertEquals([], $fieldResult->getErrorList());
    }

    public function testReplaceObjectFailureNoMatch(): void
    {
        $replaceStrategy = new SerializedFieldReplaceStrategy();

        $fieldResult = $replaceStrategy->replace($this->getColumnMetadata(), 'jazz', 'qux', 'O:6:"FooBar":3:{s:3:"foo";s:3:"bar";s:3:"baz";s:3:"bat";s:4:"test";s:3:"bar";}');

        $this->assertEquals('O:6:"FooBar":3:{s:3:"foo";s:3:"bar";s:3:"baz";s:3:"bat";s:4:"test";s:3:"bar";}', $fieldResult->getNewValue());
        $this->assertEquals(0, $fieldResult->getReplacedCount());
        $this->assertEquals(['Search term "jazz" not found in subject "O:6:"FooBar":3:{s:3:"foo";s:3:"bar";s:3:"baz";s:3:"bat";s:4:"test";s:3:"bar";}"'], $fieldResult->getErrorList());
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