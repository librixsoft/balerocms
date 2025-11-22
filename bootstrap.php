<?php
/**
 * Bootstrap de Balero CMS
 * Carga archivos críticos del framework y autoload opcional de Composer
 */

// Archivos críticos del framework
require_once BASE_PATH . '/Framework/Core/ErrorConsole.php';

// Autoload de Composer PRIMERO (se registra su autoloader)
$composerAutoload = BASE_PATH . '/vendor/autoload.php';
if (file_exists($composerAutoload)) {
    require_once $composerAutoload;
}

// Boot DESPUÉS (reorganiza los autoloaders para ejecutarse primero)
require_once BASE_PATH . '/Framework/Bootstrap/Boot.php';