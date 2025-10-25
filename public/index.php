<?php

use Framework\Bootstrap\Boot;

const BASE_PATH = __DIR__ . '/..';

define('APP_ENV', 'dev'); // change to "prod" if you are uploading to your server

require_once BASE_PATH . '/bootstrap.php';

new Boot();