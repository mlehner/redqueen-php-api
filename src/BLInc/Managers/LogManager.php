<?php

declare(strict_types=1);

namespace BLInc\Managers;

use BLInc\Model\CardSerialNumber;

class LogManager extends TimestampedManager
{
    public function getTable()
    {
        return 'logs';
    }

    protected function getFindAllQuery()
    {
        return 'SELECT l.id, l.code, l.validPin, l.created_at, c.name FROM `logs` AS l LEFT JOIN `cards` AS c ON (l.code = c.code) GROUP BY l.id ORDER BY l.created_at DESC LIMIT 100';
    }

    protected function transformRow(array $data)
    {
        $csn = CardSerialNumber::createFromHex($data['code']);

        $data['facilityCode'] = $csn->getFacilityCode();
        $data['cardNumber'] = $csn->getCardNumber();

        $data['created_at'] = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $data['created_at'])->format(\DateTime::ATOM);

        return $data;
    }
}
