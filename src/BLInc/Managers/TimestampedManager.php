<?php

declare(strict_types=1);

namespace BLInc\Managers;

use Doctrine\DBAL\Connection;

abstract class TimestampedManager implements ManagerInterface
{
    protected const DATETIME_FORMAT = 'Y-m-d H:i:s';

    /**
     * @var Connection
     */
    protected $dbal;

    public function __construct(Connection $dbal)
    {
        $this->dbal = $dbal;
    }

    abstract public function getTable();

    protected function transformRow(array $data)
    {
        return $data;
    }

    public function find($id)
    {
        $data = $this->dbal->fetchAssoc($this->getFindOneQuery(), ['id' => $id]);

        return is_array($data) ? $this->transformRow($data) : null;
    }

    protected function getFindOneQuery()
    {
        return sprintf('SELECT * FROM %s WHERE id = :id', $this->getTable());
    }

    public function findAll()
    {
        $rows = $this->dbal->fetchAll($this->getFindAllQuery());

        return array_map([$this, 'transformRow'], $rows);
    }

    protected function getFindAllQuery()
    {
        return sprintf('SELECT * FROM %s', $this->getTable());
    }

    public function create(array $data)
    {
        $data = array_merge($data, [
            'created_at' => date_create()->format(self::DATETIME_FORMAT),
            'updated_at' => date_create()->format(self::DATETIME_FORMAT),
        ]);

        $this->dbal->insert($this->getTable(), $data);

        return $this->dbal->lastInsertId();
    }

    public function update($id, array $data)
    {
        $data = array_merge($data, [
            'updated_at' => date_create()->format(self::DATETIME_FORMAT),
        ]);

        $this->dbal->update($this->getTable(), $data, ['id' => $id]);

        // @TODO check modified rows
        return true;
    }

    public function delete($id)
    {
        return $this->update($id, [
            'deleted_at' => date_create()->format(self::DATETIME_FORMAT)
        ]);
    }
}
