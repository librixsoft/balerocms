#!/usr/bin/env php
<?php

declare(strict_types=1);

use ReflectionClass;

const LOCAL_DIR = __DIR__ . '/../';
$controllersDir = LOCAL_DIR . 'App/Controllers';
$cacheDir = LOCAL_DIR . 'cache';
$cacheFile = $cacheDir . '/controllers.cache.php';

// Crear carpeta cache si no existe
if (!is_dir($cacheDir)) {
    mkdir($cacheDir, 0777, true);
    echo "📂 Carpeta cache creada: $cacheDir\n";
}

// Función recursiva para escanear controladores
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
                    $url = $args['path'] ?? '/';
                    $result[] = [
                        'class' => $className,
                        'url' => $url
                    ];
                    echo "✅ $className -> $url\n";
                }
            }
        }
    }
    return $result;
}

// Escaneo
echo "🔍 Escaneando controladores en $controllersDir...\n";
$controllers = scanControllers('App\\Controllers', $controllersDir);

// Guardar cache
file_put_contents($cacheFile, '<?php return ' . var_export($controllers, true) . ';');
echo "📦 Cache generado con " . count($controllers) . " controladores.\n";
echo "📂 Archivo de cache: $cacheFile\n";
