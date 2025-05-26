<?php

declare(strict_types=1);

use Doctrine\DBAL\Schema\Schema;

$booleanColumnOptions = fn (bool $default = false) => [
    'default' => ($default ? 1 : 0),
    'unsigned' => true,
    'columnDefinition' => 'TINYINT UNSIGNED NOT NULL DEFAULT ' . ($default ? "'1'" : "'0'")
];

$primarySchema = new Schema();

$cardTable = $primarySchema->createTable('cards');

$cardTable->addColumn('id', 'bigint', ['unsigned' => true, 'notnull' => true, 'autoincrement' => true, 'length' => 20]);
$cardTable->addColumn('name', 'string', ['length' => 255, 'notnull' => true]);
// @TODO this should be unique
$cardTable->addColumn('code', 'string', ['length' => 6, 'notnull' => true]);
$cardTable->addColumn('pin', 'string', ['length' => 32, 'notnull' => true]);
$cardTable->addColumn('isActive', 'boolean', $booleanColumnOptions(true));
$cardTable->addColumn('created_at', 'datetime', ['notnull' => true, 'default' => 'CURRENT_TIMESTAMP']);
$cardTable->addColumn('updated_at', 'datetime', ['columnDefinition' => 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP']);
$cardTable->addColumn('deleted_at', 'datetime', ['notnull' => false]);

$cardTable->setPrimaryKey(['id']);
$cardTable->addIndex(['code'], 'code');
$cardTable->addIndex(['isActive'], 'isActive');
$cardTable->addIndex(['deleted_at'], 'deleted_at');

$scheduleTable = $primarySchema->createTable('schedules');

$scheduleTable->addColumn('id', 'bigint', ['unsigned' => true, 'notnull' => true, 'autoincrement' => true, 'length' => 20]);
$scheduleTable->addColumn('name', 'string', ['length' => 255, 'notnull' => true]);
$scheduleTable->addColumn('mon', 'boolean', $booleanColumnOptions(false));
$scheduleTable->addColumn('tue', 'boolean', $booleanColumnOptions(false));
$scheduleTable->addColumn('wed', 'boolean', $booleanColumnOptions(false));
$scheduleTable->addColumn('thu', 'boolean', $booleanColumnOptions(false));
$scheduleTable->addColumn('fri', 'boolean', $booleanColumnOptions(false));
$scheduleTable->addColumn('sat', 'boolean', $booleanColumnOptions(false));
$scheduleTable->addColumn('sun', 'boolean', $booleanColumnOptions(false));
$scheduleTable->addColumn('startTime', 'time', ['notnull' => true]);
$scheduleTable->addColumn('endTime', 'time', ['notnull' => true]);
$scheduleTable->addColumn('authenticationMode', 'string', ['length' => 10, 'default' => 'card_pin', 'notnull' => true]);
$scheduleTable->addColumn('created_at', 'datetime', ['notnull' => true, 'default' => 'CURRENT_TIMESTAMP']);
$scheduleTable->addColumn('updated_at', 'datetime', ['columnDefinition' => 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP']);

$scheduleTable->setPrimaryKey(['id']);

$cardScheduleTable = $primarySchema->createTable('card_schedule');
$cardScheduleTable->addColumn('card_id', 'bigint', ['length' => 20, 'unsigned' => true, 'notnull' => true]);
$cardScheduleTable->addColumn('schedule_id', 'bigint', ['length' => 20, 'unsigned' => true, 'notnull' => true]);
$cardScheduleTable->addColumn('created_at', 'datetime', ['notnull' => true, 'default' => 'CURRENT_TIMESTAMP']);

$cardScheduleTable->setPrimaryKey(['card_id', 'schedule_id']);
$cardScheduleTable->addForeignKeyConstraint('cards', ['card_id'], ['id'], ['onDelete' => 'CASCADE']);
$cardScheduleTable->addForeignKeyConstraint('schedules', ['schedule_id'], ['id'], ['onDelete' => 'RESTRICT']);

$doorTable = $primarySchema->createTable('doors');

$doorTable->addColumn('id', 'bigint', ['unsigned' => true, 'notnull' => true, 'autoincrement' => true, 'length' => 20]);
$doorTable->addColumn('name', 'string', ['length' => 255, 'notnull' => true]);
$doorTable->addColumn('identifier', 'string', ['length' => 255, 'notnull' => true]);
$doorTable->addColumn('created_at', 'datetime', ['notnull' => true, 'default' => 'CURRENT_TIMESTAMP']);
$doorTable->addColumn('updated_at', 'datetime', ['columnDefinition' => 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP']);

$doorTable->setPrimaryKey(['id']);
$doorTable->addUniqueIndex(['identifier']);

$doorScheduleTable = $primarySchema->createTable('door_schedule');
$doorScheduleTable->addColumn('door_id', 'bigint', ['length' => 20, 'unsigned' => true, 'notnull' => true]);
$doorScheduleTable->addColumn('schedule_id', 'bigint', ['length' => 20, 'unsigned' => true, 'notnull' => true]);
$doorScheduleTable->addColumn('created_at', 'datetime', ['notnull' => true, 'default' => 'CURRENT_TIMESTAMP']);

$doorScheduleTable->setPrimaryKey(['door_id', 'schedule_id']);
$doorScheduleTable->addForeignKeyConstraint('schedules', ['schedule_id'], ['id'], ['onDelete' => 'CASCADE']);
$doorScheduleTable->addForeignKeyConstraint('doors', ['door_id'], ['id'], ['onDelete' => 'RESTRICT']);

$logSchema = new Schema();

$logTable = $logSchema->createTable('logs');

$logTable->addColumn('id', 'bigint', ['unsigned' => true, 'notnull' => true, 'autoincrement' => true, 'length' => 20]);
$logTable->addColumn('code', 'string', ['length' => 6, 'notnull' => true]);
$logTable->addColumn('validPin', 'boolean', $booleanColumnOptions(false));
$logTable->addColumn('created_at', 'datetime', ['notnull' => true, 'default' => 'CURRENT_TIMESTAMP']);
$logTable->addColumn('door_identifier', 'string', ['length' => 255, 'notnull' => false]);

$logTable->setPrimaryKey(['id']);
$logTable->addIndex(['code'], 'code');
$logTable->addIndex(['validPin'], 'validPin');
$logTable->addIndex(['created_at'], 'created_at');

return [
    'primary' => $primarySchema,
    'log' => $logSchema,
];
