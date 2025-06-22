<?php

declare(strict_types=1);

use BLInc\Test\TestClientTrait;
use BLInc\Test\TestDatabaseTrait;
use BLInc\Test\TestFixtureTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class LogApiTest extends TestCase
{
    use TestDatabaseTrait;
    use TestFixtureTrait;
    use TestClientTrait;

    public function testGetLogs(): void
    {
        self::loadData();
        // make sure data from primary is available
        self::primaryConnection()->commit();
        self::primaryConnection()->beginTransaction();

        $client = self::createClient();
        $client->request(Request::METHOD_GET, '/api/logs');
        self::assertSame(200, $client->getResponse()->getStatusCode());
        self::assertSame('application/json', $client->getResponse()->headers->get('content-type'));

        $logs = [
            'count' => 5,
            'items' => [
                [
                    'id' => '5',
                    'code' => 'F1A004',
                    'facilityCode' => 241,
                    'cardNumber' => 40964,
                    'validPin' => false,
                    'name' => null,
                    'createdAt' => '2025-04-07T12:00:00+00:00',
                    'door' => [
                        'id' => null,
                        'identifier' => null,
                        'name' => null,
                    ],
                    'card' => [
                        'id' => null,
                        'name' => null,
                    ],
                ],
                [
                    'id' => '4',
                    'code' => 'F0A000',
                    'facilityCode' => 240,
                    'cardNumber' => 40960,
                    'validPin' => true,
                    'name' => 'Person Three',
                    'createdAt' => '2025-03-06T14:00:00+00:00',
                    'door' => [
                        'id' => '1',
                        'identifier' => 'out_door',
                        'name' => 'Outside Door',
                    ],
                    'card' => [
                        'id' => '3',
                        'name' => 'Person Three',
                    ],
                ],
                [
                    'id' => '3',
                    'code' => 'F0A000',
                    'facilityCode' => 240,
                    'cardNumber' => 40960,
                    'validPin' => true,
                    'name' => 'Person Three',
                    'createdAt' => '2025-03-05T15:00:00+00:00',
                    'door' => [
                        'id' => '2',
                        'identifier' => 'in_door',
                        'name' => 'Inside Door',
                    ],
                    'card' => [
                        'id' => null,
                        'name' => null,
                    ],
                ],
                [
                    'id' => '2',
                    'code' => 'F0A001',
                    'facilityCode' => 240,
                    'cardNumber' => 40961,
                    'validPin' => true,
                    'name' => 'Person Two',
                    'createdAt' => '2025-02-20T08:05:00+00:00',
                    'door' => [
                        'id' => '2',
                        'identifier' => 'in_door',
                        'name' => 'Inside Door',
                    ],
                    'card' => [
                        'id' => null,
                        'name' => null,
                    ],
                ],
                [
                    'id' => '1',
                    'code' => 'F0A000',
                    'facilityCode' => 240,
                    'cardNumber' => 40960,
                    'validPin' => false,
                    'name' => 'Person Three',
                    'createdAt' => '2025-02-20T08:00:00+00:00',
                    'door' => [
                        'id' => '1',
                        'identifier' => 'out_door',
                        'name' => 'Outside Door',
                    ],
                    'card' => [
                        'id' => null,
                        'name' => null,
                    ],
                ],
            ],
        ];
        self::assertJsonStringEqualsJsonString(json_encode($logs, JSON_THROW_ON_ERROR), $client->getResponse()->getContent());
    }
}
