<?php

namespace Tests\App\Controllers;

use App\Controllers\TestController;
use Framework\Attributes\InjectMocks;
use Framework\Core\View;
use Framework\DI\TestContainer;
use Modules\Test\Models\TestModel;
use PHPUnit\Framework\TestCase;

class TestControllerTest extends TestCase
{
    #[InjectMocks]
    private TestController $controller;

    #[Inject]
    private TestContainer $container;


    protected function setUp(): void
    {

// tests/bootstrap.php o al inicio de tu TestCase
        if (!defined('LOCAL_DIR')) {
            define('LOCAL_DIR', __DIR__ . '/../Modules/'); // ajusta según tu estructura
        }

        // Creamos el TestContainer y lo guardamos
        $this->container = new TestContainer(fn($class) => $this->createMock($class));
        $this->container->initTest($this); // inyecta mocks automáticamente en $controller
    }

    public function testGetNotificationCallsRender(): void
    {
        // Obtenemos el mock de Controller que ya fue inyectado
        $controllerMock = $this->container->getMock(View::class);

        $controllerMock
            ->expects($this->once())
            ->method('render')
            ->with('test.html', [], false)
            ->willReturn('rendered content');

        $result = $this->controller->getNotification();

        $this->assertSame('rendered content', $result);
    }

    public function testModelConnectIsCalled(): void
    {
        $modelMock = $this->container->getMock(TestModel::class);
        $modelMock->expects($this->once())->method('connect')->willReturn('success');

        $result = $this->controller->testModelConnectMethod();
        $this->assertSame('success', $result);
    }


}
