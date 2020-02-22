<?php

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Comparator;

require_once __DIR__ . '/app/bootstrap.php';

/** @var Connection $primaryConnection */
$primaryConnection = $app['db'];

$schemaManager = $primaryConnection->getSchemaManager();

$schemas = require __DIR__ . '/app/schemas.php';

$fromSchema = $schemaManager->createSchema();

$comparator = new Comparator();
$schemaDiff = $comparator->compare($fromSchema, $schemas['primary']);

$queries = $schemaDiff->toSql($primaryConnection->getDatabasePlatform());

echo implode("\n", $queries);
