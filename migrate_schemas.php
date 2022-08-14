<?php

declare(strict_types=1);

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Schema;

require_once __DIR__ . '/app/bootstrap.php';

$applyMigrations = ($argc === 2 && $argv[1] === '--force');

/** @var Schema[] $schemas */
$schemas = require __DIR__ . '/app/schemas.php';

$migrateSchema = function (Schema $schema, Connection $connection, $applyMigrations) {
  $schemaManager = $connection->getSchemaManager();

  $fromSchema = $schemaManager->createSchema();

  $comparator = new Comparator();
  $schemaDiff = $comparator->compare($fromSchema, $schema);

  $queries = $schemaDiff->toSaveSql($connection->getDatabasePlatform());

  if (count($queries) === 0) {
    echo "No Migration Needed!\n";
    return;
  }

  echo "Queries To Run\n\n";
  foreach ($queries as $query) {
    echo $query."\n";
    if ($applyMigrations) {
      $connection->exec($query);
    }
  }

  echo "\n";
};

/** @var Connection $primaryConnection */
$primaryConnection = $app['db'];

$migrateSchema($schemas['primary'], $primaryConnection, $applyMigrations);

/** @var Connection $logConnection */
$logConnection = $app['dbs']['log'];

$migrateSchema($schemas['log'], $logConnection, $applyMigrations);
