<?php

declare(strict_types=1);

namespace BLInc\Test;

use BLInc\Migration\SchemaLoader;
use BLInc\Migration\SchemaTool;
use Doctrine\DBAL\Connection;

trait TestDatabaseTrait
{
  public static function rebuildDatabase(): void
  {
    $primaryConnection = self::primaryConnection();
    $schemaTool = new SchemaTool($primaryConnection);
    $schemaLoader = new SchemaLoader();
    $schemaTool->recreateDatabase();

    foreach ($schemaTool->generateSql($schemaLoader->getPrimarySchema()) as $query) {
      $primaryConnection->exec($query);
    }
  }

  public static function beforeTest(): Connection
  {
    $primaryConnection = self::primaryConnection();

    foreach ($primaryConnection->getSchemaManager()->listTables() as $table) {
      $primaryConnection->executeUpdate('ALTER TABLE `' . $table->getName() . '` AUTO_INCREMENT=1');
    }

    $primaryConnection->beginTransaction();

    return $primaryConnection;
  }

  public static function afterTest(): Connection
  {
    $primaryConnection = self::primaryConnection();
    $primaryConnection->rollBack();

    return $primaryConnection;
  }

  public static function primaryConnection(): Connection
  {
    global $app;
    assert($app['db'] instanceof Connection);
    return $app['db'];
  }
}
