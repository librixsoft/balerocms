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

            // Verificar que la clase existe
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
     * Crea la instancia del SUT y reemplaza las propiedades #[Inject] con mocks.
     *
     * @param string $class Nombre de la clase del SUT
     * @return object Instancia del SUT con dependencias mockeadas
     */
    public function createWithMocks(string $class): object
    {
        $reflector = new ReflectionClass($class);
        $sut = $reflector->newInstanceWithoutConstructor();

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

            // Crear mock usando reflexión para acceder al método protected
            $mock = $this->createMock($depClass);

            $prop->setAccessible(true);
            $prop->setValue($sut, $mock);

            // Guardar mock internamente
            $this->mocks[$depClass] = $mock;
        }

        return $sut;
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