<?php

namespace Tests\Modules\Notification\Controllers;

use Framework\Core\TestContainer;
use Framework\Core\InjectMocks;
use Modules\Notification\Controllers\NotificationController;
use PHPUnit\Framework\TestCase;
use Framework\Http\RequestHelper;

class NotificationControllerTest extends TestCase
{
    /**
     * La clase que vamos a probar (System Under Test)
     * #[InjectMocks] indica al TestContainer que debe inyectar
     * todas las dependencias automáticamente.
     */
    #[InjectMocks]
    private NotificationController $controller;

    /**
     * El TestContainer se encarga de:
     * 1. Leer la clase marcada con #[InjectMocks]
     * 2. Detectar su constructor y las dependencias
     * 3. Crear mocks de esas dependencias usando el callback que le pasemos
     * 4. Inyectarlos automáticamente en el controller
     */
    private TestContainer $container;

    protected function setUp(): void
    {
        if (!defined('LOCAL_DIR')) {
            define('LOCAL_DIR', __DIR__ . '/../../../../'); // raíz del proyecto
        }

        // Inicializamos el container con un callback que crea mocks automáticamente
        // Cada vez que el container necesita una clase para inyectar, llamará a este callback
        $this->container = new TestContainer(fn($class) => $this->createMock($class));

        /**
         * Esta llamada analiza todas las propiedades del test:
         * - Encuentra #[InjectMocks] ($controller)
         * - Encuentra #[Inject] si hubiera dependencias explícitas
         * - Usa reflexión para detectar el constructor del controller
         * - Crea mocks de cada dependencia usando el callback
         * - Inyecta los mocks en el constructor y/o propiedades
         */
        $this->container->initTest($this);
    }

    public function testGetNotificationReturnsSuccess(): void
    {
        /**
         * En este punto:
         * - $this->controller ya tiene todas sus dependencias inyectadas como mocks
         * - Podemos llamar a cualquier método del controller
         */
        $result = $this->controller->getNotification();

        // Verificamos la respuesta esperada
        $this->assertEquals('success', $result['status']);
        $this->assertEquals('Endpoint /notification is up.', $result['message']);
    }

    public function testPostNotificationDeletesFlash(): void
    {
        /**
         * Obtenemos el mock de RequestHelper que fue inyectado automáticamente
         * por el TestContainer al controller. Podemos configurarlo según el test.
         */
        $requestMock = $this->container->getMock(RequestHelper::class);
        $requestMock->method('post')->with('key')->willReturn('foo');

        // Ahora el controller usa su propiedad requestHelper, ya inyectada
        $result = $this->controller->postNotification();

        // Verificamos que el resultado sea correcto
        $this->assertEquals('success', $result['status']);
        $this->assertEquals("Key 'foo' deleted success.", $result['message']);
    }
}
