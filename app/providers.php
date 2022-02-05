<?php

use BLInc\Managers\CardManager;
use BLInc\Managers\LogManager;
use BLInc\Managers\ScheduleManager;
use BLInc\Validator\Constraints\UniqueValidator;
use GuzzleHttp\Psr7\HttpFactory;
use JMS\Serializer\Handler\ArrayCollectionHandler;
use JMS\Serializer\Handler\DateHandler;
use JMS\Serializer\Handler\HandlerRegistryInterface;
use JMS\Serializer\Handler\ConstraintViolationHandler;
use Jose\Component\Checker\AlgorithmChecker;
use Jose\Component\Checker\AudienceChecker;
use Jose\Component\Checker\ExpirationTimeChecker;
use Jose\Component\Checker\HeaderCheckerManager;
use Jose\Component\Checker\IssuedAtChecker;
use Jose\Component\Checker\IssuerChecker;
use Jose\Component\Checker\NotBeforeChecker;
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\KeyManagement\JKUFactory;
use Jose\Component\Signature\Algorithm\RS256;
use Jose\Component\Signature\JWSTokenSupport;
use Jose\Component\Signature\JWSVerifier;
use Jose\Component\Signature\Serializer\CompactSerializer;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use GuzzleHttp\Client;

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

$app->before(function (Request $request, Application $app): ?Response {
  $jwtString = $request->cookies->get('CF_AUTHORIZATION') ?? $request->headers->get('Cf-Access-Jwt-Assertion');

  if ($jwtString === null) {
    return Response::create('', 401);
  }

  $client = new Client();
  $requestFactory = new HttpFactory();

  $keySet = (new JKUFactory($client, $requestFactory))->loadFromUrl(
    getenv('REDQUEEN_JWT_KEYSET_URL'),
  );

  $rs256 = new RS256();

  $algorithmManager = new AlgorithmManager([$rs256]);

  $headerChecker = new HeaderCheckerManager([
    new AudienceChecker(getenv('REDQUEEN_JWT_AUDIENCE')),
    new AlgorithmChecker([$rs256->name()]),
    new NotBeforeChecker(),
    new IssuedAtChecker(),
    new ExpirationTimeChecker(),
    new IssuerChecker(getenv('REDQUEEN_JWT_ISSUER')),

  ], [
    new JWSTokenSupport(),
  ]);

  $jws = (new CompactSerializer())->unserialize($jwtString);
  $headerChecker->check($jws, 0);

  $verifier = new JWSVerifier($algorithmManager);
  if (!$verifier->verifyWithKeySet($jws, $keySet, 0)) {
    return Response::create('', 401);
  }

  return null;
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
