<?php

declare(strict_types=1);

namespace Tests\Framework\Testing\Exceptions;

use Framework\Testing\Exceptions\TestCaseException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use RuntimeException;

/**
 * Suite para Framework\Testing\Exceptions\TestCaseException
 *
 * Cubre:
 *  - Herencia correcta (extiende RuntimeException)
 *  - Puede ser lanzada y capturada como TestCaseException
 *  - Puede ser capturada como RuntimeException (compatibilidad)
 *  - Conserva mensaje, código y excepción previa (previous)
 *  - La clase es concreta (no abstracta)
 *  - Namespace correcto
 */
final class TestCaseExceptionTest extends TestCase
{
    // -------------------------------------------------------------------------
    // 1. ESTRUCTURA / FORMA DE LA CLASE
    // -------------------------------------------------------------------------

    public function testExtendsRuntimeException(): void
    {
        $r = new ReflectionClass(TestCaseException::class);
        $this->assertTrue(
            $r->isSubclassOf(RuntimeException::class),
            'TestCaseException debe extender RuntimeException'
        );
    }

    public function testIsNotAbstract(): void
    {
        $r = new ReflectionClass(TestCaseException::class);
        $this->assertFalse($r->isAbstract(), 'TestCaseException debe ser una clase concreta');
    }

    public function testCorrectNamespace(): void
    {
        $r = new ReflectionClass(TestCaseException::class);
        $this->assertSame(
            'Framework\\Testing\\Exceptions',
            $r->getNamespaceName(),
            'El namespace debe ser Framework\\Testing\\Exceptions'
        );
    }

    // -------------------------------------------------------------------------
    // 2. COMPORTAMIENTO DE LANZADO / CAPTURA
    // -------------------------------------------------------------------------

    public function testCanBeThrown(): void
    {
        $this->expectException(TestCaseException::class);

        throw new TestCaseException('test error');
    }

    public function testCanBeCaughtAsRuntimeException(): void
    {
        $caught = false;

        try {
            throw new TestCaseException('some error');
        } catch (RuntimeException) {
            $caught = true;
        }

        $this->assertTrue($caught, 'TestCaseException debe poder capturarse como RuntimeException');
    }

    public function testCanBeCaughtAsThrowable(): void
    {
        $caught = false;

        try {
            throw new TestCaseException('throwable error');
        } catch (\Throwable) {
            $caught = true;
        }

        $this->assertTrue($caught, 'TestCaseException debe poder capturarse como Throwable');
    }

    // -------------------------------------------------------------------------
    // 3. CONSTRUCTOR — mensaje, código y previous
    // -------------------------------------------------------------------------

    public function testStoresMessage(): void
    {
        $e = new TestCaseException('Container class Foo does not exist');
        $this->assertSame('Container class Foo does not exist', $e->getMessage());
    }

    public function testDefaultCodeIsZero(): void
    {
        $e = new TestCaseException('some msg');
        $this->assertSame(0, $e->getCode());
    }

    public function testStoresCustomCode(): void
    {
        $e = new TestCaseException('some msg', 42);
        $this->assertSame(42, $e->getCode());
    }

    public function testStoresPreviousException(): void
    {
        $previous = new \InvalidArgumentException('original error');
        $e = new TestCaseException('wrapper message', 0, $previous);

        $this->assertSame($previous, $e->getPrevious());
    }

    public function testPreviousIsNullByDefault(): void
    {
        $e = new TestCaseException('no previous');
        $this->assertNull($e->getPrevious());
    }

    // -------------------------------------------------------------------------
    // 4. INTEGRACIÓN — uso real igual que lo hace TestCase::setUp()
    // -------------------------------------------------------------------------

    public function testMimicsRealUsageInTestCaseSetUp(): void
    {
        $containerClass = 'NonExistentContainer123';
        $original = new \Error("Class \"{$containerClass}\" not found");

        try {
            throw new TestCaseException(
                "Container class {$containerClass} does not exist",
                0,
                $original
            );
        } catch (TestCaseException $e) {
            $this->assertStringContainsString($containerClass, $e->getMessage());
            $this->assertStringContainsString('does not exist', $e->getMessage());
            $this->assertSame($original, $e->getPrevious());
        }
    }
}
