<?php declare(strict_types=1);

/**
 * @copyright   (c) 2017-present brian ridley
 * @author      brian ridley <ptlis@ptlis.net>
 * @license     http://opensource.org/licenses/MIT MIT
 */

namespace ptlis\GrepDb\Test\Unit\Metadata\MySQL\Parser;

use PHPUnit\Framework\TestCase;
use ptlis\GrepDb\Metadata\MySQL\ColumnMetadata;
use ptlis\GrepDb\Metadata\MySQL\Parser\Parser;
use ptlis\GrepDb\Metadata\MySQL\Parser\Tokenizer;
use ptlis\GrepDb\Metadata\MySQL\TableMetadata;

final class ParserTest extends TestCase
{
    /**
     * @param ColumnMetadata[] $columnMetadataList
     */
    private function validateColumn(
        array $columnMetadataList,
        string $databaseName,
        string $tableName,
        string $columName,
        string $columnType,
        ?int $maxLength,
        bool $isPrimaryKey,
        bool $isNullable,
        bool $isIndexed
    ): void {
        $this->assertTrue(array_key_exists($columName, $columnMetadataList));
        $columnMetadata = $columnMetadataList[$columName];

        $this->assertEquals($databaseName, $columnMetadata->getDatabaseName());
        $this->assertEquals($tableName, $columnMetadata->getTableName());
        $this->assertEquals($columName, $columnMetadata->getColumnName());
        $this->assertEquals($columnType, $columnMetadata->getType());
        $this->assertEquals($maxLength, $columnMetadata->getMaxLength());
        $this->assertEquals($isPrimaryKey, $columnMetadata->isPrimaryKey());
        $this->assertEquals($isNullable, $columnMetadata->isNullable());
        $this->assertEquals($isIndexed, $columnMetadata->isIndexed());
    }

    private function validateTable(
        TableMetadata $tableMetadata,
        string $databaseName,
        string $tableName,
        string $engine,
        string $collation,
        string $charset,
        int $rowCount,
        int $columnCount
    ) {
        $this->assertEquals($databaseName, $tableMetadata->getDatabaseName());
        $this->assertEquals($tableName, $tableMetadata->getTableName());
        $this->assertEquals($engine, $tableMetadata->getEngine());
        $this->assertEquals($collation, $tableMetadata->getCollation());
        $this->assertEquals($charset, $tableMetadata->getCharset());
        $this->assertEquals($rowCount, $tableMetadata->getRowCount());
        $this->assertEquals($columnCount, count($tableMetadata->getAllColumnMetadata()));
    }

    public function testParseSingleTable(): void
    {
        $tokenizer = new Tokenizer();
        $parser = new Parser($tokenizer);

        /** @var TableMetadata[] $tableMetadataList */
        $tableMetadataList = [];
        foreach ($parser->parseAllTableMetadata('./tests/Data/single_table.sql') as $tableMetadata) {
            $tableMetadataList[] = $tableMetadata;
        };

        $dbName = './tests/Data/single_table.sql';
        $tableName = 'test_table_1';

        $this->assertEquals(1, count($tableMetadataList));

        // Verify table metadata
        $this->validateTable($tableMetadataList[0], $dbName, $tableName, 'InnoDB', 'DEFAULT', 'latin1', -1, 10);
        $columnMetadataList = $tableMetadataList[0]->getAllColumnMetadata();
        $this->validateColumn($columnMetadataList, $dbName, $tableName, 'test_pk', 'int(11)', null, true, false, true);
        $this->validateColumn($columnMetadataList, $dbName, $tableName, 'test_varchar', 'varchar(255)', 255, false, true, true);
        $this->validateColumn($columnMetadataList, $dbName, $tableName, 'test_text', 'text', 65535, false, true, false);
        $this->validateColumn($columnMetadataList, $dbName, $tableName, 'test_date', 'date', null, false, true, false);
        $this->validateColumn($columnMetadataList, $dbName, $tableName, 'test_unique', 'varchar(1024)', 1024, false, true, true);
        $this->validateColumn($columnMetadataList, $dbName, $tableName, 'test_decimal', 'decimal(10,2)', null, false, true, false);
        $this->validateColumn($columnMetadataList, $dbName, $tableName, 'test_float', 'float', null, false, true, false);
        $this->validateColumn($columnMetadataList, $dbName, $tableName, 'test_double', 'double', null, false, true, true);
        $this->validateColumn($columnMetadataList, $dbName, $tableName, 'test_blob', 'blob', 65535, false, true, false);
        $this->validateColumn($columnMetadataList, $dbName, $tableName, 'test_bigint', 'bigint(20)', null, false, true, false);
    }

