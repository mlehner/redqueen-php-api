<?php

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

  echo "Queries to run... \n\n\n";
  echo implode("\n", $queries);
  echo "\n\n\n";

  if ($applyMigrations) {
    foreach ($queries as $query) {
      $connection->exec($query);
    }
  }
};

/** @var Connection $primaryConnection */
$primaryConnection = $app['db'];

$migrateSchema($schemas['primary'], $primaryConnection, $applyMigrations);

/** @var Connection $logConnection */
$logConnection = $app['dbs']['log'];

$migrateSchema($schemas['log'], $logConnection, $applyMigrations);
