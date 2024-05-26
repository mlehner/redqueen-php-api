<?php

declare(strict_types=1);

use BLInc\Migration\SchemaLoader;
use BLInc\Migration\SchemaTool;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;

require_once __DIR__ . '/app/bootstrap.php';

$applyMigrations = ($argc === 2 && $argv[1] === '--force');

$migrateSchema = function (Schema $schema, Connection $connection, $applyMigrations) {
    $queries = (new SchemaTool($connection))->generateSql($schema);

    $queryCount = 0;
    echo "Queries To Run\n\n";

    foreach ($queries as $query) {
        $queryCount++;
        echo $query . "\n";

        if ($applyMigrations) {
            $connection->exec($query);
        }
    }

    if ($queryCount === 0) {
        echo "No Migration Needed!\n";
        return;
    }

    echo "\n";
};

$schemaLoader = new SchemaLoader();

/** @var Connection $primaryConnection */
$primaryConnection = $app['db'];

$migrateSchema($schemaLoader->getPrimarySchema(), $primaryConnection, $applyMigrations);

/** @var Connection $logConnection */
$logConnection = $app['dbs']['log'];

$migrateSchema($schemaLoader->getLogSchema(), $logConnection, $applyMigrations);
