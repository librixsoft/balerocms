<?php

namespace Tests\App\Controllers;

use App\Controllers\TestController;
use App\Models\TestModel;
use Framework\Attributes\InjectMocks;
use Framework\Attributes\SetupTestContainer;
use Framework\Core\View;
use Framework\Testing\TestCase;

#[SetupTestContainer]
class TestControllerTest extends TestCase
{
    #[InjectMocks]
    private ?TestController $controller = null;

    protected function setUp(): void
    {
        if (!defined('LOCAL_DIR')) {
            define('LOCAL_DIR', __DIR__ . '/../../Modules/');
        }

        parent::setUp();
    }

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

    public function testModelConnectIsCalled(): void
    {
        $modelMock = $this->getMock(TestModel::class);
        $modelMock->expects($this->once())->method('connect')->willReturn('success');

        $result = $this->controller->testModelConnectMethod();
        $this->assertSame('success', $result);
    }
}