<?php declare(strict_types=1);

/**
 * @copyright   (c) 2017-present brian ridley
 * @author      brian ridley <ptlis@ptlis.net>
 * @license     http://opensource.org/licenses/MIT MIT
 */

namespace ptlis\GrepDb\Test\Unit\Metadata\MySQL;

use PHPUnit\Framework\TestCase;
use ptlis\GrepDb\Metadata\MySQL\ColumnMetadata;

final class ColumnMetadataTest extends TestCase
{
    public function testSimpleGetters(): void
    {
        $columnMetadata = new ColumnMetadata(
            'my_database',
            'my_table',
            'my_column',
            'VARCHAR(255)',
            255,
            false,
            true,
            false
        );

        $this->assertEquals('my_database', $columnMetadata->getDatabaseName());
        $this->assertEquals('my_table', $columnMetadata->getTableName());
        $this->assertEquals('my_column', $columnMetadata->getColumnName());
        $this->assertEquals('VARCHAR(255)', $columnMetadata->getType());
        $this->assertEquals(255, $columnMetadata->getMaxLength());
        $this->assertFalse($columnMetadata->isPrimaryKey());
        $this->assertTrue($columnMetadata->isNullable());
        $this->assertFalse($columnMetadata->isIndexed());
    }

    public function testIsStringTypeVarchar(): void
    {
        $columnMetadata = new ColumnMetadata(
            'my_database',
            'my_table',
            'my_column',
            'VARCHAR(255)',
            255,
            false,
            true,
            false
        );

        $this->assertTrue($columnMetadata->isStringType());
    }

    public function testIsStringTypeText(): void
    {
        $columnMetadata = new ColumnMetadata(
            'my_database',
            'my_table',
            'my_column',
            'TEXT',
            255,
            false,
            true,
            false
        );

        $this->assertTrue($columnMetadata->isStringType());
    }

    public function testIsStringTypeInt(): void
    {
        $columnMetadata = new ColumnMetadata(
            'my_database',
            'my_table',
            'my_column',
            'INT',
            null,
            false,
            true,
            false
        );

        $this->assertFalse($columnMetadata->isStringType());
    }
}