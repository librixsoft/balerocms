<?php

use Framework\Core\Boot;
use Framework\Routing\Router;

const _CORE_VERSION = "1.0";
const LOCAL_DIR = __DIR__ . '/..';

define('APP_ENV', 'dev');

require_once LOCAL_DIR . '/bootstrap.php';

(new Boot)->instantiateClass(Router::class)->initBalero();
