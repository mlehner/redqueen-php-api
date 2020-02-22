<?php

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Comparator;

require_once __DIR__ . '/app/bootstrap.php';

$applyMigrations = ($argc === 2 && $argv[1] === '--force');

/** @var Connection $primaryConnection */
$primaryConnection = $app['db'];

$schemaManager = $primaryConnection->getSchemaManager();

$schemas = require __DIR__ . '/app/schemas.php';

$fromSchema = $schemaManager->createSchema();

$comparator = new Comparator();
$schemaDiff = $comparator->compare($fromSchema, $schemas['primary']);

$queries = $schemaDiff->toSaveSql($primaryConnection->getDatabasePlatform());

if (count($queries) === 0) {
  echo "No Migration Needed!\n";
  exit(0);
}

echo "Queries to run... \n\n\n";
echo implode("\n", $queries);
echo "\n\n\n";

if ($applyMigrations) {
  foreach ($queries as $query) {
    $primaryConnection->exec($query);
  }
}
