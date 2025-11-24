#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Generate DTOs cache for the application.
 *
 * This script scans all PHP classes in the App/DTO directory,
 * detects classes marked with the #[DTO] attribute,
 * and generates enhanced DTO classes as separate files that will be loaded
 * by the autoloader instead of the original DTOs.
 *
 * Usage:
 *   php bin/cache_dtos.php
 */

use ReflectionClass;

const BASE_PATH = __DIR__ . '/../';
$dtosDir = BASE_PATH . 'App/DTO';
$cacheDir = BASE_PATH . 'cache/dtos';

// Ensure cache directory exists and clean it
if (is_dir($cacheDir)) {
    array_map('unlink', glob("$cacheDir/*.php"));
} else {
    mkdir($cacheDir, 0777, true);
    echo "📂 Cache folder created: $cacheDir\n";
}

// Recursive function to scan DTOs
function scanDTOs(string $namespace, string $dir, string $cacheDir): array {
    $result = [];
    foreach (scandir($dir) as $file) {
        if ($file === '.' || $file === '..') continue;

        $fullPath = $dir . '/' . $file;

        if (is_dir($fullPath)) {
            $subNamespace = $namespace . '\\' . $file;
            $result = array_merge($result, scanDTOs($subNamespace, $fullPath, $cacheDir));
        } elseif (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
            $className = $namespace . '\\' . pathinfo($file, PATHINFO_FILENAME);

            if (!class_exists($className)) {
                require_once $fullPath;
            }

            if (!class_exists($className)) continue;

            $ref = new ReflectionClass($className);

            // Check for #[DTO] attribute
            $isDTOClass = false;
            foreach ($ref->getAttributes() as $attr) {
                $attrName = $attr->getName();
                if ($attrName === 'DTO' || str_ends_with($attrName, '\DTO')) {
                    $isDTOClass = true;
                    break;
                }
            }

            if (!$isDTOClass) continue;

            // Check for enhancement attributes
            $hasGetter = false;
            $hasSetter = false;
            $hasToArray = false;

            foreach ($ref->getAttributes() as $attr) {
                $attrName = $attr->getName();
                if (str_ends_with($attrName, '\Getter') || $attrName === 'Getter') {
                    $hasGetter = true;
                }
                if (str_ends_with($attrName, '\Setter') || $attrName === 'Setter') {
                    $hasSetter = true;
                }
                if (str_ends_with($attrName, '\ToArray') || $attrName === 'ToArray') {
                    $hasToArray = true;
                }
            }

            // If no enhancements, skip
            if (!$hasGetter && !$hasSetter && !$hasToArray) {
                echo "⏭️  $className (no enhancements needed)\n";
                continue;
            }

            // Read original file content
            $originalContent = file_get_contents($fullPath);

            // Generate enhanced methods
            $methods = [];

            foreach ($ref->getProperties() as $property) {
                $name = $property->getName();
                $camel = ucfirst($name);
                $type = $property->getType();

                if ($type) {
                    $typeName = $type->getName();
                    $nullable = $type->allowsNull() ? '?' : '';
                    $typeHint = "{$nullable}{$typeName}";
                } else {
                    $typeHint = 'mixed';
                }

                if ($hasGetter) {
                    $methods[] = "    public function get{$camel}(): {$typeHint}\n    {\n        return \$this->{$name};\n    }";
                }

                if ($hasSetter) {
                    $methods[] = "    public function set{$camel}({$typeHint} \$value): self\n    {\n        \$this->{$name} = \$value;\n        return \$this;\n    }";
                }
            }

            if ($hasToArray) {
                $methods[] = <<<'PHP'
    public function toArray(): array
    {
        $ref = new \ReflectionClass($this);
        $result = [];
        foreach ($ref->getProperties() as $prop) {
            $prop->setAccessible(true);
            $result[$prop->getName()] = $prop->getValue($this);
        }
        return $result;
    }
PHP;
            }

            // Inject methods before the closing brace of the class
            $enhancedContent = preg_replace(
                '/(\n}\s*)$/',
                "\n\n    // Auto-generated methods by cache_dtos.php\n" . implode("\n\n", $methods) . "\n}\n",
                $originalContent
            );

            // Save enhanced version to cache
            $shortClassName = $ref->getShortName();
            $enhancedFile = $cacheDir . '/' . $shortClassName . '.php';
            file_put_contents($enhancedFile, $enhancedContent);

            $result[] = $className;

            echo "✅ $className → $enhancedFile (G:" . ($hasGetter ? '✓' : '✗') .
                " S:" . ($hasSetter ? '✓' : '✗') .
                " A:" . ($hasToArray ? '✓' : '✗') . ")\n";
        }
    }
    return $result;
}

// Scan DTOs
echo "🔍 Scanning DTOs in $dtosDir...\n";
$enhancedDTOs = scanDTOs('App\\DTO', $dtosDir, $cacheDir);

// Generate autoloader mapping
$mappingFile = BASE_PATH . 'cache/dtos.cache.php';
$mappingContent = "<?php\n";
$mappingContent .= "// DTOs autoloader mapping - auto-generated\n";
$mappingContent .= "// List of DTO classes that should load from cache\n";
$mappingContent .= "return " . var_export($enhancedDTOs, true) . ";\n";
file_put_contents($mappingFile, $mappingContent);

echo "\n📦 Cache auto-generated with " . count($enhancedDTOs) . " enhanced DTOs.\n";
echo "📂 Enhanced DTOs directory: $cacheDir\n";
echo "📂 Mapping file: $mappingFile\n";