    public function testParseCompoundPrimaryKey(): void
    {
        $tokenizer = new Tokenizer();
        $parser = new Parser($tokenizer);

        /** @var TableMetadata[] $tableMetadataList */
        $tableMetadataList = [];
        foreach ($parser->parseAllTableMetadata('./tests/Data/single_table_compound_pk.sql') as $tableMetadata) {
            $tableMetadataList[] = $tableMetadata;
        };

        $this->assertEquals(1, count($tableMetadataList));

        $dbName = './tests/Data/single_table_compound_pk.sql';
        $tableName = 'test_table_compound_pk';

        // Verify table metadata
        $this->validateTable($tableMetadataList[0], $dbName, $tableName, 'InnoDB', 'DEFAULT', 'latin1', -1, 3);
        $columnMetadataList = $tableMetadataList[0]->getAllColumnMetadata();
        $this->validateColumn($columnMetadataList, $dbName, $tableName, 'column_1_pk', 'int(11)', null, true, false, true);
        $this->validateColumn($columnMetadataList, $dbName, $tableName, 'column_2_pk', 'int(11)', null, true, false, true);
        $this->validateColumn($columnMetadataList, $dbName, $tableName, 'column_data', 'varchar(512)', 512, false, true, false);
    }

    public function testParseTwoTables(): void
    {
        $tokenizer = new Tokenizer();
        $parser = new Parser($tokenizer);

        /** @var TableMetadata[] $tableMetadataList */
        $tableMetadataList = [];
        foreach ($parser->parseAllTableMetadata('./tests/Data/two_tables.sql') as $tableMetadata) {
            $tableMetadataList[] = $tableMetadata;
        };

        $this->assertEquals(2, count($tableMetadataList));

        $dbName = './tests/Data/two_tables.sql';

        // Verify table metadata
        $this->validateTable($tableMetadataList[0], $dbName, 'test_table_1', 'InnoDB', 'DEFAULT', 'latin1', -1, 10);
        $tableOneColumnMetadataList = $tableMetadataList[0]->getAllColumnMetadata();
        $this->validateColumn($tableOneColumnMetadataList, $dbName, 'test_table_1', 'test_pk', 'int(11)', null, true, false, true);
        $this->validateColumn($tableOneColumnMetadataList, $dbName, 'test_table_1', 'test_varchar', 'varchar(255)', 255, false, true, true);
        $this->validateColumn($tableOneColumnMetadataList, $dbName, 'test_table_1', 'test_text', 'text', 65535, false, true, false);
        $this->validateColumn($tableOneColumnMetadataList, $dbName, 'test_table_1', 'test_date', 'date', null, false, true, false);
        $this->validateColumn($tableOneColumnMetadataList, $dbName, 'test_table_1', 'test_unique', 'varchar(1024)', 1024, false, true, true);
        $this->validateColumn($tableOneColumnMetadataList, $dbName, 'test_table_1', 'test_decimal', 'decimal(10,2)', null, false, true, false);
        $this->validateColumn($tableOneColumnMetadataList, $dbName, 'test_table_1', 'test_float', 'float', null, false, true, false);
        $this->validateColumn($tableOneColumnMetadataList, $dbName, 'test_table_1', 'test_double', 'double', null, false, true, true);
        $this->validateColumn($tableOneColumnMetadataList, $dbName, 'test_table_1', 'test_blob', 'blob', 65535, false, true, false);
        $this->validateColumn($tableOneColumnMetadataList, $dbName, 'test_table_1', 'test_bigint', 'bigint(20)', null, false, true, false);

        // Verify table metadata
        $this->validateTable($tableMetadataList[1], $dbName, 'test_table_compound_pk', 'InnoDB', 'DEFAULT', 'latin1', -1, 3);
        $tableTwoColumnMetadataList = $tableMetadataList[1]->getAllColumnMetadata();
        $this->validateColumn($tableTwoColumnMetadataList, $dbName, 'test_table_compound_pk', 'column_1_pk', 'int(11)', null, true, false, true);
        $this->validateColumn($tableTwoColumnMetadataList, $dbName, 'test_table_compound_pk', 'column_2_pk', 'int(11)', null, true, false, true);
        $this->validateColumn($tableTwoColumnMetadataList, $dbName, 'test_table_compound_pk', 'column_data', 'varchar(512)', 512, false, true, false);
    }

    public function testParseSingleTableWithSetStatements(): void
    {
        $tokenizer = new Tokenizer();
        $parser = new Parser($tokenizer);

        /** @var TableMetadata[] $tableMetadataList */
        $tableMetadataList = [];
        foreach ($parser->parseAllTableMetadata('./tests/Data/single_table_includes_set_statements.sql') as $tableMetadata) {
            $tableMetadataList[] = $tableMetadata;
        };

        $this->assertEquals(1, count($tableMetadataList));

        $dbName = './tests/Data/single_table_includes_set_statements.sql';
        $tableName = 'table_with_collation';

        // Verify table metadata
        $this->validateTable($tableMetadataList[0], $dbName, $tableName, 'InnoDB', 'utf8mb4_unicode_520_ci', 'utf8mb4', -1, 4);
        $tableOneColumnMetadataList = $tableMetadataList[0]->getAllColumnMetadata();
        $this->validateColumn($tableOneColumnMetadataList, $dbName, $tableName, 'item_id', 'bigint(20)', null, true, false, true);
        $this->validateColumn($tableOneColumnMetadataList, $dbName, $tableName, 'comment_id', 'bigint(20)', null, false, false, true);
        $this->validateColumn($tableOneColumnMetadataList, $dbName, $tableName, 'collate_varchar', 'varchar(255)', 255, false, true, true);
        $this->validateColumn($tableOneColumnMetadataList, $dbName, $tableName, 'collate_text', 'longtext', null, false, true, false);

    }
}
