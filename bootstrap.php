<?php
/**
 * Bootstrap de Balero CMS
 * Carga archivos críticos del framework y autoload opcional de Composer
 */

// Archivos críticos del framework
require_once LOCAL_DIR . '/Framework/Core/ErrorConsole.php';
require_once LOCAL_DIR . '/Framework/Core/Boot.php';

// Autoload de Composer opcional
$composerAutoload = LOCAL_DIR . '/vendor/autoload.php';
if (file_exists($composerAutoload)) {
    require_once $composerAutoload;
}
