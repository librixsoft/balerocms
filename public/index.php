<?php

use Framework\Bootstrap\Boot;

const BASE_PATH = __DIR__ . '/..';

require_once BASE_PATH . '/bootstrap.php';

$boot = new Boot();
$boot->init();
