<?php

declare(strict_types=1);

namespace BLInc\Test;

use BLInc\Migration\SchemaLoader;
use BLInc\Migration\SchemaTool;
use DAMA\DoctrineTestBundle\Doctrine\DBAL\StaticDriver;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\BeforeClass;

trait TestDatabaseTrait
{
    #[BeforeClass]
    public static function rebuildDatabase(): void
    {
        $keepStaticConnections = StaticDriver::isKeepStaticConnections();
        if ($keepStaticConnections) {
            StaticDriver::setKeepStaticConnections(false);
        }

        $primarySchemaTool = self::rebuildPrimaryDatabase();

        self::rebuildPrimarySchema($primarySchemaTool);

        self::primaryConnection()->close();

        if ($keepStaticConnections) {
            StaticDriver::setKeepStaticConnections(true);
        }
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

    #[Before]
    public static function beforeTest(): void
    {
        $primaryConnection = self::primaryConnection();

        // ALTER will close the transaction implicitly, so just do it manually
        StaticDriver::commit();

        foreach ($primaryConnection->createSchemaManager()->listTables() as $table) {
            $primaryConnection->executeStatement('ALTER TABLE `' . $table->getName() . '` AUTO_INCREMENT=1');
        }

        StaticDriver::beginTransaction();
    }

    private static function primaryConnection(): Connection
    {
        return self::getContainer()->get('doctrine.dbal.primary_connection');
    }
}
