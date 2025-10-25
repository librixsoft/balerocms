<?php

namespace Tests\App\Controllers\Test;

use App\Controllers\Test\TestController;
use App\Models\TestModel;
use Framework\Attributes\InjectMocks;
use Framework\Attributes\SetupTestContainer;
use Framework\Core\View;
use Framework\Testing\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;

#[SetupTestContainer]
#[CoversClass(TestController::class)]
#[TestDox('Test del controlador TestController')]
class TestControllerTest extends TestCase
{
    #[InjectMocks]
    private ?TestController $controller = null;

    protected function setUp(): void
    {
        if (!defined('BASE_PATH')) {
            define('BASE_PATH', __DIR__ . '/../../Modules/');
        }

        parent::setUp();
    }

    #[Test]
    #[TestDox('Verifica que getNotification llame al render correctamente')]
    public function testGetNotificationCallsRender(): void
    {
        $viewMock = $this->getMock(View::class);

        $viewMock
            ->expects($this->once())
            ->method('render')
            ->with('test.html', [], false)
            ->willReturn('rendered content');

        $result = $this->controller->getNotification();

        $this->assertSame('rendered content', $result);
    }

    #[Test]
    #[TestDox('Verifica que testModelConnectMethod invoque connect del modelo')]
    public function testModelConnectIsCalled(): void
    {
        $modelMock = $this->getMock(TestModel::class);
        $modelMock->expects($this->once())->method('connect')->willReturn('success');

        $result = $this->controller->testModelConnectMethod();
        $this->assertSame('success', $result);
    }
}
