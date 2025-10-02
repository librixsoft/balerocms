<?php
use Modules\Test\Controllers\TestController;
use Framework\DI\TestContainer;
use Framework\Core\Controller;
use Modules\Test\Models\TestModel;
use PHPUnit\Framework\TestCase;
use Framework\Attributes\InjectMocks;

class TestControllerTest extends TestCase
{
    #[InjectMocks]
    private TestController $controller;

    #[Inject]
    private TestContainer $container;


    protected function setUp(): void
    {
        // Creamos el TestContainer y lo guardamos
        $this->container = new TestContainer(fn($class) => $this->createMock($class));
        $this->container->initTest($this); // inyecta mocks automáticamente en $controller
    }

    public function testGetNotificationCallsRender(): void
    {
        // Obtenemos el mock de Controller que ya fue inyectado
        $controllerMock = $this->container->getMock(Controller::class);

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
