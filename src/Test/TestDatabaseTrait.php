<?php

declare(strict_types=1);

namespace BLInc\Test;

use BLInc\Migration\SchemaLoader;
use BLInc\Migration\SchemaTool;
use Doctrine\DBAL\Connection;

trait TestDatabaseTrait
{
    public static function setUpBeforeClass(): void
    {
        self::rebuildDatabase();
    }

    public function setUp(): void
    {
        self::beforeTest();
    }

    public function tearDown(): void
    {
        self::afterTest();
    }

    private static function rebuildDatabase(): void
    {
        $primarySchemaTool = self::rebuildPrimaryDatabase();

        self::rebuildPrimarySchema($primarySchemaTool);
    }

    private static function rebuildPrimaryDatabase(): SchemaTool
    {
        $primaryConnection = self::primaryConnection();
        $schemaTool = new SchemaTool($primaryConnection);
        $schemaTool->recreateDatabase();

        return $schemaTool;
    }
    private static function rebuildPrimarySchema(SchemaTool $schemaTool): void
    {
        $schemaLoader = new SchemaLoader();
        $schemaTool->executeSchemaSql($schemaLoader->getPrimarySchema());
    }

    private static function beforeTest(): void
    {
        $primaryConnection = self::primaryConnection();

        foreach ($primaryConnection->getSchemaManager()->listTables() as $table) {
            $primaryConnection->executeStatement('ALTER TABLE `' . $table->getName() . '` AUTO_INCREMENT=1');
        }


        $primaryConnection->beginTransaction();
    }

    private static function primaryConnection(): Connection
    {
        return self::getContainer()->get('doctrine.dbal.primary_connection');
    }
}
