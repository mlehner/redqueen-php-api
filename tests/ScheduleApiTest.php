<?php

declare(strict_types=1);

use BLInc\Test\TestClientTrait;
use BLInc\Test\TestDatabaseTrait;
use BLInc\Test\TestFixtureTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;

final class ScheduleApiTest extends KernelTestCase
{
    use TestDatabaseTrait;
    use TestFixtureTrait;
    use TestClientTrait;

    public function testGetSchedules(): void
    {
        self::loadData();
        $client = self::createClient();

        $client->request(Request::METHOD_GET, '/api/schedules');
        self::assertEquals(200, $client->getResponse()->getStatusCode());
        self::assertSame('application/json', $client->getResponse()->headers->get('Content-Type'));
        self::assertJsonStringEqualsJsonString(json_encode(self::getDefaultScheduleList()), $client->getResponse()->getContent());
    }

    public function testGetSchedule(): void
    {
        self::loadData();

        $client = self::createClient();

        $client->request(Request::METHOD_GET, '/api/schedules/10');
        self::assertEquals(404, $client->getResponse()->getStatusCode());

        $client->request(Request::METHOD_GET, '/api/schedules/1');
        self::assertEquals(200, $client->getResponse()->getStatusCode());
        self::assertSame('application/json', $client->getResponse()->headers->get('Content-Type'));
        self::assertJsonStringEqualsJsonString(json_encode([
            'id' => '1',
            'name' => '24/7 Exterior',
            'mon' => true,
            'tue' => true,
            'wed' => true,
            'thu' => true,
            'fri' => true,
            'sat' => true,
            'sun' => true,
            'startTime' => '00:00:00',
            'endTime' => '23:59:59',
            'created_at' => '2024-05-12 08:00:00',
            'updated_at' => '2024-05-12 08:00:00',
            'authenticationMode' => 'card_pin',
            'doors' => [
                ['id' => '1', 'name' => 'Outside Door'],
            ],
        ]), $client->getResponse()->getContent());
    }

    public function testPostSchedule(): void
    {
        self::loadData();

        $client = self::createClient();

        $client->request(Request::METHOD_GET, '/api/schedules/5');
        self::assertEquals(404, $client->getResponse()->getStatusCode());

        $client->request(Request::METHOD_POST, '/api/schedules', [], [], [], json_encode([
            'name' => 'Staff Exterior',
            'mon' => true,
            'tue' => true,
            'wed' => true,
            'thu' => true,
            'fri' => true,
            'sat' => true,
            'sun' => true,
            'startTime' => '00:00:00',
            'endTime' => '23:59:59',
            'authenticationMode' => 'card_pin',
            'doors' => [
                ['id' => '1'],
            ],
        ]));
        self::assertSame(201, $client->getResponse()->getStatusCode());
        self::assertSame('/api/schedules/5', $client->getResponse()->headers->get('Location'));

        $client->request(Request::METHOD_GET, '/api/schedules/5');
        self::assertEquals(200, $client->getResponse()->getStatusCode());
        self::assertSame('application/json', $client->getResponse()->headers->get('Content-Type'));

        $scheduleResponse = json_decode($client->getResponse()->getContent(), true);

        $schedule = [
            'id' => '5',
            'name' => 'Staff Exterior',
            'mon' => true,
            'tue' => true,
            'wed' => true,
            'thu' => true,
            'fri' => true,
            'sat' => true,
            'sun' => true,
            'startTime' => '00:00:00',
            'endTime' => '23:59:59',
            'created_at' => $scheduleResponse['created_at'],
            'updated_at' => $scheduleResponse['updated_at'],
            'authenticationMode' => 'card_pin',
            'doors' => [
                ['id' => '1', 'name' => 'Outside Door'],
            ],
        ];

        self::assertJsonStringEqualsJsonString(json_encode($schedule), $client->getResponse()->getContent());

        $client->request(Request::METHOD_GET, '/api/schedules');

        $schedule['number_of_cards'] = '0';

        $schedules = self::getDefaultScheduleList();
        $schedules['count']++;
        $schedules['items'][] = $schedule;

        self::assertJsonStringEqualsJsonString(json_encode($schedules), $client->getResponse()->getContent());
    }

    public function testPutScheduleInvalidSchedule(): void
    {
        self::loadData();
        $client = self::createClient();

        $client->request(Request::METHOD_PUT, '/api/schedules/5', [], [], [], json_encode([
            'id' => '5',
            'name' => 'Staff Exterior',
            'mon' => true,
            'tue' => true,
            'wed' => true,
            'thu' => true,
            'fri' => true,
            'sat' => true,
            'sun' => true,
            'startTime' => '00:00:00',
            'endTime' => '23:59:59',
            'authenticationMode' => 'card_pin',
            'doors' => [],
        ]));
        self::assertSame(404, $client->getResponse()->getStatusCode());
    }

