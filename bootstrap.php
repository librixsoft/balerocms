<?php
/**
 * Bootstrap de Balero CMS
 * Carga archivos críticos del framework y autoload opcional de Composer
 */

// Archivos críticos del framework
require_once BASE_PATH . '/Framework/Core/ErrorConsole.php';
require_once BASE_PATH . '/Framework/Bootstrap/Boot.php';

// Autoload de Composer opcional
$composerAutoload = BASE_PATH . '/vendor/autoload.php';
if (file_exists($composerAutoload)) {
    require_once $composerAutoload;
}
