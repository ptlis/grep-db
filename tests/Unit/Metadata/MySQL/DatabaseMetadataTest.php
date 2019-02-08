<?php declare(strict_types=1);

/**
 * @copyright   (c) 2017-present brian ridley
 * @author      brian ridley <ptlis@ptlis.net>
 * @license     http://opensource.org/licenses/MIT MIT
 */

namespace ptlis\GrepDb\Test\Unit\Metadata\MySQL;

use PHPUnit\Framework\TestCase;
use ptlis\GrepDb\Metadata\MySQL\ColumnMetadata;
use ptlis\GrepDb\Metadata\MySQL\DatabaseMetadata;
use ptlis\GrepDb\Metadata\MySQL\TableMetadata;

final class DatabaseMetadataTest extends TestCase
{
    public function testSimpleGetters(): void
    {
        $databaseMetadata = $this->getExampleDatabaseMetadata();

        $this->assertEquals('my_database', $databaseMetadata->getDatabaseName());
        $this->assertEquals(1, count($databaseMetadata->getAllTableMetadata()));
    }

    public function testGetTableMetadataSuccess(): void
    {
        $databaseMetadata = $this->getExampleDatabaseMetadata();

        $this->assertInstanceOf(TableMetadata::class, $databaseMetadata->getTableMetadata('my_table'));
    }

    public function testGetTableMetadataError(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Database "my_database" doesn\'t contain table "not_a_table"');

        $databaseMetadata = $this->getExampleDatabaseMetadata();

        $databaseMetadata->getTableMetadata('not_a_table');
    }

    private function getExampleDatabaseMetadata(): DatabaseMetadata {
        return new DatabaseMetadata(
            'my_database',
            [
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
                            'INT',
                            null,
                            false,
                            true,
                            false
                        ),
                        new ColumnMetadata(
                            'my_database',
                            'my_table',
                            'my_other_column',
                            'TEXT',
                            255,
                            false,
                            true,
                            false
                        )
                    ]
                )
            ]
        );
    }
}