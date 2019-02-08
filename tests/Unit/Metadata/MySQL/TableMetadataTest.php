<?php declare(strict_types=1);

/**
 * @copyright   (c) 2017-present brian ridley
 * @author      brian ridley <ptlis@ptlis.net>
 * @license     http://opensource.org/licenses/MIT MIT
 */

namespace ptlis\GrepDb\Test\Unit\Metadata\MySQL;

use PHPUnit\Framework\TestCase;
use ptlis\GrepDb\Metadata\MySQL\ColumnMetadata;
use ptlis\GrepDb\Metadata\MySQL\TableMetadata;

final class TableMetadataTest extends TestCase
{
    public function testSimpleGetters(): void
    {
        $tableMetadata = $this->getExampleMetadataIntNotPrimaryKey();

        $this->assertEquals('my_database', $tableMetadata->getDatabaseName());
        $this->assertEquals('my_table', $tableMetadata->getTableName());
        $this->assertEquals('InnoDB', $tableMetadata->getEngine());
        $this->assertEquals('utf8mb4_unicode_520_ci', $tableMetadata->getCollation());
        $this->assertEquals('utf8mb4', $tableMetadata->getCharset());
        $this->assertEquals(3, $tableMetadata->getRowCount());
        $this->assertEquals(1, count($tableMetadata->getAllColumnMetadata()));
        $this->assertEquals(null, $tableMetadata->getPrimaryKeyMetadata());
    }

    public function testHasStringTypeColumn(): void
    {
        $tableMetadata = new TableMetadata(
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
        );

        $this->assertTrue($tableMetadata->hasStringTypeColumn());
    }

    public function testDoesntHaveStringTypeColumn(): void
    {
        $tableMetadata = $this->getExampleMetadataIntNotPrimaryKey();

        $this->assertFalse($tableMetadata->hasStringTypeColumn());
    }

    public function testGetColumnMetadataSuccess(): void
    {
        $tableMetadata = $this->getExampleMetadataIntPrimaryKey();

        $this->assertInstanceOf(ColumnMetadata::class, $tableMetadata->getColumnMetadata('my_column'));
    }

    public function testGetColumnMetadataError(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Table "my_table" doesn\'t contain column "not_a_column"');

        $tableMetadata = $this->getExampleMetadataIntPrimaryKey();

        $tableMetadata->getColumnMetadata('not_a_column');
    }

    private function getExampleMetadataIntPrimaryKey(): TableMetadata {
        return new TableMetadata(
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
                    'INT',
                    null,
                    true,
                    false,
                    false
                )
            ]
        );
    }

    private function getExampleMetadataIntNotPrimaryKey(): TableMetadata {
        return new TableMetadata(
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
                    'INT',
                    null,
                    false,
                    true,
                    false
                )
            ]
        );
    }
}