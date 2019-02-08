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
use ptlis\GrepDb\Metadata\MySQL\ServerMetadata;
use ptlis\GrepDb\Metadata\MySQL\TableMetadata;

final class ServerMetadataTest extends TestCase
{
    public function testSimpleGetters()
    {
        $serverMetadata = $this->getExampleServerMetadata();

        $this->assertEquals('my.fake.hostname', $serverMetadata->getHost());
        $this->assertEquals(1, count($serverMetadata->getAllDatabaseMetadata()));
    }

    public function testGetDatabaseMetadataSuccess(): void
    {
        $serverMetadata = $this->getExampleServerMetadata();

        $this->assertInstanceOf(DatabaseMetadata::class, $serverMetadata->getDatabaseMetadata('my_database'));
    }

    public function testGetDatabaseMetadataError(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Server "my.fake.hostname" doesn\'t contain database "not_a_database"');

        $serverMetadata = $this->getExampleServerMetadata();

        $serverMetadata->getDatabaseMetadata('not_a_database');
    }

    private function getExampleServerMetadata(): ServerMetadata {
        return new ServerMetadata(
            'my.fake.hostname',
            [
                new DatabaseMetadata(
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
                )
            ]
        );
    }
}