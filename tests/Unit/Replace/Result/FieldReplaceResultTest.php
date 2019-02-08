<?php declare(strict_types=1);

/**
 * @copyright   (c) 2017-present brian ridley
 * @author      brian ridley <ptlis@ptlis.net>
 * @license     http://opensource.org/licenses/MIT MIT
 */

namespace ptlis\GrepDb\Test\Unit\Replace\Result;

use PHPUnit\Framework\TestCase;
use ptlis\GrepDb\Metadata\MySQL\ColumnMetadata;
use ptlis\GrepDb\Replace\Result\FieldReplaceResult;

final class FieldReplaceResultTest extends TestCase
{
    public function testSimpleGetters(): void
    {
        $fieldResult = new FieldReplaceResult(
            new ColumnMetadata(
                'my_database',
                'my_table',
                'my_column',
                'VARCHAR(255)',
                255,
                false,
                true,
                false
            ),
            2,
            [],
            'charlie this is a test string charlie',
            'bob this is a test string bob'
        );

        $this->assertEquals(2, $fieldResult->getReplacedCount());
        $this->assertEquals(0, count($fieldResult->getErrorList()));
        $this->assertEquals('bob this is a test string bob', $fieldResult->getNewValue());
        $this->assertEquals('charlie this is a test string charlie', $fieldResult->getOldValue());
    }
}