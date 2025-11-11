<?php

use Framework\Bootstrap\Boot;
use Framework\DI\Container;

const BASE_PATH = __DIR__ . '/..';
define('APP_ENV', 'dev'); // "prod" en servidor

require_once BASE_PATH . '/bootstrap.php';

$boot = new Boot(new Container());
$boot->init();
