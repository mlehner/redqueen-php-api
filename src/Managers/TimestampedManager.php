<?php

declare(strict_types=1);

namespace BLInc\Managers;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;

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
        $data = $this->findInternal($id);

        return is_array($data) ? $this->transformRow($data) : null;
    }

    protected function getFindOneQuery()
    {
        return $this->getFindOneQueryBuilder()->getSQL();
    }

    protected function getFindOneQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder()
            ->where('id = :id')
        ;
    }

    protected function createQueryBuilder(): QueryBuilder
    {
        $tableName = $this->getTable();

        return $this->dbal->createQueryBuilder()
            ->select($this->dbal->quoteIdentifier($tableName) . '.*')
            ->from($tableName)
        ;
    }

    public function findAll()
    {
        $rows = $this->dbal->fetchAllAssociative($this->getFindAllQuery());

        return array_map([$this, 'transformRow'], $rows);
    }

    protected function getFindAllQuery()
    {
        return $this->getFindAllQueryBuilder()->getSQL();
    }

    protected function getFindAllQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder();
    }

    public function create(array $data)
    {
        $data = array_merge($data, [
            'created_at' => date_create()->format(self::DATETIME_FORMAT),
            'updated_at' => date_create()->format(self::DATETIME_FORMAT),
        ]);

        unset($data['id']);

        $this->dbal->insert($this->getTable(), $data);

        return $this->dbal->lastInsertId();
    }

    public function update($id, array $data)
    {
        return $this->updateInternal($id, array_merge($data, [
            'updated_at' => date_create()->format(self::DATETIME_FORMAT),
        ]));
    }

    public function delete($id): bool
    {
        return $this->updateInternal($id, [
            'deleted_at' => date_create()->format(self::DATETIME_FORMAT),
        ]);
    }

    protected function updateInternal($id, array $data): bool
    {
        $this->dbal->update($this->getTable(), $data, ['id' => $id]);

        // @TODO check modified rows
        return true;
    }

    protected function findInternal(string $id): ?array
    {
        $data = $this->dbal->fetchAssociative($this->getFindOneQuery(), ['id' => $id]);

        return is_array($data) ? $data : null;
    }
}
