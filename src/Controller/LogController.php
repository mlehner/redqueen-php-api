<?php

declare(strict_types=1);

namespace BLInc\Controller;

use BLInc\Managers\LogManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class LogController
{
    private LogManager $logManager;

    public function __construct(LogManager $logManager)
    {
        $this->logManager = $logManager;
    }

    public function getLogs(Request $request) {
        if ($request->query->has('since')) {
            $since = $request->query->get('since');
            $sinceDateTime = \DateTimeImmutable::createFromFormat(\DateTimeInterface::ATOM, $since);

            if (!$sinceDateTime instanceof \DateTimeInterface) {
                return new JsonResponse(['message' => 'Invalid since = ' . $since], 400);
            }
        }

        $logs = $this->logManager->findLatestSince($sinceDateTime ?? null);

        return new JsonResponse(['items' => $logs, 'count' => count($logs)]);
    }
}
