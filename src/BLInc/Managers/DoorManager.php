<?php

declare(strict_types=1);

namespace BLInc\Managers;

class DoorManager extends TimestampedManager
{
  public function getTable(): string
  {
    return 'doors';
  }
}
