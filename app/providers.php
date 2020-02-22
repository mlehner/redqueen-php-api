<?php

use BLInc\Managers\CardManager;
use BLInc\Managers\LogManager;
use BLInc\Managers\ScheduleManager;
use BLInc\Validator\Constraints\UniqueValidator;
use JMS\Serializer\Handler\ArrayCollectionHandler;
use JMS\Serializer\Handler\DateHandler;
use JMS\Serializer\Handler\HandlerRegistryInterface;
use JMS\Serializer\Handler\ConstraintViolationHandler;

$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
  'db.default_options' => array(
    'url' => getenv('REDQUEEN_DB_URL'),
  )
));

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
    return new LogManager($app['db']);
});

$app['card.manager'] = Pimple::share(function(Silex\Application $app) {
    return new CardManager($app['db']);
});

$app['schedule.manager'] = Pimple::share(function(Silex\Application $app) {
    return new ScheduleManager($app['db']);
});

$app['serializer'] = Pimple::share(function() {
    /**
     * @var $serializer JMS\Serializer\Serializer
     */
    $serializer = JMS\Serializer\SerializerBuilder::create()->configureHandlers(function(HandlerRegistryInterface $registry) {
        $registry->registerSubscribingHandler(new ConstraintViolationHandler());
        $registry->registerSubscribingHandler(new DateHandler());
        $registry->registerSubscribingHandler(new ArrayCollectionHandler());
    })->build();

    return $serializer;
});
