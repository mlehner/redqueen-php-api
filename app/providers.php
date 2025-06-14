<?php

declare(strict_types=1);

use BLInc\Controller\CardController;
use BLInc\Controller\DoorController;
use BLInc\Controller\LogController;
use BLInc\Controller\ScheduleController;
use BLInc\Managers\CardManager;
use BLInc\Managers\DoorManager;
use BLInc\Managers\LogManager;
use BLInc\Managers\ScheduleManager;
use BLInc\Validator\Constraints\UniqueValidator;
use JMS\Serializer\Handler\ArrayCollectionHandler;
use JMS\Serializer\Handler\DateHandler;
use JMS\Serializer\Handler\HandlerRegistryInterface;
use JMS\Serializer\Handler\ConstraintViolationHandler;
use JMS\Serializer\SerializerInterface;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Silex\Application;

require_once __DIR__ . '/jwt_providers.php';

$app->register(new Silex\Provider\DoctrineServiceProvider(), [
    'dbs.options' => [
        'primary' => [
            'url' => $_ENV['REDQUEEN_DB_URL'],
        ],
        'log' => [
            'url' => $_ENV['REDQUEEN_LOG_DB_URL'],
        ],
    ],
]);

$app->error(function (\Throwable $e) use ($app): void {
    $app['logger']->error(sprintf('Exception catch: %s', $e->getMessage()), ['exception' => $e]);
});

$app['logger'] = Pimple::share(function (Application $app): LoggerInterface {
    return new Logger('app', [new ErrorLogHandler()]);
});

$app->register(new Silex\Provider\UrlGeneratorServiceProvider());
$app->register(new Silex\Provider\ValidatorServiceProvider());

$app['validator.validator_service_ids'] = function () {
    return [
        UniqueValidator::class => 'validator.blinc_unique_validator',
    ];
};

$app['validator.blinc_unique_validator'] = function (Silex\Application $app): UniqueValidator {
    return new UniqueValidator($app['db']);
};

$app[LogManager::class] = Pimple::share(function (Silex\Application $app): LogManager {
    return new LogManager($app['dbs']['log']);
});

$app[CardManager::class] = Pimple::share(function (Silex\Application $app): CardManager {
    return new CardManager($app['db']);
});

$app[ScheduleManager::class] = Pimple::share(function (Silex\Application $app): ScheduleManager {
    return new ScheduleManager($app['db']);
});

$app[ScheduleController::class] = Pimple::share(function (Silex\Application $app): ScheduleController {
    return new ScheduleController($app[ScheduleManager::class], $app[DoorManager::class], $app['validator'], $app['serializer'], $app['url_generator']);
});

$app[DoorManager::class] = Pimple::share(function (Silex\Application $app): DoorManager {
    return new DoorManager($app['db']);
});

$app[DoorController::class] = Pimple::share(function (Application $app): DoorController {
    return new DoorController($app[DoorManager::class], $app['validator'], $app['serializer'], $app['url_generator']);
});

$app[CardController::class] = Pimple::share(function (Silex\Application $app): CardController {
    return new CardController($app[CardManager::class], $app[ScheduleManager::class], $app['validator'], $app['serializer'], $app['url_generator']);
});

$app[LogController::class] = Pimple::share(function (Silex\Application $app): LogController {
    return new LogController($app[LogManager::class]);
});

$app['serializer'] = Pimple::share(function (): SerializerInterface {
    return JMS\Serializer\SerializerBuilder::create()->configureHandlers(function (HandlerRegistryInterface $registry) {
        $registry->registerSubscribingHandler(new ConstraintViolationHandler());
        $registry->registerSubscribingHandler(new DateHandler());
        $registry->registerSubscribingHandler(new ArrayCollectionHandler());
    })->build();
});
