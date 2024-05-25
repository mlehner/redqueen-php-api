<?php

declare(strict_types=1);

namespace BLInc\Test;

use Symfony\Component\HttpKernel\Client;

trait TestClientTrait
{
  private static function createClient(): Client
  {
    global $app;

    return new Client($app);
  }
}
