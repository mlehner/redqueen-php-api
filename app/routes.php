<?php

declare(strict_types=1);

use BLInc\Controller\DoorController;
use BLInc\Controller\ScheduleController;
use BLInc\Managers\ScheduleManager;
use BLInc\Validator\Constraints\Unique;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Validator\Constraints as Assert;
use BLInc\Model\CardSerialNumber;

$app['card.validation_constraints'] = function (Silex\Application $app) {
    return new Assert\Collection([
        'fields' => [
            'name' => new Assert\NotBlank(),
            'facilityCode' => [
                new Assert\NotBlank(),
                new Assert\Type('digit'),
                new Assert\Range(['min' => 1, 'max' => 255]),
            ],
            'cardNumber' => [
                new Assert\NotBlank(),
                new Assert\Type('digit'),
                new Assert\Range(['min' => 1, 'max' => 65535]),
            ],
            'code' => [
                new Assert\NotBlank(),
                new Unique(['table' => 'cards', 'column' => 'code']),
            ],
            'pin' => [
                new Assert\Type('digit'),
                new Assert\Length(['min' => 3]),
            ],
            'isActive' => new Assert\Type(['type' => 'boolean']),
            'schedules' => [new Assert\Count(['min' => 1]), new Assert\All([
                new Assert\Collection([
                    'fields' => [
                        'id' => [
                            new Assert\NotBlank(),
                            // Valid Schedule Id
                        ],
                    ],
                ]),
            ])],
        ],
    ]);
};

$app->match('/api/cards', function () {
    $response = new JsonResponse();
    $response->headers->set('Access-Control-Allow-Methods', 'POST,GET,OPTIONS');

    return $response;
})->method('OPTIONS');

$app->match('/api/cards/{id}', function () {
    $response = new JsonResponse();
    $response->headers->set('Access-Control-Allow-Methods', 'PUT,GET,OPTIONS');

    return $response;
})->method('OPTIONS');

$app->put('/api/cards/{id}', function (Silex\Application $app, Request $request, $id) {
    $content = $request->getContent();

    $card = json_decode($content, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        return $app->json([['message' => 'Failed to parse request.']], 400);
    }

    if (!is_array($card)) {
        return $app->json([['message' => 'Request must contain a hash or properties.']], 400);
    }

    $constraints = $app['card.validation_constraints'];
    $constraints->allowMissingFields = true;

    if (isset($card['facilityCode'], $card['cardNumber'])) {
        $card['code'] = CardSerialNumber::createFromStrings($card['facilityCode'], $card['cardNumber'])->getHexCsn();
    }

    $violations = $app['validator']->validateValue($card, $constraints, 'edit');

    if (count($violations)) {
        return new Response(
            $app['serializer']->serialize($violations, 'json'),
            400,
            ['Content-Type' => 'application/json']
        );
    }

    unset($card['facilityCode'], $card['cardNumber']);

    $app['card.manager']->update($id, $card);

    $response = new JsonResponse();
    $response->setStatusCode(201);
    $response->headers->set('Location', $app['url_generator']->generate('get_card', ['id' => $id]));

    return $response;
})->bind('put_card');

$app->post('/api/cards', function (Silex\Application $app, Request $request) {
    $content = $request->getContent();

    $card = json_decode($content, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        return $app->json([['message' => 'Failed to parse request.']], 400);
    }

    if (!is_array($card)) {
        return $app->json([['message' => 'Request must contain a hash or properties.']], 400);
    }

    if (isset($card['facilityCode'], $card['cardNumber'])) {
        $card['code'] = CardSerialNumber::createFromStrings($card['facilityCode'], $card['cardNumber'])->getHexCsn();
    }

    $violations = $app['validator']->validateValue($card, $app['card.validation_constraints'], 'new');

    if (count($violations)) {
        return new Response(
            $app['serializer']->serialize($violations, 'json'),
            400,
            ['Content-Type' => 'application/json']
        );
    }

    unset($card['facilityCode'], $card['cardNumber']);

    $card_id = $app['card.manager']->create($card);

    $response = new JsonResponse();
    $response->setStatusCode(201);
    $response->headers->set('Location', $app['url_generator']->generate('get_card', ['id' => $card_id]));

    return $response;
})->bind('post_card');

$app->get('/api/cards/{id}', function (Silex\Application $app, Request $request, $id) {
    $card = $app['card.manager']->find($id);

    if (!is_array($card)) {
        throw new NotFoundHttpException();
    }

    return $app->json($card);
})->bind('get_card');

$app->get('/api/cards', function (Silex\Application $app, Request $request) {
    $cards = $app['card.manager']->findAll();

    /** @var ScheduleManager $scheduleManager */
    $scheduleManager = $app['schedule.manager'];

    $schedules = $scheduleManager->findByCards(array_map(function (array $card) {
        return $card['id'];
    }, $cards));

    $schedulesByCardId = [];

    foreach ($schedules as $schedule) {
        if (isset($schedulesByCardId[$schedule['card_id']])) {
            $schedulesByCardId[$schedule['card_id']] = [];
        }

        $schdulesByCardId[$schedule['card_id']][] = $schedule;
    }

    $cards = array_map(function (array $card) use ($schedulesByCardId) {
        $cardSchedules = $schedulesByCardId[$card['id']] ?? [];

        $card['schedules'] = array_map(function (array $schedule) {
            return [
                'id' => $schedule['id'],
                'name' => $schedule['name'],
            ];
        }, $cardSchedules);

        return $card;
    }, $cards);

    return $app->json(['items' => $cards, 'count' => count($cards)]);
})->bind('get_cards');

$app->get('/api/logs', function (Silex\Application $app, Request $request) {
    if ($request->query->has('since')) {
        $since = $request->query->get('since');
        $sinceDateTime = \DateTimeImmutable::createFromFormat(\DateTimeInterface::ATOM, $since);

        if (!$sinceDateTime instanceof \DateTimeInterface) {
            return $app->json(['message' => 'Invalid since = ' . $since], 400);
        }
    }

    $logs = $app['log.manager']->findLatestSince($sinceDateTime ?? null);

    return $app->json(['items' => $logs, 'count' => count($logs)]);
})->bind('get_logs');

$app->match('/api/schedules', function () {
    $response = new JsonResponse();
    $response->headers->set('Access-Control-Allow-Methods', 'POST,GET,OPTIONS');

    return $response;
})->method('OPTIONS');

$app->match('/api/schedules/{id}', function () {
    $response = new JsonResponse();
    $response->headers->set('Access-Control-Allow-Methods', 'PUT,GET,OPTIONS');

    return $response;
})->method('OPTIONS');

$app->put('/api/schedules/{id}', [$app[ScheduleController::class], 'putSchedule'])->bind('put_schedule');
$app->post('/api/schedules', [$app[ScheduleController::class], 'postSchedule'])->bind('post_schedule');
$app->get('/api/schedules/{id}', [$app[ScheduleController::class], 'getSchedule'])->bind('get_schedule');
$app->get('/api/schedules', [$app[ScheduleController::class], 'getSchedules'])->bind('get_schedules');

$app->get('/api/doors', [$app[DoorController::class], 'getDoors'])->bind('get_doors');
$app->post('/api/doors', [$app[DoorController::class], 'postDoor'])->bind('post_door');
$app->get('/api/doors/{id}', [$app[DoorController::class], 'getDoor'])->bind('get_door');
$app->put('/api/doors/{id}', [$app[DoorController::class], 'putDoor'])->bind('put_door');
