<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/src/autoload.php';

use App\Core\Router;
use App\Core\Session;
use App\Core\Env;

Env::loadEnv(__DIR__ . '/../config/');

Session::start();

new Router();
