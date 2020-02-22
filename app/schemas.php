<?php

use Doctrine\DBAL\Schema\Schema;

$primarySchema = new Schema();

$cardTable = $primarySchema->createTable('cards');

$cardTable->addColumn('id', 'bigint', ['unsigned' => true, 'notnull' => true, 'autoincrement' => true, 'length' => 20]);
$cardTable->setPrimaryKey(['id']);

$cardTable->addColumn('name', 'varchar', ['length' => 255, 'notnull' => true]);
$cardTable->addColumn('code', 'varchar', ['length' => 6, 'notnull' => true]);
$cardTable->addColumn('pin', 'varchar', ['length' => 32, 'notnull' => true]);
$cardTable->addColumn('isActive', 'tinyint', ['length' => 1, 'default' => '1', 'notnull' => true]);
$cardTable->addColumn('created_at', 'datetime', ['notnull' => true]);
$cardTable->addColumn('updated_at', 'datetime', ['notnull' => true]);

$scheduleTable = $primarySchema->createTable('schedules');

$scheduleTable->addColumn('id', 'bigint', ['unsigned' => true, 'notnull' => true, 'autoincrement' => true, 'length' => 20]);
$scheduleTable->setPrimaryKey(['id']);

$scheduleTable->addColumn('name', 'varchar', ['length' => 255, 'notnull' => true]);
$scheduleTable->addColumn('mon', 'tinyint', ['length' => 1, 'notnull' => true, 'default' => '0']);
$scheduleTable->addColumn('tue', 'tinyint', ['length' => 1, 'notnull' => true, 'default' => '0']);
$scheduleTable->addColumn('wed', 'tinyint', ['length' => 1, 'notnull' => true, 'default' => '0']);
$scheduleTable->addColumn('thu', 'tinyint', ['length' => 1, 'notnull' => true, 'default' => '0']);
$scheduleTable->addColumn('fri', 'tinyint', ['length' => 1, 'notnull' => true, 'default' => '0']);
$scheduleTable->addColumn('sat', 'tinyint', ['length' => 1, 'notnull' => true, 'default' => '0']);
$scheduleTable->addColumn('sun', 'tinyint', ['length' => 1, 'notnull' => true, 'default' => '0']);
$scheduleTable->addColumn('startTime', 'time', ['notnull' => true]);
$scheduleTable->addColumn('endTime', 'time', ['notnull' => true]);
$scheduleTable->addColumn('created_at', 'datetime', ['notnull' => true]);
$scheduleTable->addColumn('updated_at', 'datetime', ['notnull' => true]);

$cardScheduleTable = $primarySchema->createTable('card_schedule');

$cardScheduleTable->addColumn('card_id', 'bigint', ['length' => 20, 'unsigned' => true, 'notnull' => true]);
$cardScheduleTable->addColumn('schedule_id', 'bigint', ['length' => 20, 'unsigned' => true, 'notnull' => true]);

$logSchema = new Schema();

$logTable = $logSchema->createTable('logs');

$logTable->addColumn('id', 'bigint', ['unsigned' => true, 'notnull' => true, 'autoincrement' => true, 'length' => 20]);
$logTable->setPrimaryKey(['id']);

$logTable->addColumn('code', 'varchar', ['length' => 6, 'notnull' => true]);
$logTable->addColumn('validPin', 'tinyint', ['length' => 1, 'notnull' => true, 'default' => '0']);
$logTable->addColumn('created_at', 'datetime', ['notnull' => true]);

return [
  'primary' => $primarySchema,
  'log' => $logSchema,
];
