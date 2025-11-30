<?php

declare(strict_types=1);

namespace BLInc\Test;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;

trait TestClientTrait
{
    private static function createClient(): KernelBrowser
    {
        return static::getContainer()->get('test.client');
    }
}
