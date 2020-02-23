<?php

namespace BLInc\Managers;

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
        $data['facilityCode'] = \hexdec(\substr($data['code'], 0, 2));
        $data['cardNumber'] = \hexdec(\substr($data['code'], 3));

        return $data;
    }
}
