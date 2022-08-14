<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

$app = new Silex\Application();
$app['debug'] = isset($_SERVER['APP_DEBUG']) ? (bool) $_SERVER['APP_DEBUG'] : (isset($_ENV['APP_DEBUG']) ? (bool) $_ENV['APP_DEBUG'] : false);

require_once __DIR__ . '/providers.php';
