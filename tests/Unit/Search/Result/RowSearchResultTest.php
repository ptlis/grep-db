<?php declare(strict_types=1);

/**
 * @copyright   (c) 2017-present brian ridley
 * @author      brian ridley <ptlis@ptlis.net>
 * @license     http://opensource.org/licenses/MIT MIT
 */

namespace ptlis\GrepDb\Test\Unit\Search\Result;

use PHPUnit\Framework\TestCase;
use ptlis\GrepDb\Metadata\MySQL\ColumnMetadata;
use ptlis\GrepDb\Metadata\MySQL\TableMetadata;
use ptlis\GrepDb\Search\Result\FieldSearchResult;
use ptlis\GrepDb\Search\Result\RowSearchResult;

final class RowSearchResultTest extends TestCase
{
    public function testSimpleGetters(): void
    {
        $rowResult = $this->getExampleRowResult();

        $this->assertEquals(1, count($rowResult->getMatchingFields()));
        $this->assertTrue($rowResult->hasColumnResult('my_column'));
        $this->assertFalse($rowResult->hasColumnResult('not_a_column'));
        $this->assertInstanceOf(ColumnMetadata::class, $rowResult->getPrimaryKeyColumn());
        $this->assertEquals(1234, $rowResult->getPrimaryKeyValue());
    }

    public function testGetColumnResultSuccess(): void
    {
        $rowResult = $this->getExampleRowResult();

        $this->assertInstanceOf(FieldSearchResult::class, $rowResult->getColumnResult('my_column'));
    }

    public function testGetColumnResultError(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Could not find changed column named "not_a_column"');

        $rowResult = $this->getExampleRowResult();

        $rowResult->getColumnResult('not_a_column');
    }

    private function getExampleRowResult(): RowSearchResult
    {
        return new RowSearchResult(
            new TableMetadata(
                'my_database',
                'my_table',
                'InnoDB',
                'utf8mb4_unicode_520_ci',
                'utf8mb4',
                3,
                [
                    new ColumnMetadata(
                        'my_database',
                        'my_table',
                        'my_column',
                        'TEXT',
                        255,
                        false,
                        true,
                        false
                    )
                ]
            ),
            [
                new FieldSearchResult(
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
                )
            ],
            new ColumnMetadata(
                'my_database',
                'my_table',
                'my_column',
                'INT',
                null,
                true,
                false,
                false
            ),
            1234
        );
    }
}