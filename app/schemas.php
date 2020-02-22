<?php

use Doctrine\DBAL\Schema\Schema;

$primarySchema = new Schema();

$cardTable = $primarySchema->createTable('cards');

$cardTable->addColumn('id', 'integer', ['unsigned' => true, 'notnull' => true, 'autoincrement' => true, 'length' => 20]);
$cardTable->setPrimaryKey(['id']);

$cardTable->addColumn('name', 'string', ['length' => 255, 'notnull' => true]);
$cardTable->addColumn('code', 'string', ['length' => 6, 'notnull' => true]);
$cardTable->addColumn('pin', 'string', ['length' => 32, 'notnull' => true]);
$cardTable->addColumn('isActive', 'boolean', ['default' => true, 'notnull' => true]);
$cardTable->addColumn('created_at', 'datetime', ['notnull' => true]);
$cardTable->addColumn('updated_at', 'datetime', ['notnull' => true]);

$scheduleTable = $primarySchema->createTable('schedules');

$scheduleTable->addColumn('id', 'integer', ['unsigned' => true, 'notnull' => true, 'autoincrement' => true, 'length' => 20]);
$scheduleTable->setPrimaryKey(['id']);

$scheduleTable->addColumn('name', 'string', ['length' => 255, 'notnull' => true]);
$scheduleTable->addColumn('mon', 'boolean', ['notnull' => true, 'default' => false]);
$scheduleTable->addColumn('tue', 'boolean', ['notnull' => true, 'default' => false]);
$scheduleTable->addColumn('wed', 'boolean', ['notnull' => true, 'default' => false]);
$scheduleTable->addColumn('thu', 'boolean', ['notnull' => true, 'default' => false]);
$scheduleTable->addColumn('fri', 'boolean', ['notnull' => true, 'default' => false]);
$scheduleTable->addColumn('sat', 'boolean', ['notnull' => true, 'default' => false]);
$scheduleTable->addColumn('sun', 'boolean', ['notnull' => true, 'default' => false]);
$scheduleTable->addColumn('startTime', 'time', ['notnull' => true]);
$scheduleTable->addColumn('endTime', 'time', ['notnull' => true]);
$scheduleTable->addColumn('created_at', 'datetime', ['notnull' => true]);
$scheduleTable->addColumn('updated_at', 'datetime', ['notnull' => true]);

$cardScheduleTable = $primarySchema->createTable('card_schedule');

$cardScheduleTable->addColumn('card_id', 'integer', ['length' => 20, 'unsigned' => true, 'notnull' => true]);
$cardScheduleTable->addColumn('schedule_id', 'integer', ['length' => 20, 'unsigned' => true, 'notnull' => true]);

$logSchema = new Schema();

$logTable = $logSchema->createTable('logs');

$logTable->addColumn('id', 'integer', ['unsigned' => true, 'notnull' => true, 'autoincrement' => true, 'length' => 20]);
$logTable->setPrimaryKey(['id']);

$logTable->addColumn('code', 'string', ['length' => 6, 'notnull' => true]);
$logTable->addColumn('validPin', 'boolean', ['notnull' => true, 'default' => false]);
$logTable->addColumn('created_at', 'datetime', ['notnull' => true]);

return [
  'primary' => $primarySchema,
  'log' => $logSchema,
];