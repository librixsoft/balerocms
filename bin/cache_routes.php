#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Generate controllers cache for the application.
 *
 * This script scans all PHP classes in the App/Controllers directory,
 * detects classes marked with the #[Controller] attribute,
 * and generates a cache file (`controllers.cache.php`) containing
 * an array of controllers with their associated base URLs.
 *
 * The cache is always regenerated on system start to ensure it is up-to-date.
 *
 * Usage:
 *   php bin/cache_routes.php
 */

use ReflectionClass;

const BASE_PATH = __DIR__ . '/../';
$controllersDir = BASE_PATH . 'App/Controllers';
$cacheDir = BASE_PATH . 'cache';
$cacheFile = $cacheDir . '/controllers.cache.php';

// Ensure cache directory exists
if (!is_dir($cacheDir)) {
    mkdir($cacheDir, 0777, true);
    echo "📂 Cache folder created: $cacheDir\n";
}

// Recursive function to scan controllers
function scanControllers(string $namespace, string $dir): array {
    $result = [];
    foreach (scandir($dir) as $file) {
        if ($file === '.' || $file === '..') continue;

        $fullPath = $dir . '/' . $file;

        if (is_dir($fullPath)) {
            $subNamespace = $namespace . '\\' . $file;
            $result = array_merge($result, scanControllers($subNamespace, $fullPath));
        } elseif (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
            $className = $namespace . '\\' . pathinfo($file, PATHINFO_FILENAME);

            if (!class_exists($className)) {
                require_once $fullPath;
            }

            if (!class_exists($className)) continue;

            $ref = new ReflectionClass($className);

            foreach ($ref->getAttributes() as $attr) {
                $attrName = $attr->getName();
                if ($attrName === 'Controller' || str_ends_with($attrName, '\Controller')) {
                    $args = $attr->getArguments();
                    $path = $args['path'] ?? $args[0] ?? '/';
                    $result[] = [
                        'class' => $className,
                        'path' => $path
                    ];
                    echo "✅ $className -> $path\n";
                }
            }
        }
    }
    return $result;
}

// Scan controllers
echo "🔍 Scanning controllers in $controllersDir...\n";
$controllers = scanControllers('App\\Controllers', $controllersDir);

// Prepare cache content with creation timestamp
$cacheContent = "<?php\n";
$cacheContent .= "// Controllers cache auto-generated on " . date('Y-m-d H:i:s') . "\n";
$cacheContent .= "return " . var_export($controllers, true) . ";\n";

// Save cache (always overwrite)
file_put_contents($cacheFile, $cacheContent);

echo "📦 Cache auto-generated with " . count($controllers) . " controllers.\n";
echo "📂 Cache file: $cacheFile\n";
