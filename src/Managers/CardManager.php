<?php

declare(strict_types=1);

namespace BLInc\Managers;

use BLInc\Model\CardSerialNumber;
use Doctrine\DBAL\Query\QueryBuilder;

class CardManager extends TimestampedManager
{
    private const TABLE_NAME = 'cards';

    public function getTable(): string
    {
        return self::TABLE_NAME;
    }

    protected function transformRow(array $data)
    {
        $data['id'] = (string) $data['id'];
        $data['isActive'] = (bool) $data['isActive'];

        try {
            $csn = CardSerialNumber::createFromHex($data['code']);

            $data['facilityCode'] = $csn->getFacilityCode();
            $data['cardNumber'] = $csn->getCardNumber();
        } catch (\Throwable $e) {
            error_log(sprintf('Invalid Card CSN: %s', $data['code']));
        }

        return $data;
    }

    public function update($id, array $data): string
    {
        return $this->dbal->transactional(function () use ($id, $data): string {
            $originalCard = $this->findInternal($id);
            $originalCard['schedules'] = array_map(fn($id) => ['id' => (string) $id], $this->getScheduleIds($id));
            $this->delete($id);
            return $this->create(array_merge($originalCard, $data));
        });
    }

    public function create(array $data): string
    {
        $schedules = [];
        if (isset($data['schedules'])) {
            $schedules = $data['schedules'];
            unset($data['schedules']);
        }

        $data['isActive'] = $data['isActive'] ? 1 : 0;
        $data['deleted_at'] = null;

        return $this->dbal->transactional(function () use ($data, $schedules): string {
            $id = parent::create($data);

            if (count($schedules) > 0) {
                foreach ($schedules as $schedule) {
                    if (isset($schedule['id'])) {
                        $this->addSchedule($id, $schedule['id']);
                    }
                }
            }

            return $id;
        });
    }

    public function find($id)
    {
        $result = parent::find($id);

        unset($result['pin']);

        $result['schedules'] = array_map(fn($id) => ['id' => (string) $id], $this->getScheduleIds($id));

        return $result;
    }

    public function findAll()
    {
        $results = parent::findAll();

        return array_map(function ($card) {
            unset($card['pin']);

            return $card;
        }, $results);
    }

    protected function getScheduleIds($id)
    {
        $query = 'SELECT schedule_id FROM card_schedule WHERE card_id = :cardId';

        $rows = $this->dbal->fetchAllAssociative($query, ['cardId' => $id]);

        $ids = [];
        foreach ($rows as $row) {
            $ids[] = $row['schedule_id'];
        }

        return $ids;
    }

    public function addSchedule($id, $scheduleId)
    {
        $scheduleIds = $this->getScheduleIds($id);

        if (false === in_array($scheduleId, $scheduleIds)) {
            $this->dbal->insert('card_schedule', [
                'card_id' => $id,
                'schedule_id' => $scheduleId,
            ]);
        }
    }

    public function removeSchedule($id, $scheduleId)
    {
        $scheduleIds = $this->getScheduleIds($id);

        if (in_array($scheduleId, $scheduleIds)) {
            $this->dbal->delete('card_schedule', [
                'card_id' => $id,
                'schedule_id' => $scheduleId,
            ]);
        }
    }

    protected function getFindOneQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder()
            ->addSelect('c.pin')
            ->andWhere('id = :id')
            ->addSelect('c.deleted_at')
        ;
    }

    protected function getFindAllQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder()
            ->where('deleted_at IS NULL')
        ;
    }

    protected function createQueryBuilder(): QueryBuilder
    {
        return $this->dbal->createQueryBuilder()
            ->select(
                'c.id',
                'c.name',
                'c.code',
                'c.isActive',
                'c.created_at',
                'c.updated_at',
            )
            ->from(self::TABLE_NAME, 'c')
            ->orderBy('c.created_at', 'DESC')
            ->addOrderBy('c.id', 'ASC')
        ;
    }
}
