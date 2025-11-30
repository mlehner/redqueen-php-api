<?php

declare(strict_types=1);

namespace BLInc\Test;

trait TestFixtureTrait
{
    use TestDatabaseTrait;

    public static function loadData(): void
    {
        self::primaryConnection()->executeStatement(<<<'SQL'
            INSERT INTO `doors` (id, name, identifier, created_at, updated_at) VALUES
            (1, 'Outside Door', 'out_door', '2024-05-12 08:00:00', '2024-05-12 08:00:00'),
            (2, 'Inside Door', 'in_door', '2024-05-12 08:00:00', '2024-05-12 08:00:00')
            ;

            INSERT INTO `schedules` (id, name, mon, tue, wed, thu, fri, sat, sun, startTime, endTime, created_at, updated_at, authenticationMode) VALUES
            (1, '24/7 Exterior', 1, 1, 1, 1, 1, 1, 1, '00:00:00', '23:59:59', '2024-05-12 08:00:00', '2024-05-12 08:00:00', 'card_pin'),
            (2, '24/7 Interior', 1, 1, 1, 1, 1, 1, 1, '00:00:00', '23:59:59', '2024-05-12 08:00:00', '2024-05-12 08:00:00', 'card'),
            (3, 'Mon-Fri 8-6 Exterior', 1, 1, 1, 1, 1, 0, 0, '08:00:00', '18:00:00', '2024-05-12 08:00:00', '2024-05-12 08:00:00', 'card_pin'),
            (4, 'Mon-Fri All Day Interior', 1, 1, 1, 1, 1, 0, 0, '00:00:00', '23:59:59', '2024-05-12 08:00:00', '2024-05-12 08:00:00', 'card')
            ;

            INSERT INTO `door_schedule` (door_id, schedule_id, created_at) VALUES
            (1, 1, '2024-05-12 08:00:00'),
            (2, 2, '2024-05-12 08:00:00'),
            (1, 3, '2024-05-12 08:00:00'),
            (2, 4, '2024-05-12 08:00:00')
            ;

            INSERT INTO `cards` (id, name, code, pin, isActive, created_at, updated_at, deleted_at) VALUES
            (1, 'Person One', 'F0A000', '0000', 1, '2022-08-20 10:00:00', '2022-08-20 10:00:00', NULL),
            (2, 'Person Two', 'F0A001', '0000', 0, '2022-08-20 10:00:00', '2022-08-20 10:00:00', NULL),
            (3, 'Person Three', 'F0A000', '0000', 1, '2022-08-20 10:00:00', '2022-08-20 10:00:00', '2022-08-20 10:00:00'),
            (4, 'Person Three', 'F0A004', '0000', 1, '2022-08-20 10:00:00', '2022-08-20 10:00:00', NULL)
            ;

            INSERT INTO `card_schedule` (card_id, schedule_id, created_at) VALUES
            (1, 1, '2022-08-20 10:00:00'),
            (1, 2, '2022-08-20 10:00:00'),
            (2, 1, '2022-08-20 10:00:00'),
            (2, 4, '2022-08-20 10:00:00'),
            (3, 1, '2022-08-20 10:00:00'),
            (3, 2, '2022-08-20 10:00:00'),
            (4, 1, '2022-08-20 10:00:00'),
            (4, 2, '2022-08-20 10:00:00')
            ;

            INSERT INTO `logs` (id, card_id, code, validPin, created_at, door_identifier) VALUES
            (1, null, 'F0A000', 0, '2025-02-20 08:00:00', 'out_door'),
            (2, null, 'F0A001', 1, '2025-02-20 08:05:00', 'in_door'),
            (3, null, 'F0A000', 1, '2025-03-05 15:00:00', 'in_door'),
            (4, 3, 'F0A000', 1, '2025-03-06 14:00:00', 'out_door'),
            (5, null, 'F1A004', 0, '2025-04-07 12:00:00', null)
            ;
            SQL);
    }
}
