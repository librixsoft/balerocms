<?php

/**
 * Balero CMS - Test Container
 *
 * Contenedor de pruebas para PHPUnit que permite:
 * - Inicializar propiedades del test marcadas con #[InjectMocks] como SUT (System Under Test)
 * - Reemplazar automáticamente las dependencias marcadas con #[Inject] por mocks
 * - Soportar inyección de dependencias por constructor y por propiedades
 * - Mantener un registro de todos los mocks creados para uso dentro de los tests
 *
 * @author Anibal Gomez
 * @license GNU General Public License
 */

namespace Framework\DI;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionNamedType;
use Framework\Attributes\Inject;
use Framework\Attributes\InjectMocks;

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
     *
     * @param object $test Instancia del PHPUnit TestCase
     * @return void
     * @throws \RuntimeException
     */
    public function initTest(object $test): void
    {
        $reflectTest = new ReflectionClass($test);

        foreach ($reflectTest->getProperties() as $prop) {
            $attrs = $prop->getAttributes(InjectMocks::class);
            if (empty($attrs)) continue;

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

            $sut = $this->createWithMocks($sutClass);

            $prop->setAccessible(true);
            $prop->setValue($test, $sut);
        }
    }

    /**
     * Resuelve una clase o devuelve el mock existente
     *
     * @param string $className
     * @return object
     */
    public function get(string $className): object
    {
        if (isset($this->mocks[$className])) {
            return $this->mocks[$className];
        }
        $this->mocks[$className] = $this->createMock($className);
        return $this->mocks[$className];
    }

    /**
     * Crea la instancia del SUT con dependencias mockeadas
     *
     * @param string $className
     * @return object
     */
    public function createWithMocks(string $className): object
    {
        $factory = new DependencyFactory($this);
        return $factory->create($className);
    }

    /**
     * Sobreescribe el metodo para acceder a el xq tiene protected
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
