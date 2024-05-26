<?php

declare(strict_types=1);

ini_set('display_errors', 'off');
ini_set('log_errors', 'on');

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/routes.php';

$app->run();
