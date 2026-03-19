<?php

declare(strict_types=1);

namespace Tests\Framework\DI;

use Framework\Attributes\Inject;
use Framework\DI\DependencyFactory;
use Framework\Exceptions\ContainerException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

/**
 * Suite completa para Framework\DI\DependencyFactory
 *
 * Cubre al 100%:
 *  - create()                   → rama con constructor (params > 0)
 *  - create()                   → rama sin constructor o constructor vacío
 *  - create()                   → clase inexistente → ContainerException
 *  - createWithConstructor()    → parámetro con tipo object (resolución por contenedor)
 *  - createWithConstructor()    → parámetro built-in con default value
 *  - createWithConstructor()    → parámetro built-in sin default value (null)
 *  - createWithConstructor()    → parámetro sin tipo (null)
 *  - createWithConstructor()    → fallo en contenedor → ContainerException
 *  - createWithoutConstructor() → éxito
 *  - createWithoutConstructor() → fallo (clase abstracta) → ContainerException
 *  - injectProperties()         → propiedad sin #[Inject] (skip)
 *  - injectProperties()         → propiedad con #[Inject] y tipo object → inyectada
 *  - injectProperties()         → propiedad con #[Inject] pero tipo no ReflectionNamedType (skip)
 *  - injectProperties()         → fallo en contenedor → ContainerException
 */
final class DependencyFactoryTest extends TestCase
{
    // -------------------------------------------------------------------------
    // HELPERS: resolver mocks
    // -------------------------------------------------------------------------

    /** Resolver que instancia la clase solicitada sin argumentos */
    private function simpleResolver(): object
    {
        return new class {
            public function get(string $className): object
            {
                return new $className();
            }
        };
    }

    /** Resolver que lanza una excepción para simular fallo */
    private function failingResolver(): object
    {
        return new class {
            public function get(string $className): object
            {
                throw new \RuntimeException("Cannot resolve {$className}");
            }
        };
    }

    // =========================================================================
    // 1. create() – rama principal
    // =========================================================================

    public function testCreateWithConstructorAndPropertyInjection(): void
    {
        $factory = new DependencyFactory($this->simpleResolver());
        $obj = $factory->create(NeedsDeps::class);

        $this->assertInstanceOf(NeedsDeps::class, $obj);
        $this->assertInstanceOf(DepA::class, $obj->a);   // inyección por constructor
        $this->assertInstanceOf(DepB::class, $obj->b);   // inyección por propiedad #[Inject]
    }

    public function testCreateWithoutConstructorParams(): void
    {
        $factory = new DependencyFactory($this->simpleResolver());
        $obj = $factory->create(NoCtor::class);
        $this->assertInstanceOf(NoCtor::class, $obj);
    }

    public function testCreateWithEmptyConstructorUsesCreateWithoutConstructor(): void
    {
        // Constructor existe pero tiene 0 parámetros → rama createWithoutConstructor
        $factory = new DependencyFactory($this->simpleResolver());
        $obj = $factory->create(EmptyCtor::class);
        $this->assertInstanceOf(EmptyCtor::class, $obj);
    }

    public function testCreateThrowsContainerExceptionForNonExistentClass(): void
    {
        $factory = new DependencyFactory($this->simpleResolver());

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessageMatches('/NonExistentClassXyz123/');

        $factory->create('NonExistentClassXyz123');
    }

    // =========================================================================
    // 2. createWithConstructor()
    // =========================================================================

    public function testCreateWithConstructorResolvesObjectParam(): void
    {
        $factory = new DependencyFactory($this->simpleResolver());
        $reflector = new ReflectionClass(HasObjectParam::class);
        $constructor = $reflector->getConstructor();

        $obj = $factory->createWithConstructor($reflector, $constructor);

        $this->assertInstanceOf(HasObjectParam::class, $obj);
        $this->assertInstanceOf(DepA::class, $obj->dep);
    }

    public function testCreateWithConstructorBuiltinParamWithDefault(): void
    {
        // string $name = 'default' → usa default value, NO llama al resolver
        $factory = new DependencyFactory($this->failingResolver());
        $reflector = new ReflectionClass(HasBuiltinParamWithDefault::class);
        $constructor = $reflector->getConstructor();

        $obj = $factory->createWithConstructor($reflector, $constructor);

        $this->assertInstanceOf(HasBuiltinParamWithDefault::class, $obj);
        $this->assertSame('default', $obj->name);
    }

    public function testCreateWithConstructorBuiltinParamWithoutDefaultUsesNull(): void
    {
        // int $value (sin default) → pasa null
        $factory = new DependencyFactory($this->failingResolver());
        $reflector = new ReflectionClass(HasBuiltinParamNoDefault::class);
        $constructor = $reflector->getConstructor();

        $obj = $factory->createWithConstructor($reflector, $constructor);

        $this->assertInstanceOf(HasBuiltinParamNoDefault::class, $obj);
        $this->assertNull($obj->value);
    }

