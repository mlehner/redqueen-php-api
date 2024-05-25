<?php

declare(strict_types=1);

use BLInc\Test\TestClientTrait;
use BLInc\Test\TestDatabaseTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class DoorApiTest extends TestCase
{
  use TestDatabaseTrait;
  use TestClientTrait;

  /**
   * @covers \BLInc\Controller\DoorController::getDoors
   */
  public function testGetDoors(): void
  {
    self::loadData();
    $client = self::createClient();

    $client->request(Request::METHOD_GET, '/api/doors');
    self::assertSame(200, $client->getResponse()->getStatusCode());
    self::assertSame('application/json', $client->getResponse()->headers->get('Content-Type'));
    self::assertJson($client->getResponse()->getContent());
    self::assertJsonStringEqualsJsonString(json_encode(self::getDefaultDoorList()), $client->getResponse()->getContent());
  }

  /**
   * @covers \BLInc\Controller\DoorController::getDoor
   */
  public function testGetDoor(): void
  {
    self::loadData();
    $client = self::createClient();

    $client->request(Request::METHOD_GET, '/api/doors/5');
    self::assertSame(404, $client->getResponse()->getStatusCode());

    $client->request(Request::METHOD_GET, '/api/doors/1');
    self::assertSame(200, $client->getResponse()->getStatusCode());
    self::assertSame('application/json', $client->getResponse()->headers->get('Content-Type'));
    self::assertJsonStringEqualsJsonString(json_encode([
      'id' => '1',
      'name' => 'Door 1',
      'identifier' => 'InteriorDoor',
      'created_at' => '2024-05-01 08:00:00',
      'updated_at' => '2024-05-01 08:00:00',
    ]), $client->getResponse()->getContent());
  }

  /**
   * @covers \BLInc\Controller\DoorController::postDoor
   */
  public function testPostDoor(): void
  {
    self::loadData();
    $client = self::createClient();

    $client->request(Request::METHOD_POST, '/api/doors', [], [], [], json_encode([
      'name' => 'Interior Door',
      'identifier' => 'Door 1',
    ]));
    self::assertSame(201, $client->getResponse()->getStatusCode());
    self::assertSame('/api/doors/4', $client->getResponse()->headers->get('Location'));

    $client->request(Request::METHOD_GET, '/api/doors/4');
    self::assertSame(200, $client->getResponse()->getStatusCode());

    $doorResponse = json_decode($client->getResponse()->getContent(), true);

    $door = [
      'id' => '4',
      'name' => 'Interior Door',
      'identifier' => 'Door 1',
      'created_at' => $doorResponse['created_at'],
      'updated_at' => $doorResponse['updated_at'],
    ];

    self::assertJsonStringEqualsJsonString(json_encode($door), $client->getResponse()->getContent());

    $client->request(Request::METHOD_GET, '/api/doors');

    $doors = self::getDefaultDoorList();
    $doors['count']++;
    $doors['items'][] = $door;

    self::assertJsonStringEqualsJsonString(json_encode($doors), $client->getResponse()->getContent());
  }

  /**
   * @covers \BLInc\Controller\DoorController::putDoor
   */
  public function testPutDoor(): void
  {
    self::loadData();
    $client = self::createClient();

    $client->request(Request::METHOD_PUT, '/api/doors/5', [], [], [], json_encode([
      'name' => 'Interior Door',
      'identifier' => 'Door 1',
    ]));
    self::assertSame(404, $client->getResponse()->getStatusCode());
  }

  private static function loadData(): void
  {
    self::primaryConnection()->query(<<<'SQL'
INSERT INTO `doors` VALUES
(null, 'Door 1', 'InteriorDoor', '2024-05-01 08:00:00', '2024-05-01 08:00:00'),
(null, 'Door 2', 'ExteriorDoor', '2024-05-01 08:00:00', '2024-05-01 08:00:00'),
(null, 'Door 3', 'OfficeDoor', '2024-05-01 08:00:00', '2024-05-01 08:00:00')
SQL);
  }

  private static function getDefaultDoorList(): array
  {
    return [
      'count' => 3,
      'items' => [
        [
          'id' => '1',
          'name' => 'Door 1',
          'identifier' => 'InteriorDoor',
          'created_at' => '2024-05-01 08:00:00',
          'updated_at' => '2024-05-01 08:00:00',
        ],
        [
          'id' => '2',
          'name' => 'Door 2',
          'identifier' => 'ExteriorDoor',
          'created_at' => '2024-05-01 08:00:00',
          'updated_at' => '2024-05-01 08:00:00',
        ],
        [
          'id' => '3',
          'name' => 'Door 3',
          'identifier' => 'OfficeDoor',
          'created_at' => '2024-05-01 08:00:00',
          'updated_at' => '2024-05-01 08:00:00',
        ],
      ]
    ];
  }
}
