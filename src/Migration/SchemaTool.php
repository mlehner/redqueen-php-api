<?php

declare(strict_types=1);

namespace BLInc\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
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
        $params = $this->connection->getParams();
        $database = $params['dbname'];
        unset($params['dbname']);

        $connection = DriverManager::getConnection($params, $this->connection->getConfiguration());
        $schemaManager = $connection->createSchemaManager();
        $schemaManager->dropDatabase($database);
        $schemaManager->createDatabase($database);
        $connection->close();
    }

    public function generateSql(Schema $schema): iterable
    {
        $schemaManager = $this->connection->createSchemaManager();

        try {
            $fromSchema = $schemaManager->introspectSchema();
        } catch (\Throwable) {
            $fromSchema = new Schema();
        }

        $comparator = new Comparator();
        $schemaDiff = $comparator->compareSchemas($fromSchema, $schema);

        return $this->connection->getDatabasePlatform()->getAlterSchemaSQL($schemaDiff);
    }

    public function executeSchemaSql(Schema $schema): void
    {
        foreach ($this->generateSql($schema) as $query) {
            $this->connection->executeStatement($query);
        }
    }
}
