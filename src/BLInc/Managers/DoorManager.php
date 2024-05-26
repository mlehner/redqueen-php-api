<?php

declare(strict_types=1);

namespace BLInc\Managers;

use Doctrine\DBAL\Connection;

class DoorManager extends TimestampedManager
{
  public function findBySchedules(array $scheduleIds): iterable
  {
    $query = 'SELECT d.*,ds.schedule_id FROM doors d LEFT JOIN door_schedule ds ON (d.id = ds.door_id) WHERE ds.schedule_id IN (:scheduleIds)';
    return array_map([$this, 'transformRow'], $this->dbal->fetchAll($query, array('scheduleIds' => $scheduleIds), ['scheduleIds' => Connection::PARAM_INT_ARRAY]));
  }

  public function getTable(): string
  {
    return 'doors';
  }
}
