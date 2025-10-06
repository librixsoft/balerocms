<?php

/**
 * Balero CMS - Test Base Class
 *
 * Clase base personalizada para tests de PHPUnit que proporciona auto-inyección
 * de dependencias mediante atributos. Extiende PHPUnit TestCase y agrega soporte
 * para el patrón de inyección de mocks usando #[SetupTestContainer] y #[InjectMocks].
 *
 * Características:
 * - Auto-configuración de TestContainer mediante atributos
 * - Inyección automática de mocks en propiedades marcadas con #[InjectMocks]
 * - Acceso simplificado a mocks generados
 * - Extensible mediante setUp() personalizado
 *
 * @package Framework\Testing
 * @author Anibal Gomez
 * @license GNU General Public License
 */

namespace Framework\Testing;

use Framework\Attributes\SetupTestContainer;
use Framework\DI\TestContainer;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use ReflectionClass;

/**
 * TestCase
 *
 * Clase base abstracta para tests que proporciona auto-inyección de dependencias.
 *
 * Uso básico:
 * ```php
 * #[SetupTestContainer]
 * class MiTest extends TestCase
 * {
 *     #[InjectMocks]
 *     private ?MiClase $sut = null;
 *
 *     public function testAlgo(): void
 *     {
 *         // $this->sut ya está inicializado con sus dependencias mockeadas
 *     }
 * }
 * ```
 *
 * Con setUp personalizado:
 * ```php
 * protected function setUp(): void
 * {
 *     parent::setUp(); // IMPORTANTE: Siempre llamar primero
 *     // Tu código personalizado aquí
 * }
 * ```
 */
abstract class TestCase extends PHPUnitTestCase
{
    /**
     * Instancia del contenedor de pruebas que gestiona los mocks
     *
     * @var TestContainer|null
     */
    private ?TestContainer $_autoContainer = null;

    /**
     * Configura el test antes de cada ejecución
     *
     * Este método intercepta el setUp de PHPUnit para inicializar automáticamente
     * el TestContainer si la clase tiene el atributo #[SetupTestContainer].
     * Busca propiedades marcadas con #[InjectMocks] y las inicializa con sus
     * dependencias mockeadas automáticamente.
     *
     * Si necesitas un setUp personalizado, extiende este método pero siempre
     * llama a parent::setUp() primero para que la auto-inyección funcione:
     *
     * ```php
     * protected function setUp(): void
     * {
     *     parent::setUp(); // CRÍTICO: Llamar primero
     *     // Tu configuración aquí
     * }
     * ```
     *
     * @return void
     * @throws \RuntimeException Si la clase del contenedor especificada no existe
     */
    protected function setUp(): void
    {
        parent::setUp();

        $reflection = new ReflectionClass($this);
        $attributes = $reflection->getAttributes(SetupTestContainer::class);

        if (empty($attributes)) {
            return;
        }

        $attribute = $attributes[0]->newInstance();
        $containerClass = $attribute->containerClass ?? TestContainer::class;

        if (!class_exists($containerClass)) {
            throw new \RuntimeException("Container class {$containerClass} does not exist");
        }

        $this->_autoContainer = new $containerClass($this);
        $this->_autoContainer->initTest($this);
    }

    /**
     * Obtiene la instancia del TestContainer del test actual
     *
     * Permite acceder al contenedor para operaciones avanzadas como
     * obtener mocks específicos o realizar configuraciones adicionales.
     *
     * Ejemplo:
     * ```php
     * $container = $this->getContainer();
     * $mock = $container->getMock(MiServicio::class);
     * ```
     *
     * @return TestContainer|null Instancia del contenedor o null si no fue inicializado
     */
    protected function getContainer(): ?TestContainer
    {
        return $this->_autoContainer;
    }

    /**
     * Obtiene un mock específico del contenedor de pruebas
     *
     * Método de conveniencia para acceder directamente a un mock generado
     * por el contenedor sin necesidad de llamar a getContainer() primero.
     *
     * Ejemplo:
     * ```php
     * $serviceMock = $this->getMock(MiServicio::class);
     * $serviceMock->method('getData')->willReturn(['test' => 'data']);
     * ```
     *
     * @param string $class Nombre completo de la clase (FQCN) del mock a obtener
     * @return object|null Instancia del mock o null si no existe
     */
    protected function getMock(string $class): ?object
    {
        return $this->_autoContainer?->getMock($class);
    }
}