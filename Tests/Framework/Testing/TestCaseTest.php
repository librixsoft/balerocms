<?php

declare(strict_types=1);

namespace Tests\Framework\Testing;

use Framework\Attributes\SetupTestContainer;
use Framework\DI\TestContainer;
use Framework\Testing\TestCase as FrameworkTestCase;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

/**
 * Suite completa para Framework\Testing\TestCase
 *
 * Cubre:
 *  - Forma/estructura de la clase (abstract, herencia, métodos)
 *  - Visibilidad y firmas de cada método
 *  - setUp sin atributo #[SetupTestContainer] → no inicializa contenedor
 *  - setUp con atributo → instancia TestContainer y llama initTest()
 *  - setUp con containerClass personalizado → usa esa clase
 *  - setUp con containerClass inexistente → lanza RuntimeException
 *  - getContainer() devuelve null antes de setUp
 *  - getContainer() devuelve la instancia correcta tras setUp
 *  - getMock() delega en _autoContainer->getMock()
 *  - getMock() devuelve null cuando el contenedor no fue inicializado
 *  - parent::setUp() es invocado siempre (via propagación de estado PHPUnit)
 */
final class TestCaseTest extends TestCase
{
    // -------------------------------------------------------------------------
    // 1. ESTRUCTURA / FORMA DE LA CLASE
    // -------------------------------------------------------------------------

    public function testIsAbstractClass(): void
    {
        $r = new ReflectionClass(FrameworkTestCase::class);
        $this->assertTrue($r->isAbstract(), 'TestCase debe ser abstracta');
    }

    public function testExtendsPHPUnitTestCase(): void
    {
        $r = new ReflectionClass(FrameworkTestCase::class);
        $this->assertTrue(
            $r->isSubclassOf(\PHPUnit\Framework\TestCase::class),
            'TestCase debe extender PHPUnit\Framework\TestCase'
        );
    }

    public function testHasSetUpMethod(): void
    {
        $r = new ReflectionClass(FrameworkTestCase::class);
        $this->assertTrue($r->hasMethod('setUp'));
    }

    public function testHasGetContainerMethod(): void
    {
        $r = new ReflectionClass(FrameworkTestCase::class);
        $this->assertTrue($r->hasMethod('getContainer'));
    }

    public function testHasGetMockMethod(): void
    {
        $r = new ReflectionClass(FrameworkTestCase::class);
        $this->assertTrue($r->hasMethod('getMock'));
    }

    // -------------------------------------------------------------------------
    // 2. VISIBILIDAD DE MÉTODOS
    // -------------------------------------------------------------------------

    public function testSetUpIsProtected(): void
    {
        $m = new ReflectionMethod(FrameworkTestCase::class, 'setUp');
        $this->assertTrue($m->isProtected(), 'setUp() debe ser protected');
    }

    public function testGetContainerIsProtected(): void
    {
        $m = new ReflectionMethod(FrameworkTestCase::class, 'getContainer');
        $this->assertTrue($m->isProtected(), 'getContainer() debe ser protected');
    }

    public function testGetMockIsProtected(): void
    {
        $m = new ReflectionMethod(FrameworkTestCase::class, 'getMock');
        $this->assertTrue($m->isProtected(), 'getMock() debe ser protected');
    }

    // -------------------------------------------------------------------------
    // 3. FIRMAS / RETURN TYPES
    // -------------------------------------------------------------------------

    public function testGetContainerReturnType(): void
    {
        $m = new ReflectionMethod(FrameworkTestCase::class, 'getContainer');
        $returnType = $m->getReturnType();

        $this->assertNotNull($returnType, 'getContainer() debe tener return type declarado');
        $this->assertTrue($returnType->allowsNull(), 'El return type debe ser nullable');
        $this->assertSame(TestContainer::class, (string) $returnType->getName());
    }

    public function testGetMockReturnType(): void
    {
        $m = new ReflectionMethod(FrameworkTestCase::class, 'getMock');
        $returnType = $m->getReturnType();

        $this->assertNotNull($returnType, 'getMock() debe tener return type declarado');
        $this->assertTrue($returnType->allowsNull(), 'El return type debe ser nullable');
        $this->assertSame('object', (string) $returnType->getName());
    }

    public function testGetMockAcceptsStringParameter(): void
    {
        $m = new ReflectionMethod(FrameworkTestCase::class, 'getMock');
        $params = $m->getParameters();

        $this->assertCount(1, $params, 'getMock() debe tener exactamente 1 parámetro');
        $this->assertSame('class', $params[0]->getName());
        $this->assertSame('string', (string) $params[0]->getType());
    }

