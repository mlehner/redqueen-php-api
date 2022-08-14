<?php

declare(strict_types=1);

use BLInc\Managers\CardManager;
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
      'url' => getenv('REDQUEEN_DB_URL'),
    ],
    'log' => [
      'url' => getenv('REDQUEEN_LOG_DB_URL'),
    ]
  ]
]);

$app['logger'] = Pimple::share(function (Application $app): LoggerInterface {
  return new Logger('app', [new ErrorLogHandler()]);
});

$app->register(new Silex\Provider\UrlGeneratorServiceProvider());
$app->register(new Silex\Provider\ValidatorServiceProvider());

$app['validator.validator_service_ids'] = function() {
    return array(
        UniqueValidator::class => 'validator.blinc_unique_validator',
    );
};

$app['validator.blinc_unique_validator'] = function(Silex\Application $app) {
    return new UniqueValidator($app['db']);
};

$app['log.manager'] = Pimple::share(function(Silex\Application $app) {
    return new LogManager($app['dbs']['log']);
});

$app['card.manager'] = Pimple::share(function(Silex\Application $app) {
    return new CardManager($app['db']);
});

$app['schedule.manager'] = Pimple::share(function(Silex\Application $app) {
    return new ScheduleManager($app['db']);
});

$app['serializer'] = Pimple::share(function() {
    return JMS\Serializer\SerializerBuilder::create()->configureHandlers(function(HandlerRegistryInterface $registry) {
        $registry->registerSubscribingHandler(new ConstraintViolationHandler());
        $registry->registerSubscribingHandler(new DateHandler());
        $registry->registerSubscribingHandler(new ArrayCollectionHandler());
    })->build();
});
