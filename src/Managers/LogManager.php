<?php

declare(strict_types=1);

namespace BLInc\Managers;

use BLInc\Model\CardSerialNumber;
use Doctrine\DBAL\Query\QueryBuilder;

final class LogManager extends TimestampedManager
{
    public function getTable(): string
    {
        return 'logs';
    }

    public function findLatestSince(?\DateTimeInterface $sinceDateTime = null): array
    {
        if ($sinceDateTime === null) {
            $sinceDateTime = new \DateTimeImmutable();
        }

        $rows = $this
            ->getFindAllQueryBuilder()
            ->andWhere('l.created_at < :sinceDateTime')
            ->setParameter('sinceDateTime', $sinceDateTime->format('Y-m-d H:i:s'))
            ->execute()
            ->fetchAllAssociative()
        ;

        return array_map([$this, 'transformRow'], $rows);
    }

    protected function getFindAllQueryBuilder(): QueryBuilder
    {
        return $this->dbal->createQueryBuilder()
            ->select(
                'l.id',
                'l.code',
                'l.validPin',
                'l.created_at',
                'MAX(cf.name) AS name',
                'c.id AS card__id',
                'c.name AS card__name',
                'l.door_identifier AS door__identifier',
                'd.id AS door__id',
                'd.name AS door__name',
            )
            ->from('logs', 'l')
            ->leftJoin('l', 'cards', 'c', 'l.card_id = c.id')
            ->leftJoin('l', 'cards', 'cf', 'l.code = cf.code AND l.created_at >= cf.created_at')
            ->leftJoin('l', 'doors', 'd', 'l.door_identifier = d.identifier')
            ->groupBy('l.id')
            ->orderBy('l.created_at', 'DESC')
            ->setMaxResults(100)
        ;
    }

    protected function transformRow(array $data): array
    {
        $data['id'] = (string) $data['id'];

        try {
          $csn = CardSerialNumber::createFromHex($data['code']);

          $data['facilityCode'] = $csn->getFacilityCode();
          $data['cardNumber'] = $csn->getCardNumber();
        } catch (\Throwable $e) {
        }

        $data['validPin'] = (bool) $data['validPin'];

        $data['createdAt'] = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $data['created_at'])->format(\DateTime::ATOM);
        unset($data['created_at']);

        foreach ($data as $key => $value) {
            if (!strpos($key, '__')) {
                continue;
            }

            list($relation, $property) = explode('__', $key, 2);

            if (!isset($data[$relation])) {
                $data[$relation] = [];
            }

            if ($property === 'id' && is_int($value)) {
                $value = (string) $value;
            }

            $data[$relation][$property] = $value;
            unset($data[$key]);
        }

        return $data;
    }
}