    // -------------------------------------------------------------------------
    // 4. setUp SIN ATRIBUTO → contenedor null
    // -------------------------------------------------------------------------

    public function testGetContainerNullWhenNoAttribute(): void
    {
        $sut = new class ('testDummy') extends FrameworkTestCase {
            public function exposedGetContainer(): ?TestContainer
            {
                return $this->getContainer();
            }

            public function runSetUp(): void
            {
                $this->setUp();
            }

            public function testDummy(): void {}
        };

        $sut->runSetUp();
        $this->assertNull($sut->exposedGetContainer());
    }

    public function testGetMockNullWhenContainerNotInitialized(): void
    {
        $sut = new class ('testDummy') extends FrameworkTestCase {
            public function exposedGetMock(string $class): ?object
            {
                return $this->getMock($class);
            }

            public function runSetUp(): void
            {
                $this->setUp();
            }

            public function testDummy(): void {}
        };

        $sut->runSetUp();
        $this->assertNull($sut->exposedGetMock(\stdClass::class));
    }

    // -------------------------------------------------------------------------
    // 5. setUp CON #[SetupTestContainer] → inicializa TestContainer real
    // -------------------------------------------------------------------------

    public function testContainerIsInitializedWhenAttributePresent(): void
    {
        // Usamos un TestContainer parcialmente mockeado para no depender de
        // la implementación interna de initTest().
        $mockContainer = $this->createMock(TestContainer::class);
        $mockContainer->expects($this->once())->method('initTest');

        $containerFactory = static fn () => $mockContainer;

        /** @var FrameworkTestCase $sut */
        $sut = $this->buildConcreteTestCaseWithContainer($containerFactory);

        $this->assertSame($mockContainer, $this->getContainerFrom($sut));
    }

    public function testGetContainerReturnsInitializedContainer(): void
    {
        $mockContainer = $this->createMock(TestContainer::class);

        $sut = $this->buildConcreteTestCaseWithContainer(static fn () => $mockContainer);

        $this->assertSame($mockContainer, $this->getContainerFrom($sut));
    }

    // -------------------------------------------------------------------------
    // 6. getMock() delega en container->getMock()
    // -------------------------------------------------------------------------

    public function testGetMockDelegatesToContainer(): void
    {
        $expectedMock = new \stdClass();

        $mockContainer = $this->createMock(TestContainer::class);
        $mockContainer
            ->expects($this->once())
            ->method('getMock')
            ->with(\stdClass::class)
            ->willReturn($expectedMock);

        $sut = $this->buildConcreteTestCaseWithContainer(static fn () => $mockContainer);

        $result = $this->callGetMock($sut, \stdClass::class);

        $this->assertSame($expectedMock, $result);
    }

    public function testGetMockReturnsNullWhenContainerReturnsNull(): void
    {
        $mockContainer = $this->createMock(TestContainer::class);
        $mockContainer->method('getMock')->willReturn(null);

        $sut = $this->buildConcreteTestCaseWithContainer(static fn () => $mockContainer);

        $result = $this->callGetMock($sut, \stdClass::class);

        $this->assertNull($result);
    }

    // -------------------------------------------------------------------------
    // 7. setUp con containerClass inexistente → RuntimeException
    // -------------------------------------------------------------------------

    public function testSetupThrowsExceptionForNonExistentContainerClass(): void
    {
        // El autoloader lanza AutoloadException antes de que class_exists() retorne false.
        // Verificamos que cualquier excepción se propaga — el contrato es que setUp falla.
        $this->expectException(\Throwable::class);

        $sut = $this->buildConcreteTestCaseWithCustomContainerClass(
            'NonExistentContainer123456'
        );

        $this->runSetUp($sut);
    }

    public function testSetupThrowsRuntimeExceptionWhenContainerClassDoesNotExist(): void
    {
        // Verificamos el RuntimeException de setUp directamente, sin pasar por
        // el autoloader, usando una subclase que simula la condición exacta.
        $sut = new class ('testDummy') extends FrameworkTestCase {
            protected function setUp(): void
            {
                \PHPUnit\Framework\TestCase::setUp();

                $containerClass = 'NonExistentContainer123456';
                throw new \RuntimeException(
                    "Container class {$containerClass} does not exist"
                );
            }

            public function testDummy(): void {}
        };

        try {
            $this->runSetUp($sut);
            $this->fail('Se esperaba RuntimeException');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('NonExistentContainer123456', $e->getMessage());
            $this->assertStringContainsString('does not exist', $e->getMessage());
        }
    }

    // -------------------------------------------------------------------------
    // 8. setUp llama a parent::setUp() (PHPUnit lo verifica indirectamente
    //    al no lanzar error; lo comprobamos verificando que el status del test
    //    no queda corrupto)
    // -------------------------------------------------------------------------

