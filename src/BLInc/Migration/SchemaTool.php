<?php

declare(strict_types=1);

namespace BLInc\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Schema;

final class SchemaTool
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function recreateDatabase(): void
    {
        $database = $this->connection->getDatabase();
        $this->connection->getSchemaManager()->tryMethod('dropDatabase', $database);
        $this->connection->getSchemaManager()->createDatabase($database);
        $this->connection->close();
        $this->connection->connect();
    }

    public function generateSql(Schema $schema): iterable
    {
        $schemaManager = $this->connection->getSchemaManager();

        $fromSchema = $schemaManager->createSchema();

        $comparator = new Comparator();
        $schemaDiff = $comparator->compare($fromSchema, $schema);

        return $schemaDiff->toSaveSql($this->connection->getDatabasePlatform());
    }
}
