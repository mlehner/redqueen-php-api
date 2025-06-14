<?php

declare(strict_types=1);

use BLInc\Test\TestClientTrait;
use BLInc\Test\TestDatabaseTrait;
use BLInc\Test\TestFixtureTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class CardApiTest extends TestCase
{
    use TestDatabaseTrait;
    use TestFixtureTrait;
    use TestClientTrait;

    public function testGetCards(): void
    {
        self::loadData();
        $client = self::createClient();

        $client->request(Request::METHOD_GET, '/api/cards');
        self::assertSame(200, $client->getResponse()->getStatusCode());
        self::assertSame('application/json', $client->getResponse()->headers->get('Content-Type'));
        self::assertJsonStringEqualsJsonString(json_encode(self::getDefaultCardList(), JSON_THROW_ON_ERROR), $client->getResponse()->getContent());
    }

    public function testGetCard(): void
    {
       self::loadData();
       $client = self::createClient();

       $expectedResponse = self::getDefaultCardList()['items'][0];
       unset($expectedResponse['schedules'][0]['name'], $expectedResponse['schedules'][1]['name']);
       $expectedResponse['deleted_at'] = null;

       $client->request(Request::METHOD_GET, '/api/cards/1');
       self::assertSame(200, $client->getResponse()->getStatusCode());
       self::assertSame('application/json', $client->getResponse()->headers->get('Content-Type'));
       self::assertJsonStringEqualsJsonString(json_encode($expectedResponse, JSON_THROW_ON_ERROR), $client->getResponse()->getContent());
    }

    public function testPostCard(): void
    {
        self::loadData();
        $client = self::createClient();

        $client->request(Request::METHOD_POST, '/api/cards', [], [], [], json_encode([
            'name' => 'Person Three',
            'facilityCode' => '240',
            'cardNumber' => '40962',
            'code' => 'F0A002',
            'pin' => '0987',
            'isActive' => true,
            'schedules' => [],
        ], JSON_THROW_ON_ERROR));
        self::assertSame(201, $client->getResponse()->getStatusCode());
        self::assertSame('application/json', $client->getResponse()->headers->get('Content-Type'));
        self::assertSame('/api/cards/5', $client->getResponse()->headers->get('Location'));
        self::assertSame('{}', $client->getResponse()->getContent());

        $expectedResponse = [
            'id' => '5',
            'name' => 'Person Three',
            'facilityCode' => 240,
            'cardNumber' => 40962,
            'code' => 'F0A002',
            'isActive' => true,
            'schedules' => [],
            'deleted_at' => null,
        ];

        $client->request(Request::METHOD_GET, '/api/cards/5');
        self::assertSame(200, $client->getResponse()->getStatusCode());
        self::assertSame('application/json', $client->getResponse()->headers->get('Content-Type'));
        self::assertJson($client->getResponse()->getContent());

        $cardResponse = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $expectedResponse['created_at'] = $cardResponse['created_at'];
        $expectedResponse['updated_at'] = $cardResponse['updated_at'];

        self::assertJsonStringEqualsJsonString(json_encode($expectedResponse, JSON_THROW_ON_ERROR), $client->getResponse()->getContent());

        $cards = self::getDefaultCardList();
        $cards['count']++;
        unset($expectedResponse['deleted_at']);
        $cards['items'][] = $expectedResponse;

        $client->request(Request::METHOD_GET, '/api/cards');
        self::assertJsonStringEqualsJsonString(json_encode($cards, JSON_THROW_ON_ERROR), $client->getResponse()->getContent());
    }

    /**
     * @dataProvider getPutCardCases
     */
    public function testPutCard(array $requestOverrides, array $expectedOverrides): void
    {
        self::loadData();
        $client = self::createClient();

        $client->request(Request::METHOD_PUT, '/api/cards/1', [], [], [], json_encode(array_merge([
            'name' => 'Person One',
            'facilityCode' => '240',
            'cardNumber' => '40960',
            'code' => 'F0A000',
            'isActive' => true,
            'schedules' => [],
        ], $requestOverrides), JSON_THROW_ON_ERROR));
        self::assertSame(201, $client->getResponse()->getStatusCode());
        self::assertSame('application/json', $client->getResponse()->headers->get('Content-Type'));
        self::assertSame('/api/cards/5', $client->getResponse()->headers->get('Location'));
        self::assertSame('{}', $client->getResponse()->getContent());

        $expectedResponse = array_merge([
            'id' => '5',
            'name' => 'Person One',
            'facilityCode' => 240,
            'cardNumber' => 40960,
            'code' => 'F0A000',
            'isActive' => true,
            'schedules' => [],
            'deleted_at' => null,
        ], $expectedOverrides);

        $client->request(Request::METHOD_GET, '/api/cards/5');
        self::assertSame(200, $client->getResponse()->getStatusCode());
        self::assertSame('application/json', $client->getResponse()->headers->get('Content-Type'));
        self::assertJson($client->getResponse()->getContent());

        $cardResponse = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $expectedResponse['created_at'] = $cardResponse['created_at'];
        $expectedResponse['updated_at'] = $cardResponse['updated_at'];

        self::assertJsonStringEqualsJsonString(json_encode($expectedResponse, JSON_THROW_ON_ERROR), $client->getResponse()->getContent());
    }

    public static function getPutCardCases(): iterable
    {
        yield 'change name' => [
            ['name' => 'Person Um'],
            ['name' => 'Person Um'],
        ];
        yield 'code' => [
            ['code' => 'F0A000'],
            ['code' => 'F0A000', 'facilityCode' => 240, 'cardNumber' => 40960],
        ];
        yield 'is_active' => [
            ['isActive' => false],
            ['isActive' => false],
        ];
        yield 'add schedule' => [
            ['schedules' => [['id' => '1'], ['id' => '2'], ['id' => '3']]],
            ['schedules' => [
                ['id' => '1'],
                ['id' => '2'],
                ['id' => '3'],
            ]],
        ];
        yield 'remove schedule' => [
            ['schedules' => [['id' => '2']]],
            ['schedules' => [
                ['id' => '2'],
            ]]
        ];
        yield 'replace schedule' => [
            ['schedules' => [['id' => '1'], ['id' => '3']]],
            ['schedules' => [
                ['id' => '1'],
                ['id' => '3'],
            ]]
        ];
    }

    private static function getDefaultCardList(): array
    {
        return [
            'items' => [
                [
                    'id' => '1',
                    'name' => 'Person One',
                    'facilityCode' => 240,
                    'cardNumber' => 40960,
                    'code' => 'F0A000',
                    'isActive' => true,
                    'created_at' => '2022-08-20 10:00:00',
                    'updated_at' => '2022-08-20 10:00:00',
                    'schedules' => [
                        [
                            'id' => '1',
                            'name' => '24/7 Exterior',
                        ],
                        [
                            'id' => '2',
                            'name' => '24/7 Interior',
                        ],
                    ],
                ],
                [
                    'id' => '2',
                    'name' => 'Person Two',
                    'facilityCode' => 240,
                    'cardNumber' => 40961,
                    'code' => 'F0A001',
                    'isActive' => false,
                    'created_at' => '2022-08-20 10:00:00',
                    'updated_at' => '2022-08-20 10:00:00',
                    'schedules' => [
                        [
                            'id' => '1',
                            'name' => '24/7 Exterior',
                        ],
                        [
                            'id' => '4',
                            'name' => 'Mon-Fri All Day Interior',
                        ],
                    ],
                ],
                [
                    'id' => '4',
                    'name' => 'Person Three',
                    'facilityCode' => 240,
                    'cardNumber' => 40964,
                    'code' => 'F0A004',
                    'isActive' => true,
                    'created_at' => '2022-08-20 10:00:00',
                    'updated_at' => '2022-08-20 10:00:00',
                    'schedules' => [
                        [
                            'id' => '1',
                            'name' => '24/7 Exterior',
                        ],
                        [
                            'id' => '2',
                            'name' => '24/7 Interior',
                        ],
                    ],
                ],
            ],
            'count' => 3,
        ];
    }
}