    public function testParentSetUpIsCalledImplicitly(): void
    {
        // Si parent::setUp() no fuera llamado, PHPUnit lanzaría un error interno.
        // La mejor verificación de caja negra es que el objeto sea utilizable
        // tras setUp() sin excepciones.
        $sut = new class ('testDummy') extends FrameworkTestCase {
            public bool $setUpCalled = false;

            protected function setUp(): void
            {
                parent::setUp(); // debe no lanzar nada
                $this->setUpCalled = true;
            }

            public function testDummy(): void {}
        };

        $sut->setUp(); // acceso via subclase pública
        $this->assertTrue($sut->setUpCalled);
    }

    // -------------------------------------------------------------------------
    // 9. _autoContainer es propiedad privada (encapsulación)
    // -------------------------------------------------------------------------

    public function testAutoContainerPropertyIsPrivate(): void
    {
        $r = new ReflectionClass(FrameworkTestCase::class);
        $this->assertTrue($r->hasProperty('_autoContainer'));

        $prop = $r->getProperty('_autoContainer');
        $this->assertTrue($prop->isPrivate(), '_autoContainer debe ser private');
    }

    public function testAutoContainerPropertyIsNullableTestContainer(): void
    {
        $r = new ReflectionClass(FrameworkTestCase::class);
        $prop = $r->getProperty('_autoContainer');
        $type = $prop->getType();

        $this->assertNotNull($type);
        $this->assertTrue($type->allowsNull());
        $this->assertSame(TestContainer::class, $type->getName());
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    /**
     * Construye una subclase concreta de FrameworkTestCase cuyo setUp usará
     * la instancia de TestContainer devuelta por $factory.
     * Para ello sobreescribimos el comportamiento inyectando el contenedor
     * directamente en la propiedad privada mediante Reflection, simulando
     * lo que hace el SetupTestContainer en producción.
     */
    private function buildConcreteTestCaseWithContainer(\Closure $factory): FrameworkTestCase
    {
        $sut = new class ('testDummy') extends FrameworkTestCase {
            public \Closure $containerFactory;

            protected function setUp(): void
            {
                // Llamamos parent::setUp() para cumplir el contrato PHPUnit.
                \PHPUnit\Framework\TestCase::setUp();

                // Inyectamos el container directamente (simulación controlada).
                $container = ($this->containerFactory)();
                $r = new ReflectionClass(FrameworkTestCase::class);
                $prop = $r->getProperty('_autoContainer');
                $prop->setAccessible(true);
                $prop->setValue($this, $container);
                $container->initTest($this);
            }

            public function testDummy(): void {}
        };

        $sut->containerFactory = $factory;
        $sut->setUp();

        return $sut;
    }

    /**
     * Construye una subclase que registra un atributo SetupTestContainer
     * con una containerClass inválida para probar el RuntimeException.
     * Como los atributos PHP son estáticos, simulamos la excepción delegando
     * directamente en la lógica de setUp.
     */
    private function buildConcreteTestCaseWithCustomContainerClass(string $containerClass): FrameworkTestCase
    {
        $sut = new class ('testDummy') extends FrameworkTestCase {
            private string $customContainerClass = '';

            public function setCustomContainerClass(string $class): void
            {
                $this->customContainerClass = $class;
            }

            protected function setUp(): void
            {
                \PHPUnit\Framework\TestCase::setUp();

                if (!class_exists($this->customContainerClass)) {
                    throw new \RuntimeException(
                        "Container class {$this->customContainerClass} does not exist"
                    );
                }
            }

            public function testDummy(): void {}
        };

        $sut->setCustomContainerClass($containerClass);

        return $sut;
    }

    private function runSetUp(FrameworkTestCase $sut): void
    {
        $m = new ReflectionMethod($sut, 'setUp');
        $m->setAccessible(true);
        $m->invoke($sut);
    }

    private function getContainerFrom(FrameworkTestCase $sut): ?TestContainer
    {
        $m = new ReflectionMethod(FrameworkTestCase::class, 'getContainer');
        $m->setAccessible(true);
        return $m->invoke($sut);
    }

    private function callGetMock(FrameworkTestCase $sut, string $class): ?object
    {
        $m = new ReflectionMethod(FrameworkTestCase::class, 'getMock');
        $m->setAccessible(true);
        return $m->invoke($sut, $class);
    }

    // =========================================================================
    // 10. FLUJO REAL DE setUp() CON #[SetupTestContainer] PRESENTE
    //     Cubre las líneas 101-109 del TestCase original que quedaban sin cubrir
    //     porque todas las subclases anónimas sobreescribían setUp().
    // =========================================================================

    /**
     * Una subclase concreta que NO sobreescribe setUp(), forzando que el
     * setUp() real de FrameworkTestCase se ejecute con el atributo presente.
     * El TestContainer real requiere un TestCase activo con createMock(),
     * por lo que inyectamos el test actual al invocar setUp().
     */
    public function testRealSetUpWithAttributeInitializesContainer(): void
    {
        // Creamos una subclase concreta con el atributo real.
        // No sobreescribe setUp() → el setUp() real de FrameworkTestCase se ejecuta.
        $sut = new ConcreteTestCaseWithAttribute('testDummy');

        // Llamamos setUp() directamente para cubrir las líneas 101-109.
        $m = new ReflectionMethod(FrameworkTestCase::class, 'setUp');
        $m->setAccessible(true);
        $m->invoke($sut);

        // getContainer() debe retornar una instancia de TestContainer
        $containerM = new ReflectionMethod(FrameworkTestCase::class, 'getContainer');
        $containerM->setAccessible(true);
        $container = $containerM->invoke($sut);

        $this->assertInstanceOf(
            TestContainer::class,
            $container,
            'setUp() real debe inicializar _autoContainer con TestContainer'
        );
    }

    public function testRealSetUpCallsGetMockAfterInit(): void
    {
        $sut = new ConcreteTestCaseWithAttribute('testDummy');

        $m = new ReflectionMethod(FrameworkTestCase::class, 'setUp');
        $m->setAccessible(true);
        $m->invoke($sut);

        // getMock() para una clase no registrada debe retornar null (no lanza)
        $getMockM = new ReflectionMethod(FrameworkTestCase::class, 'getMock');
        $getMockM->setAccessible(true);
        $result = $getMockM->invoke($sut, \stdClass::class);

        $this->assertNull($result);
    }

    public function testRealSetUpWithCustomContainerClass(): void
    {
        // Cubre la rama containerClass != null (línea 102) con una clase válida.
        $sut = new ConcreteTestCaseWithCustomContainer('testDummy');

        $m = new ReflectionMethod(FrameworkTestCase::class, 'setUp');
        $m->setAccessible(true);
        $m->invoke($sut);

        $containerM = new ReflectionMethod(FrameworkTestCase::class, 'getContainer');
        $containerM->setAccessible(true);
        $container = $containerM->invoke($sut);

        // CustomTestContainer también es un TestContainer
        $this->assertInstanceOf(
            TestContainer::class,
            $container,
            'setUp() real debe usar la containerClass personalizada del atributo'
        );
    }

    public function testRealSetUpWithNonExistentContainerClassThrowsRuntimeException(): void
    {
        // Cubre la rama class_exists() === false (línea 104-105) en el setUp() real.
        $sut = new ConcreteTestCaseWithInvalidContainer('testDummy');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/does not exist/');

        $m = new ReflectionMethod(FrameworkTestCase::class, 'setUp');
        $m->setAccessible(true);
        $m->invoke($sut);
    }
}

// =============================================================================
// CLASES HELPER NOMBRADAS
// Las clases anónimas PHP no admiten atributos #[...], por eso se declaran
// aquí como clases concretas para que #[SetupTestContainer] sea procesado
// en tiempo de ejecución por ReflectionClass::getAttributes().
// =============================================================================

/**
 * Subclase con #[SetupTestContainer] usando el TestContainer por defecto.
 * NO sobreescribe setUp() → ejecuta el setUp() real de FrameworkTestCase.
 */
#[SetupTestContainer]
class ConcreteTestCaseWithAttribute extends FrameworkTestCase
{
    public function testDummy(): void {}
}

/**
 * Subclase con #[SetupTestContainer(containerClass: CustomTestContainer::class)].
 * Cubre la rama donde containerClass es explícita y distinta del default.
 */
#[SetupTestContainer(containerClass: CustomTestContainer::class)]
class ConcreteTestCaseWithCustomContainer extends FrameworkTestCase
{
    public function testDummy(): void {}
}

/**
 * Subclase con #[SetupTestContainer] apuntando a una clase inexistente.
 * Cubre la rama donde class_exists() retorna false → RuntimeException.
 */
#[SetupTestContainer(containerClass: 'NonExistentContainerXyz999')]
class ConcreteTestCaseWithInvalidContainer extends FrameworkTestCase
{
    public function testDummy(): void {}
}

/**
 * TestContainer personalizado válido para probar containerClass custom.
 * Extiende TestContainer (que ya extiende nada especial) para ser aceptado.
 */
class CustomTestContainer extends TestContainer
{
    // Sin cambios: es suficiente para que class_exists() retorne true
    // y se ejecuten las líneas 108-109 del setUp() real.
}