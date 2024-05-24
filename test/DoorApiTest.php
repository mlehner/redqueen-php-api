<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Client;

final class DoorApiTest extends TestCase {
  public function testGetDoors(): void
  {
    global $app;

    putenv('REDQUEEN_JWT_DISABLED=true');

    $client = new Client($app);

    $client->request(Request::METHOD_GET, '/api/doors');
    self::assertSame(200, $client->getResponse()->getStatusCode());
  }
}
