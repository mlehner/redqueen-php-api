<?php

declare(strict_types=1);

use Symfony\Component\Dotenv\Dotenv;

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = new Dotenv();
$dotenv->bootEnv(__DIR__.'/../.env');

$app = new Silex\Application();
$app['debug'] = $_ENV['APP_DEBUG'];

require_once __DIR__ . '/providers.php';