    /**
     * @dataProvider getPutScheduleCases
     */
    public function testPutSchedule(array $requestOverrides, array $expectedOverrides): void
    {
        self::loadData();
        $client = self::createClient();

        $client->request(Request::METHOD_PUT, '/api/schedules/3', [], [], [], json_encode(array_merge([
            'id' => '3',
            'name' => 'Mon-Fri 8-6 Exterior',
            'mon' => true,
            'tue' => true,
            'wed' => true,
            'thu' => true,
            'fri' => true,
            'sat' => true,
            'sun' => true,
            'startTime' => '00:00:00',
            'endTime' => '23:59:59',
            'authenticationMode' => 'card_pin',
            'doors' => [
                ['id' => '1'],
            ],
        ], $requestOverrides)));
        self::assertSame(201, $client->getResponse()->getStatusCode());
        self::assertSame('/api/schedules/3', $client->getResponse()->headers->get('Location'));

        $client->request(Request::METHOD_GET, '/api/schedules/3');
        self::assertSame(200, $client->getResponse()->getStatusCode());
        self::assertSame('application/json', $client->getResponse()->headers->get('Content-Type'));

        $scheduleResponse = json_decode($client->getResponse()->getContent(), true);

        $schedule = array_merge([
            'id' => '3',
            'name' => 'Mon-Fri 8-6 Exterior',
            'mon' => true,
            'tue' => true,
            'wed' => true,
            'thu' => true,
            'fri' => true,
            'sat' => true,
            'sun' => true,
            'startTime' => '00:00:00',
            'endTime' => '23:59:59',
            'authenticationMode' => 'card_pin',
            'created_at' => '2024-05-12 08:00:00',
            'updated_at' => $scheduleResponse['updated_at'],
            'doors' => [
                ['id' => '1', 'name' => 'Outside Door'],
            ],
        ], $expectedOverrides);

        self::assertJsonStringEqualsJsonString(json_encode($schedule), $client->getResponse()->getContent());
    }

    public static function getPutScheduleCases(): iterable
    {
        yield 'change name' => [
            ['name' => 'Every Day, All Day, Exterior'],
            ['name' => 'Every Day, All Day, Exterior'],
        ];
        yield 'add door' => [
            ['doors' => [['id' => '1'], ['id' => '2']]],
            ['doors' => [
                ['id' => '1', 'name' => 'Outside Door'],
                ['id' => '2', 'name' => 'Inside Door'],
            ]],
        ];
        yield 'replace door' => [
            ['doors' => [['id' => '2']]],
            ['doors' => [
                ['id' => '2', 'name' => 'Inside Door'],
            ]],
        ];
        yield 'remove door' => [
            ['doors' => []],
            ['doors' => []],
        ];
    }

    private static function getDefaultScheduleList(): array
    {
        return [
            'count' => 4,
            'items' => [
                [
                    'id' => '1',
                    'name' => '24/7 Exterior',
                    'mon' => true,
                    'tue' => true,
                    'wed' => true,
                    'thu' => true,
                    'fri' => true,
                    'sat' => true,
                    'sun' => true,
                    'startTime' => '00:00:00',
                    'endTime' => '23:59:59',
                    'created_at' => '2024-05-12 08:00:00',
                    'updated_at' => '2024-05-12 08:00:00',
                    'authenticationMode' => 'card_pin',
                    'number_of_cards' => '3',
                    'doors' => [
                        ['id' => '1', 'name' => 'Outside Door'],
                    ],
                ],
                [
                    'id' => '2',
                    'name' => '24/7 Interior',
                    'mon' => true,
                    'tue' => true,
                    'wed' => true,
                    'thu' => true,
                    'fri' => true,
                    'sat' => true,
                    'sun' => true,
                    'startTime' => '00:00:00',
                    'endTime' => '23:59:59',
                    'created_at' => '2024-05-12 08:00:00',
                    'updated_at' => '2024-05-12 08:00:00',
                    'authenticationMode' => 'card',
                    'number_of_cards' => '2',
                    'doors' => [
                        ['id' => '2', 'name' => 'Inside Door'],
                    ],
                ],
                [
                    'id' => '3',
                    'name' => 'Mon-Fri 8-6 Exterior',
                    'mon' => true,
                    'tue' => true,
                    'wed' => true,
                    'thu' => true,
                    'fri' => true,
                    'sat' => false,
                    'sun' => false,
                    'startTime' => '08:00:00',
                    'endTime' => '18:00:00',
                    'created_at' => '2024-05-12 08:00:00',
                    'updated_at' => '2024-05-12 08:00:00',
                    'authenticationMode' => 'card_pin',
                    'number_of_cards' => '0',
                    'doors' => [
                        ['id' => '1', 'name' => 'Outside Door'],
                    ],
                ],
                [
                    'id' => '4',
                    'name' => 'Mon-Fri All Day Interior',
                    'mon' => true,
                    'tue' => true,
                    'wed' => true,
                    'thu' => true,
                    'fri' => true,
                    'sat' => false,
                    'sun' => false,
                    'startTime' => '00:00:00',
                    'endTime' => '23:59:59',
                    'created_at' => '2024-05-12 08:00:00',
                    'updated_at' => '2024-05-12 08:00:00',
                    'authenticationMode' => 'card',
                    'number_of_cards' => '1',
                    'doors' => [
                        ['id' => '2', 'name' => 'Inside Door'],
                    ],
                ],
            ],
        ];
    }
}
