<?php

declare(strict_types=1);

use Doctrine\DBAL\Schema\Schema;

$primarySchema = new Schema();

$cardTable = $primarySchema->createTable('cards');

$cardTable->addColumn('id', 'bigint', ['unsigned' => true, 'notnull' => true, 'autoincrement' => true, 'length' => 20]);
$cardTable->addColumn('name', 'string', ['length' => 255, 'notnull' => true]);
// @TODO this should be unique
$cardTable->addColumn('code', 'string', ['length' => 6, 'notnull' => true]);
$cardTable->addColumn('pin', 'string', ['length' => 32, 'notnull' => true]);
$cardTable->addColumn('isActive', 'boolean', ['default' => true, 'notnull' => true]);
$cardTable->addColumn('created_at', 'datetime', ['notnull' => true]);
$cardTable->addColumn('updated_at', 'datetime', ['notnull' => true]);

$cardTable->setPrimaryKey(['id']);
$cardTable->addIndex(['code'], 'code');
$cardTable->addIndex(['isActive'], 'isActive');

$scheduleTable = $primarySchema->createTable('schedules');

$scheduleTable->addColumn('id', 'bigint', ['unsigned' => true, 'notnull' => true, 'autoincrement' => true, 'length' => 20]);
$scheduleTable->addColumn('name', 'string', ['length' => 255, 'notnull' => true]);
$scheduleTable->addColumn('mon', 'boolean', ['unsigned' => true, 'notnull' => true, 'default' => false]);
$scheduleTable->addColumn('tue', 'boolean', ['unsigned' => true, 'notnull' => true, 'default' => false]);
$scheduleTable->addColumn('wed', 'boolean', ['unsigned' => true, 'notnull' => true, 'default' => false]);
$scheduleTable->addColumn('thu', 'boolean', ['unsigned' => true, 'notnull' => true, 'default' => false]);
$scheduleTable->addColumn('fri', 'boolean', ['unsigned' => true, 'notnull' => true, 'default' => false]);
$scheduleTable->addColumn('sat', 'boolean', ['unsigned' => true, 'notnull' => true, 'default' => false]);
$scheduleTable->addColumn('sun', 'boolean', ['unsigned' => true, 'notnull' => true, 'default' => false]);
$scheduleTable->addColumn('startTime', 'time', ['notnull' => true]);
$scheduleTable->addColumn('endTime', 'time', ['notnull' => true]);
$scheduleTable->addColumn('created_at', 'datetime', ['notnull' => true]);
$scheduleTable->addColumn('updated_at', 'datetime', ['notnull' => true]);

$scheduleTable->setPrimaryKey(['id']);

$cardScheduleTable = $primarySchema->createTable('card_schedule');
$cardScheduleTable->addColumn('card_id', 'bigint', ['length' => 20, 'unsigned' => true, 'notnull' => true]);
$cardScheduleTable->addColumn('schedule_id', 'bigint', ['length' => 20, 'unsigned' => true, 'notnull' => true]);

$cardScheduleTable->setPrimaryKey(['card_id', 'schedule_id']);

$logSchema = new Schema();

$logTable = $logSchema->createTable('logs');

$logTable->addColumn('id', 'bigint', ['unsigned' => true, 'notnull' => true, 'autoincrement' => true, 'length' => 20]);
$logTable->addColumn('code', 'string', ['length' => 6, 'notnull' => true]);
$logTable->addColumn('validPin', 'boolean', ['notnull' => true, 'default' => false]);
$logTable->addColumn('created_at', 'datetime', ['notnull' => true]);

$logTable->setPrimaryKey(['id']);
$logTable->addIndex(['code'], 'code');
$logTable->addIndex(['validPin'], 'validPin');
$logTable->addIndex(['created_at'], 'created_at');

return [
  'primary' => $primarySchema,
  'log' => $logSchema,
];
