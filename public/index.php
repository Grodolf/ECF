<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/src/env.php';
require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/src/autoload.php';

use App\Core\Router;
use App\Core\Session;

Session::start();

new Router();
