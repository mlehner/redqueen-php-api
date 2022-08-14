<?php

declare(strict_types=1);

namespace BLInc\Managers;

interface ManagerInterface {
    public function find($id);
    public function findAll();

    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
}
