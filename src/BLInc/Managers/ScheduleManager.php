<?php

declare(strict_types=1);

namespace BLInc\Managers;

use Doctrine\DBAL\Connection;

class ScheduleManager extends TimestampedManager
{
    public function findByCards(array $cardIds): iterable
    {
        $query = 'SELECT s.*,cs.card_id FROM schedules s LEFT JOIN card_schedule cs ON (s.id = cs.schedule_id) WHERE cs.card_id IN (:cardIds)';
        return array_map([$this, 'transformRow'], $this->dbal->fetchAll($query, ['cardIds' => $cardIds], ['cardIds' => Connection::PARAM_INT_ARRAY]));
    }

    protected function getFindAllQuery(): string
    {
        return 'SELECT s.*, COUNT(cs.card_id) AS number_of_cards FROM schedules s LEFT JOIN card_schedule cs on s.id = cs.schedule_id GROUP BY s.id';
    }

    protected function transformRow(array $data)
    {
        $data['mon'] = (bool) $data['mon'];
        $data['tue'] = (bool) $data['tue'];
        $data['wed'] = (bool) $data['wed'];
        $data['thu'] = (bool) $data['thu'];
        $data['fri'] = (bool) $data['fri'];
        $data['sat'] = (bool) $data['sat'];
        $data['sun'] = (bool) $data['sun'];

        return $data;
    }

    public function update($id, array $data)
    {
        $data['mon'] = $data['mon'] ? 1 : 0;
        $data['tue'] = $data['tue'] ? 1 : 0;
        $data['wed'] = $data['wed'] ? 1 : 0;
        $data['thu'] = $data['thu'] ? 1 : 0;
        $data['fri'] = $data['fri'] ? 1 : 0;
        $data['sat'] = $data['sat'] ? 1 : 0;
        $data['sun'] = $data['sun'] ? 1 : 0;

        return $this->dbal->transactional(function () use ($id, $data) {
            if (isset($data['doors'])) {
                $doors = $data['doors'];
                unset($data['doors']);

                $doorIds = [];
                foreach($doors as $door) {
                    $doorIds[] = $door['id'];
                }

                $currentDoorIds = $this->getDoorIds($id);

                $doorsToRemove = array_diff($currentDoorIds, $doorIds);

                foreach ($doorsToRemove as $removeId) {
                    $this->removeDoor($id, $removeId);
                }

                $doorsToAdd = array_diff($doorIds, $currentDoorIds);

                foreach ($doorsToAdd as $addId) {
                    $this->addDoor($id, $addId);
                }
            }

            return parent::update($id, $data);
        });
    }

    public function create(array $data)
    {
        $doors = [];
        if (isset($data['doors'])) {
            $doors = $data['doors'];
            unset($data['doors']);
        }

        $data['mon'] = $data['mon'] ? 1 : 0;
        $data['tue'] = $data['tue'] ? 1 : 0;
        $data['wed'] = $data['wed'] ? 1 : 0;
        $data['thu'] = $data['thu'] ? 1 : 0;
        $data['fri'] = $data['fri'] ? 1 : 0;
        $data['sat'] = $data['sat'] ? 1 : 0;
        $data['sun'] = $data['sun'] ? 1 : 0;

        return $this->dbal->transactional(function () use ($data, $doors) {
            $id = parent::create($data);

            if (count($doors) > 0) {
                foreach ($doors as $door) {
                    $this->addDoor($id, $door['id']);
                }
            }

            return $id;
        });
    }

    public function delete($id)
    {
        return $this->dbal->transactional(function () use ($id) {
            $this->dbal->delete('door_schedule', ['schedule_id' => $id]);
            parent::delete($id);
        });
    }

    public function getTable()
    {
        return 'schedules';
    }

    protected function getDoorIds($id)
    {
        $query = 'SELECT door_id FROM door_schedule WHERE schedule_id = :scheduleId';

        $rows = $this->dbal->fetchAll($query, ['scheduleId' => $id]);

        $ids = [];
        foreach($rows as $row) {
            $ids[] = $row['door_id'];
        }

        return $ids;
    }

    public function addDoor($id, $doorId)
    {
        $this->dbal->transactional(function () use ($id, $doorId) {
            $doorIds = $this->getDoorIds($id);

            if (false === in_array($doorId, $doorIds)) {
                $this->dbal->insert('door_schedule', [
                    'schedule_id' => $id,
                    'door_id' => $doorId,
                ]);
            }
        });
    }

    public function removeDoor($id, $doorId)
    {
        $this->dbal->transactional(function () use ($id, $doorId) {
            $doorIds = $this->getDoorIds($id);

            if (in_array($doorId, $doorIds)) {
                $this->dbal->delete('door_schedule', [
                    'schedule_id' => $id,
                    'door_id' => $doorId,
                ]);
            }
        });
    }
}
