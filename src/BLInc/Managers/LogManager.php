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

    public function findLatestSince(?\DateTimeInterface $sinceDateTime = null): array
    {
      if ($sinceDateTime === null) {
        $sinceDateTime = new \DateTimeImmutable();
      }

      $rows = $this->dbal->fetchAll(
        <<<SQL
        SELECT
            l.id, l.code, l.validPin, l.created_at, MAX(c.name) AS name
        FROM `logs` AS l
            LEFT JOIN `cards` AS c ON (l.code = c.code)
        WHERE l.created_at < :sinceDateTime
        GROUP BY l.id
        ORDER BY l.created_at
        DESC LIMIT 100
SQL,
        [
          'sinceDateTime' => $sinceDateTime->format('Y-m-d H:i:s')
        ]
      );

      return array_map([$this, 'transformRow'], $rows);
    }

    protected function getFindAllQuery(): string
    {
        return 'SELECT l.id, l.code, l.validPin, l.created_at, MAX(c.name) AS name FROM `logs` AS l LEFT JOIN `cards` AS c ON (l.code = c.code) GROUP BY l.id ORDER BY l.created_at DESC LIMIT 100';
    }

    protected function transformRow(array $data)
    {
        $csn = CardSerialNumber::createFromHex($data['code']);

        $data['facilityCode'] = $csn->getFacilityCode();
        $data['cardNumber'] = $csn->getCardNumber();

        $data['createdAt'] = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $data['created_at'])->format(\DateTime::ATOM);
        unset($data['created_at']);

        return $data;
    }
}
