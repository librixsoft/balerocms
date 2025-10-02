<?php

use Framework\Bootstrap\Boot;

const _CORE_VERSION = "1.0";
const LOCAL_DIR = __DIR__ . '/..';

define('APP_ENV', 'dev'); // change to "prod" if you are uploading to your server

require_once LOCAL_DIR . '/bootstrap.php';

new Boot();