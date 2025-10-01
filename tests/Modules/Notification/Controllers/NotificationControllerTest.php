<?php

namespace Tests\Modules\Notification\Controllers;

use Framework\Core\TestContainer;
use Framework\Core\InjectMocks;
use Modules\Notification\Controllers\NotificationController;
use PHPUnit\Framework\TestCase;
use Framework\Http\RequestHelper;

class NotificationControllerTest extends TestCase
{
    #[InjectMocks]
    private NotificationController $controller;

    private TestContainer $container;

    protected function setUp(): void
    {
        if (!defined('LOCAL_DIR')) {
            define('LOCAL_DIR', __DIR__ . '/../../../../'); // raíz del proyecto
        }

        // Callback para crear mocks dentro del TestCase
        $this->container = new TestContainer(fn($class) => $this->createMock($class));
        $this->container->initTest($this);
    }

    public function testGetNotificationReturnsSuccess(): void
    {
        $result = $this->controller->getNotification();

        $this->assertEquals('success', $result['status']);
        $this->assertEquals('Endpoint /notification is up.', $result['message']);
    }

    public function testPostNotificationDeletesFlash(): void
    {
        // Obtener mock de RequestHelper inyectado automáticamente
        $requestMock = $this->container->getMock(RequestHelper::class);
        $requestMock->method('post')->with('key')->willReturn('foo');

        // Ahora el controller usa su propiedad requestHelper, ya inyectada
        $result = $this->controller->postNotification();

        $this->assertEquals('success', $result['status']);
        $this->assertEquals("Key 'foo' deleted success.", $result['message']);
    }
}
