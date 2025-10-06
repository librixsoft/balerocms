<?php

/**
 * Balero CMS
 * @author Anibal Gomez <balerocms@gmail.com>
 * @license GNU General Public License
 */

namespace Framework\DI;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionNamedType;
use Framework\Attributes\Inject;
use Framework\Attributes\InjectMocks;

/**
 * TestContainer
 *
 * Contenedor de pruebas para PHPUnit que permite:
 * - Inicializar propiedades del test marcadas con #[InjectMocks] como SUT (System Under Test),
 *   creando la instancia y asignando automáticamente las dependencias.
 * - Reemplazar automáticamente las dependencias marcadas con #[Inject] por mocks,
 *   que se inyectan en el SUT.
 * - Soportar inyección de dependencias por constructor y por propiedades.
 * - Mantener un registro de todos los mocks creados para uso dentro de los tests.
 *
 * Uso típico en un TestCase:
 * ```php
 * $container = new TestContainer($this);
 * $container->initTest($this);
 * ```
 */
class TestContainer
{
    /**
     * Almacena todos los mocks creados para el test
     *
     * @var array<string, object>
     */
    private array $mocks = [];

    /**
     * Referencia al TestCase para crear mocks
     *
     * @var TestCase
     */
    private TestCase $testCase;

    /**
     * Constructor
     *
     * @param TestCase $testCase Instancia del TestCase actual
     */
    public function __construct(TestCase $testCase)
    {
        $this->testCase = $testCase;
    }

    /**
     * Inicializa todas las propiedades del test con #[InjectMocks]
     * creando la instancia de la clase (SUT) y reemplazando sus dependencias #[Inject] con mocks.
     *
     * @param object $test Instancia del PHPUnit TestCase
     * @return void
     * @throws \RuntimeException Si la propiedad #[InjectMocks] no tiene tipo válido
     */
    public function initTest(object $test): void
    {
        $reflectTest = new ReflectionClass($test);

        foreach ($reflectTest->getProperties() as $prop) {
            $attrs = $prop->getAttributes(InjectMocks::class);
            if (empty($attrs)) {
                continue;
            }

            $sutType = $prop->getType();
            if (!$sutType instanceof ReflectionNamedType || $sutType->isBuiltin()) {
                throw new \RuntimeException(
                    "InjectMocks requiere un tipo de clase válido en {$prop->getName()}"
                );
            }

            $sutClass = $sutType->getName();

            if (!class_exists($sutClass)) {
                throw new \RuntimeException(
                    "La clase {$sutClass} no existe para la propiedad {$prop->getName()}"
                );
            }

            // Creamos el SUT con sus dependencias mockeadas
            $sut = $this->createWithMocks($sutClass);

            $prop->setAccessible(true);
            $prop->setValue($test, $sut);
        }
    }

    /**
     * Crea la instancia del SUT y reemplaza las dependencias con mocks.
     * Soporta inyección por constructor y por propiedades.
     *
     * @param string $class Nombre de la clase del SUT
     * @return object Instancia del SUT con dependencias mockeadas
     */
    public function createWithMocks(string $class): object
    {
        $reflector = new ReflectionClass($class);
        $constructor = $reflector->getConstructor();

        if ($constructor && $constructor->getNumberOfParameters() > 0) {
            $sut = $this->createWithConstructor($reflector, $constructor);
        } else {
            $sut = $this->createWithoutConstructor($reflector);
        }

        $this->injectPropertyDependencies($sut, $reflector);

        return $sut;
    }

    /**
     * Crea instancia con inyección de dependencias por constructor
     * (misma lógica que Container::createWithConstructor)
     *
     * @param ReflectionClass $reflector
     * @param \ReflectionMethod $constructor
     * @return object
     */
    private function createWithConstructor(ReflectionClass $reflector, \ReflectionMethod $constructor): object
    {
        $params = [];
        foreach ($constructor->getParameters() as $param) {
            $type = $param->getType();
            if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
                $params[] = $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null;
            } else {
                $depClass = $type->getName();
                if (!isset($this->mocks[$depClass])) {
                    $this->mocks[$depClass] = $this->createMock($depClass);
                }
                $params[] = $this->mocks[$depClass];
            }
        }
        return $reflector->newInstanceArgs($params);
    }

    /**
     * Crea una instancia de la clase sin pasar parámetros al constructor
     * (misma lógica que Container::createWithoutConstructor)
     *
     * @param ReflectionClass $reflector
     * @return object
     */
    private function createWithoutConstructor(ReflectionClass $reflector): object
    {
        return $reflector->newInstance();
    }

    /**
     * Inyecta dependencias en propiedades marcadas con #[Inject]
     *
     * @param object $sut
     * @param ReflectionClass $reflector
     * @return void
     */
    private function injectPropertyDependencies(object $sut, ReflectionClass $reflector): void
    {
        foreach ($reflector->getProperties() as $prop) {
            $injectAttrs = $prop->getAttributes(Inject::class);
            if (empty($injectAttrs)) {
                continue;
            }

            $propType = $prop->getType();
            if (!$propType instanceof ReflectionNamedType) {
                continue;
            }

            $depClass = $propType->getName();

            if (!class_exists($depClass) && !interface_exists($depClass)) {
                continue;
            }

            if (!isset($this->mocks[$depClass])) {
                $this->mocks[$depClass] = $this->createMock($depClass);
            }

            $prop->setAccessible(true);
            $prop->setValue($sut, $this->mocks[$depClass]);
        }
    }

    /**
     * Crea un mock usando reflexión para acceder al método protected del TestCase
     *
     * @param string $className Nombre de la clase a mockear
     * @return object Mock de la clase
     */
    private function createMock(string $className): object
    {
        $reflection = new ReflectionClass($this->testCase);
        $method = $reflection->getMethod('createMock');
        $method->setAccessible(true);

        return $method->invoke($this->testCase, $className);
    }

    /**
     * Permite obtener cualquier mock generado para el test
     *
     * @param string $class Nombre de la clase del mock
     * @return object|null Instancia del mock o null si no existe
     */
    public function getMock(string $class): ?object
    {
        return $this->mocks[$class] ?? null;
    }
}
