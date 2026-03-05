<?php

declare(strict_types=1);

namespace Tests\App\Smoke;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AppClassLoadTest extends TestCase
{
    #[DataProvider('appPhpFilesProvider')]
    public function testAppPhpFileDeclaresLoadableClass(string $file): void
    {
        $fqcn = $this->extractClass($file);
        $this->assertNotNull($fqcn, "No class found in {$file}");

        require_once $file;

        $this->assertTrue(class_exists($fqcn, false), "Class {$fqcn} from {$file} is not loadable");

        $reflection = new \ReflectionClass($fqcn);
        if ($reflection->isInstantiable() && !$reflection->isAbstract()) {
            $ctor = $reflection->getConstructor();
            if ($ctor === null || $ctor->getNumberOfRequiredParameters() === 0) {
                $instance = $reflection->newInstance();
                $this->assertInstanceOf($fqcn, $instance);
            }
        }
    }

    public static function appPhpFilesProvider(): array
    {
        $base = dirname(__DIR__, 3) . '/App';
        $files = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($base, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = [$file->getPathname()];
            }
        }

        sort($files);

        return $files;
    }

    private function extractClass(string $file): ?string
    {
        $code = file_get_contents($file) ?: '';
        $tokens = token_get_all($code);

        $namespace = '';
        $name = null;

        for ($i = 0, $count = count($tokens); $i < $count; $i++) {
            $token = $tokens[$i];

            if (is_array($token) && $token[0] === T_NAMESPACE) {
                $namespace = $this->readNamespace($tokens, $i + 1);
            }

            if (is_array($token) && $token[0] === T_CLASS) {
                $name = $this->readNextStringToken($tokens, $i + 1);
                break;
            }
        }

        if (!$name) {
            return null;
        }

        return ltrim($namespace . '\\' . $name, '\\');
    }

    private function readNamespace(array $tokens, int $start): string
    {
        $parts = [];
        for ($i = $start, $count = count($tokens); $i < $count; $i++) {
            $token = $tokens[$i];
            if ($token === ';' || $token === '{') {
                break;
            }
            if (is_array($token) && in_array($token[0], [T_STRING, T_NAME_QUALIFIED, T_NS_SEPARATOR], true)) {
                $parts[] = $token[1];
            }
        }

        return implode('', $parts);
    }

    private function readNextStringToken(array $tokens, int $start): ?string
    {
        for ($i = $start, $count = count($tokens); $i < $count; $i++) {
            $token = $tokens[$i];
            if (is_array($token) && $token[0] === T_STRING) {
                return $token[1];
            }
        }

        return null;
    }
}
