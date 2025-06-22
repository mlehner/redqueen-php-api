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
        $logSchemaTool = self::rebuildLogDatabase();

        self::rebuildPrimarySchema($primarySchemaTool);
        self::rebuildLogSchema($logSchemaTool);
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

    private static function rebuildLogDatabase(): SchemaTool
    {
        $logConnection = self::logConnection();
        $schemaTool = new SchemaTool($logConnection);
        $schemaTool->recreateDatabase();

        return $schemaTool;
    }

    private static function rebuildLogSchema(SchemaTool $schemaTool): void
    {
        $schemaLoader = new SchemaLoader();
        $schemaTool->executeSchemaSql($schemaLoader->getLogSchema());
    }

    private static function beforeTest(): void
    {
        $primaryConnection = self::primaryConnection();
        $logConnection = self::logConnection();

        foreach ($primaryConnection->getSchemaManager()->listTables() as $table) {
            $primaryConnection->executeStatement('ALTER TABLE `' . $table->getName() . '` AUTO_INCREMENT=1');
        }

        foreach ($logConnection->getSchemaManager()->listTables() as $table) {
            $logConnection->executeStatement('ALTER TABLE `' . $table->getName() . '` AUTO_INCREMENT=1');
        }

        $primaryConnection->beginTransaction();
        $logConnection->beginTransaction();
    }

    private static function afterTest(): void
    {
        $primaryConnection = self::primaryConnection();
        $primaryConnection->rollBack();

        $logConnection = self::logConnection();
        $logConnection->rollBack();
    }

    private static function primaryConnection(): Connection
    {
        global $app;
        assert($app['db'] instanceof Connection);
        return $app['db'];
    }

    private static function logConnection(): Connection
    {
        global $app;
        assert($app['dbs']['log'] instanceof Connection);
        return $app['dbs']['log'];
    }
}
