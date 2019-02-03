<?php

/**
 * @copyright   (c) 2017-present brian ridley
 * @author      brian ridley <ptlis@ptlis.net>
 * @license     http://opensource.org/licenses/MIT MIT
 */

namespace ptlis\GrepDb\Test\Replace\ReplacementStrategy;

use PHPUnit\Framework\TestCase;
use ptlis\GrepDb\Replace\ReplacementStrategy\SerializedReplace;

final class SerializedReplaceTest extends TestCase
{
    public function testCanReplaceSerialized()
    {
        $serialized = serialize(new \stdClass());

        $replacementStrategy = new SerializedReplace();

        $this->assertTrue($replacementStrategy->canReplace($serialized));
    }

    public function testCannotReplaceSerialized()
    {
        $serialized = 'this is just a plain string';

        $replacementStrategy = new SerializedReplace();

        $this->assertFalse($replacementStrategy->canReplace($serialized));
    }

    public function testFlatObjectReplace()
    {
        $testObj = new \stdClass();
        $testObj->foo = 'foo';
        $testObj->bar = 'bar';

        $serialized = serialize($testObj);

        $replacementStrategy = new SerializedReplace();

        $replaced = $replacementStrategy->replace('foo', 'baz', $serialized);

        $this->assertEquals(
            'O:8:"stdClass":2:{s:3:"foo";s:3:"baz";s:3:"bar";s:3:"bar";}',
            $replaced
        );
    }

    public function testNestedObjectReplace()
    {
        $testObj = new \stdClass();
        $testObj->foo = 'foo';
        $testObj->bar = 'bar';
        $testObj->bat = new \stdClass();
        $testObj->bat->baz = 'baz';

        $serialized = serialize($testObj);

        $replacementStrategy = new SerializedReplace();

        $replaced = $replacementStrategy->replace('baz', 'qux', $serialized);

        $this->assertEquals(
            'O:8:"stdClass":3:{s:3:"foo";s:3:"foo";s:3:"bar";s:3:"bar";s:3:"bat";O:8:"stdClass":1:{s:3:"baz";s:3:"qux";}}',
            $replaced
        );
    }

    // TODO: test array?

    public function testUnknownClassReplace()
    {
        $serialized = 'O:14:"NotAKnownClass":2:{s:19:" NotAKnownClass foo";s:3:"foo";s:19:" NotAKnownClass bar";s:3:"bar";}';

        $replacementStrategy = new SerializedReplace();

        $replaced = $replacementStrategy->replace('bar', 'bat', $serialized);
    }
}