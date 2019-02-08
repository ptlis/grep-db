<?php declare(strict_types=1);

/**
 * @copyright   (c) 2017-present brian ridley
 * @author      brian ridley <ptlis@ptlis.net>
 * @license     http://opensource.org/licenses/MIT MIT
 */

namespace ptlis\GrepDb\Test\Unit\Search\Result;

use PHPUnit\Framework\TestCase;
use ptlis\GrepDb\Metadata\MySQL\ColumnMetadata;
use ptlis\GrepDb\Search\Result\FieldSearchResult;

final class FieldSearchResultTest extends TestCase
{
    public function testSimpleGetters(): void
    {
        $fieldResult = new FieldSearchResult(
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
            'bob'
        );

        $this->assertInstanceOf(ColumnMetadata::class, $fieldResult->getMetadata());
        $this->assertEquals('bob', $fieldResult->getValue());
    }
}