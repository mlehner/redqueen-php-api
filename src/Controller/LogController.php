<?php

declare(strict_types=1);

namespace BLInc\Controller;

use BLInc\Managers\LogManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class LogController
{
    private LogManager $logManager;

    public function __construct(LogManager $logManager)
    {
        $this->logManager = $logManager;
    }

    #[Route(path: '/api/logs', name: 'get_logs', methods: Request::METHOD_GET)]
    public function getLogs(Request $request): Response {
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
