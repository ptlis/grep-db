<?php declare(strict_types=1);

/**
 * @copyright   (c) 2017-present brian ridley
 * @author      brian ridley <ptlis@ptlis.net>
 * @license     http://opensource.org/licenses/MIT MIT
 */

namespace ptlis\GrepDb\Metadata\MySQL\DataSource;

use ptlis\GrepDb\Metadata\MySQL\DatabaseMetadata;
use ptlis\GrepDb\Metadata\MySQL\TableMetadata;

/**
 * Factory that builds database & table metadata from a doctrine DBAL connection.
 */
interface MetadataFactory
{
    /**
     * Query the server and build database metadata DTO.
     */
    public function getDatabaseMetadata(): DatabaseMetadata;

    /**
     * Query the server and build table metadata DTO.
     */
    public function getTableMetadata(string $tableName): TableMetadata;
}
