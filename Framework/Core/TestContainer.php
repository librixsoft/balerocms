<?php

/**
 * Balero CMS
 * @author Anibal Gomez <balerocms@gmail.com>
 * @license GNU General Public License
 */

namespace Framework\Core;

use ReflectionClass;
use ReflectionNamedType;

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
 * $container = new TestContainer(fn($class) => $this->createMock($class));
 * $container->initTest($this);
 * ```
 *
 * Notas:
 * - Cada dependencia #[Inject] se reemplaza por un mock y se inyecta automáticamente por constructor
 *   o en la propiedad correspondiente del SUT.
 * - Se puede obtener cualquier mock usando `getMock(ClassName::class)`.
 */
class TestContainer
{
    /**
     * Almacena todos los mocks creados para el test
     * [
     *   'NombreClase' => mockInstance
     * ]
     *
     * @var array<string, object>
     */
    private array $mocks = [];

    /**
     * Callback para crear mocks.
     * Normalmente se pasa `$this->createMock` del TestCase.
     *
     * @var callable
     */
    private $mockFactory;

    /**
     * Constructor
     *
     * @param callable $mockFactory Callback para crear mocks, ejemplo: fn($class) => $this->createMock($class)
     */
    public function __construct(callable $mockFactory)
    {
        $this->mockFactory = $mockFactory;
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
            if (empty($attrs)) continue;

            $sutType = $prop->getType();
            if (!$sutType instanceof ReflectionNamedType || $sutType->isBuiltin()) {
                throw new \RuntimeException(
                    "InjectMocks requiere un tipo de clase válido en {$prop->getName()}"
                );
            }

            $sutClass = $sutType->getName();

            // Creamos el SUT sin llamar al constructor
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
    private function createWithMocks(string $class): object
    {
        $reflector = new ReflectionClass($class);
        $sut = $reflector->newInstanceWithoutConstructor();

        foreach ($reflector->getProperties() as $prop) {
            $injectAttrs = $prop->getAttributes(\Framework\Core\Inject::class);
            if (empty($injectAttrs)) continue;

            $depClass = $prop->getType()?->getName();
            if (!$depClass || !class_exists($depClass)) continue;

            // Crear mock usando el callback
            $mock = ($this->mockFactory)($depClass);

            $prop->setAccessible(true);
            $prop->setValue($sut, $mock);

            // Guardar mock internamente
            $this->mocks[$depClass] = $mock;
        }

        return $sut;
    }

    /**
     * Permite obtener cualquier mock generado para el test
     *
     * @param string $class Nombre de la clase del mock
     * @return object|null Instancia del mock o null si no existe
     */
    public function getMock(string $class)
    {
        return $this->mocks[$class] ?? null;
    }
}
