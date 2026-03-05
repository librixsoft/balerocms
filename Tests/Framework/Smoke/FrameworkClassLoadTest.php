<?php

declare(strict_types=1);

namespace Tests\Framework\Smoke;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class FrameworkClassLoadTest extends TestCase
{
    #[DataProvider('frameworkPhpFilesProvider')]
    public function testFrameworkPhpFileDeclaresLoadableSymbol(string $file): void
    {
        [$fqcn, $type] = $this->extractPrimarySymbol($file);

        $this->assertNotNull($fqcn, "No class/interface/trait found in {$file}");

        require_once $file;

        $exists = match ($type) {
            'interface' => interface_exists($fqcn, false),
            'trait' => trait_exists($fqcn, false),
            default => class_exists($fqcn, false),
        };

        $this->assertTrue($exists, "Symbol {$fqcn} from {$file} is not autoloadable");

        if ($type === 'class' && is_subclass_of($fqcn, \Throwable::class)) {
            $instance = new $fqcn('test');
            $this->assertInstanceOf($fqcn, $instance);
        }

        if ($type === 'class') {
            $reflection = new \ReflectionClass($fqcn);
            $shouldInstantiate = !str_starts_with($fqcn, 'Framework\\Bootstrap\\')
                && !str_starts_with($fqcn, 'Framework\\Core\\EarlyErrorConsole')
                && !str_starts_with($fqcn, 'Framework\\Core\\ErrorConsole');

            if ($shouldInstantiate && !$reflection->isAbstract() && $reflection->isInstantiable() && $reflection->getConstructor()?->getNumberOfRequiredParameters() === 0) {
                $instance = $reflection->newInstance();
                $this->assertInstanceOf($fqcn, $instance);
            }
        }
    }

    public static function frameworkPhpFilesProvider(): array
    {
        $base = dirname(__DIR__, 3) . '/Framework';
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

    private function extractPrimarySymbol(string $file): array
    {
        $code = file_get_contents($file) ?: '';
        $tokens = token_get_all($code);

        $namespace = '';
        $type = null;
        $name = null;

        for ($i = 0, $count = count($tokens); $i < $count; $i++) {
            $token = $tokens[$i];

            if (is_array($token) && $token[0] === T_NAMESPACE) {
                $namespace = $this->readNamespace($tokens, $i + 1);
            }

            if (is_array($token) && in_array($token[0], [T_CLASS, T_INTERFACE, T_TRAIT], true)) {
                $type = match ($token[0]) {
                    T_INTERFACE => 'interface',
                    T_TRAIT => 'trait',
                    default => 'class',
                };

                $name = $this->readNextStringToken($tokens, $i + 1);
                break;
            }
        }

        if (!$name) {
            return [null, null];
        }

        return [ltrim($namespace . '\\' . $name, '\\'), $type];
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
