<?php

declare(strict_types=1);

use BLInc\Controller\CardController;
use BLInc\Controller\DoorController;
use BLInc\Controller\LogController;
use BLInc\Controller\ScheduleController;
use Symfony\Component\HttpFoundation\JsonResponse;
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

$app->put('/api/cards/{id}', [$app[CardController::class], 'putCard'])->bind('put_card');
$app->post('/api/cards', [$app[CardController::class], 'postCard'])->bind('post_card');
$app->get('/api/cards/{id}', [$app[CardController::class], 'getCard'])->bind('get_card');
$app->get('/api/cards', [$app[CardController::class], 'getCards'])->bind('get_cards');

$app->get('/api/logs', [$app[LogController::class], 'getLogs'])->bind('get_logs');

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
