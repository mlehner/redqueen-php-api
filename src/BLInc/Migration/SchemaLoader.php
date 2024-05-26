<?php

declare(strict_types=1);

namespace BLInc\Migration;

use Doctrine\DBAL\Schema\Schema;

final class SchemaLoader
{
    private array $schemas;

    public function getPrimarySchema(): Schema
    {
        $this->loadSchemas();
        return $this->schemas['primary'];
    }

    public function getLogSchema(): Schema
    {
        $this->loadSchemas();
        return $this->schemas['log'];
    }

    private function loadSchemas()
    {
        if (isset($this->schemas['primary'])) {
            return;
        }

        $this->schemas = require __DIR__ . '/../../../app/schemas.php';
    }
}
