<?php

declare(strict_types=1);

namespace BLInc\Controller;

use BLInc\Managers\ScheduleManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class ScheduleController
{
  private ScheduleManager $scheduleManager;

  public function __construct(ScheduleManager $scheduleManager)
  {
    $this->scheduleManager = $scheduleManager;
  }

  public function getSchedules(): Response
  {
    $schedules = $this->scheduleManager->findAll();

    return new JsonResponse(['items' => $schedules, 'count' => count($schedules)]);
  }
}
