<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

use App\Core\Router;
use App\Core\Session;
use App\Core\Env;

Env::loadEnv(__DIR__ . '/../config/');

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');

Session::start();

new Router();
