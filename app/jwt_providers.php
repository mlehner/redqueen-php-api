<?php

declare(strict_types=1);

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\HttpFactory;
use Jose\Component\Checker\AlgorithmChecker;
use Jose\Component\Checker\AudienceChecker;
use Jose\Component\Checker\ClaimCheckerManager;
use Jose\Component\Checker\ExpirationTimeChecker;
use Jose\Component\Checker\HeaderCheckerManager;
use Jose\Component\Checker\InvalidClaimException;
use Jose\Component\Checker\InvalidHeaderException;
use Jose\Component\Checker\IssuedAtChecker;
use Jose\Component\Checker\IssuerChecker;
use Jose\Component\Checker\MissingMandatoryClaimException;
use Jose\Component\Checker\NotBeforeChecker;
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Core\JWKSet;
use Jose\Component\KeyManagement\JKUFactory;
use Jose\Component\Signature\Algorithm\RS256;
use Jose\Component\Signature\JWSTokenSupport;
use Jose\Component\Signature\JWSVerifier;
use Jose\Component\Signature\Serializer\CompactSerializer;
use Kevinrob\GuzzleCache\CacheMiddleware;
use Kevinrob\GuzzleCache\Storage\Psr6CacheStorage;
use Kevinrob\GuzzleCache\Strategy\PrivateCacheStrategy;
use Silex\Application;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

$app['jwt.http_client'] = Pimple::share(function (Application $app): ClientInterface {
  $handlerStack = HandlerStack::create();
  $handlerStack->push(new CacheMiddleware(
    new PrivateCacheStrategy(
      new Psr6CacheStorage(
        new FilesystemAdapter()
      )
    )
  ), 'cache');

  return new Client([
    'timeout' => 2,
    'handler' => $handlerStack,
  ]);
});

$app['jwt.keyset'] = Pimple::share(function (Application $app): JWKSet {
  return (new JKUFactory($app['jwt.http_client'], new HttpFactory()))->loadFromUrl(
    getenv('REDQUEEN_JWT_KEYSET_URL'),
  );
});

$app['jwt.rs256_algorithm'] = Pimple::share(function (Application $app): RS256 {
  return new RS256();
});

$app['jwt.header_checker'] = Pimple::share(function (Application $app): HeaderCheckerManager {
  return new HeaderCheckerManager([
    new AlgorithmChecker([$app['jwt.rs256_algorithm']->name()]),
  ], [
    new JWSTokenSupport(),
  ]);
});

$app['jwt.claim_checker'] = Pimple::share(function (Application $app): ClaimCheckerManager {
  return new ClaimCheckerManager([
    new AudienceChecker(getenv('REDQUEEN_JWT_AUDIENCE')),
    new NotBeforeChecker(),
    new IssuedAtChecker(),
    new ExpirationTimeChecker(),
    new IssuerChecker([getenv('REDQUEEN_JWT_ISSUER')]),
  ]);
});

$app['jwt.jws_verifier'] = Pimple::share(function (Application $app): JWSVerifier {
  $algorithmManager = new AlgorithmManager([$app['jwt.rs256_algorithm']]);
  return new JWSVerifier($algorithmManager);
});

$app->before(function (Request $request, Application $app): ?Response {
  if (getenv('REDQUEEN_JWT_DISABLED') === 'true') {
    return null;
  }

  $jwtString = $request->cookies->get('CF_AUTHORIZATION') ?? $request->headers->get('Cf-Access-Jwt-Assertion');

  if ($jwtString === null) {
    return Response::create('', 401);
  }

  try {
    $jws = (new CompactSerializer())->unserialize($jwtString);
    $app['jwt.header_checker']->check($jws, 0, ['alg']);
    $claims = json_decode($jws->getPayload(), true, JSON_THROW_ON_ERROR);
    $app['jwt.claim_checker']->check($claims, ['aud', 'nbf', 'iat', 'exp', 'iss']);
  } catch (InvalidClaimException $claimException) {
    return Response::create(sprintf('Invalid Claim "%s"', $claimException->getClaim()) , 401);
  } catch (MissingMandatoryClaimException $claimException) {
    return Response::create(sprintf('Missing Claims "%s"', implode('", "', $claimException->getClaims())), 401);
  } catch (InvalidHeaderException $headerException) {
    return Response::create(sprintf('Invalid Header "%s"', $headerException->getHeader()), 401);
  }

  if (!$app['jwt.jws_verifier']->verifyWithKeySet($jws, $app['jwt.keyset'], 0)) {
    return Response::create('', 401);
  }

  return null;
});