    public function testCreateWithConstructorParamWithoutTypeUsesNull(): void
    {
        // $untyped (sin tipo) → pasa null
        $factory = new DependencyFactory($this->failingResolver());
        $reflector = new ReflectionClass(HasUntypedParam::class);
        $constructor = $reflector->getConstructor();

        $obj = $factory->createWithConstructor($reflector, $constructor);

        $this->assertInstanceOf(HasUntypedParam::class, $obj);
        $this->assertNull($obj->value);
    }

    public function testCreateWithConstructorThrowsContainerExceptionOnFailure(): void
    {
        $factory = new DependencyFactory($this->failingResolver());
        $reflector = new ReflectionClass(HasObjectParam::class);
        $constructor = $reflector->getConstructor();

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessageMatches('/Failed to create instance of/');

        $factory->createWithConstructor($reflector, $constructor);
    }

    // =========================================================================
    // 3. createWithoutConstructor()
    // =========================================================================

    public function testCreateWithoutConstructorSucceeds(): void
    {
        $factory = new DependencyFactory($this->simpleResolver());
        $reflector = new ReflectionClass(NoCtor::class);

        $obj = $factory->createWithoutConstructor($reflector);

        $this->assertInstanceOf(NoCtor::class, $obj);
    }

    public function testCreateWithoutConstructorThrowsContainerExceptionForAbstractClass(): void
    {
        $factory = new DependencyFactory($this->simpleResolver());
        $reflector = new ReflectionClass(AbstractHelper::class);

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessageMatches('/Failed to create instance of.*without constructor/');

        $factory->createWithoutConstructor($reflector);
    }

    // =========================================================================
    // 4. injectProperties()
    // =========================================================================

    public function testInjectPropertiesSkipsPropertiesWithoutInjectAttribute(): void
    {
        $instance = new class {
            public ?DepA $dep = null; // sin #[Inject]
        };

        $factory = new DependencyFactory($this->failingResolver()); // no debe llamarse
        $reflector = new ReflectionClass($instance);

        // No debe lanzar ni asignar nada
        $factory->injectProperties($instance, $reflector);

        $this->assertNull($instance->dep);
    }

    public function testInjectPropertiesInjectsPropertyMarkedWithInject(): void
    {
        $instance = new HasInjectProperty();

        $factory = new DependencyFactory($this->simpleResolver());
        $reflector = new ReflectionClass($instance);

        $factory->injectProperties($instance, $reflector);

        $this->assertInstanceOf(DepA::class, $instance->dep);
    }

    public function testInjectPropertiesSkipsPropertyWithNonNamedType(): void
    {
        // Propiedad con #[Inject] pero tipo union/intersection → skip (no ReflectionNamedType)
        $instance = new HasInjectWithUnionType();

        $factory = new DependencyFactory($this->failingResolver()); // no debe llamarse
        $reflector = new ReflectionClass($instance);

        // No debe lanzar
        $factory->injectProperties($instance, $reflector);

        $this->assertNull($instance->dep);
    }

    public function testInjectPropertiesThrowsContainerExceptionOnFailure(): void
    {
        $instance = new HasInjectProperty();
        $reflector = new ReflectionClass($instance);

        $factory = new DependencyFactory($this->failingResolver());

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessageMatches('/Failed to inject properties/');

        $factory->injectProperties($instance, $reflector);
    }
}

// =============================================================================
// CLASES AUXILIARES
// =============================================================================

class DepA {}
class DepB {}

/** Sin constructor declarado */
class NoCtor {}

/** Constructor vacío (0 parámetros) */
class EmptyCtor
{
    public function __construct() {}
}

/** Constructor con un parámetro object */
class HasObjectParam
{
    public DepA $dep;

    public function __construct(DepA $dep)
    {
        $this->dep = $dep;
    }
}

/** Constructor con parámetro built-in que tiene default */
class HasBuiltinParamWithDefault
{
    public string $name;

    public function __construct(string $name = 'default')
    {
        $this->name = $name;
    }
}

/** Constructor con parámetro built-in sin default value */
class HasBuiltinParamNoDefault
{
    public mixed $value;

    public function __construct(?int $value)
    {
        $this->value = $value;
    }
}

/** Constructor con parámetro sin tipo */
class HasUntypedParam
{
    public mixed $value;

    public function __construct($untyped = null)
    {
        $this->value = $untyped;
    }
}

/** Clase con inyección por constructor + propiedad #[Inject] */
class NeedsDeps
{
    public DepA $a;

    #[Inject]
    public DepB $b;

    public function __construct(DepA $a)
    {
        $this->a = $a;
    }
}

/** Clase con propiedad #[Inject] de tipo object */
class HasInjectProperty
{
    #[Inject]
    public ?DepA $dep = null;
}

/** Clase con propiedad #[Inject] de tipo union (no ReflectionNamedType) */
class HasInjectWithUnionType
{
    #[Inject]
    public DepA|DepB|null $dep = null;
}

/** Clase abstracta para forzar fallo en createWithoutConstructor */
abstract class AbstractHelper {}
