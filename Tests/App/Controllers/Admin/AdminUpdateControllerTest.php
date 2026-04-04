<?php

namespace Tests\App\Controllers\Admin;

use App\Controllers\Admin\AdminUpdateController;
use App\Services\Admin\AdminUpdateService;
use Framework\Attributes\InjectMocks;
use Framework\Attributes\SetupTestContainer;
use Framework\Core\View;
use Framework\Testing\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;

#[SetupTestContainer]
#[CoversClass(AdminUpdateController::class)]
#[TestDox('Test del controlador AdminUpdateController')]
class AdminUpdateControllerTest extends TestCase
{
    #[InjectMocks]
    private ?AdminUpdateController $controller = null;

    private $adminServiceMock;
    private $viewMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adminServiceMock = $this->getMock(AdminUpdateService::class);
        $this->viewMock = $this->getMock(View::class);
    }

    #[Test]
    #[TestDox('updateSystem renderiza admin/dashboard.html')]
    public function testUpdateSystemRenders(): void
    {
        $params = ['update' => []];
        $this->adminServiceMock->expects($this->once())
            ->method('getUpdateViewParams')
            ->willReturn($params);

        $this->viewMock->expects($this->once())
            ->method('render')
            ->with("admin/dashboard.html", $params, false)
            ->willReturn('html content');

        $result = $this->controller->updateSystem();
        $this->assertSame('html content', $result);
    }

    #[Test]
    #[TestDox('selfUpdate ejecuta servicio de auto-actualizacion')]
    public function testSelfUpdateCallsService(): void
    {
        $response = ['status' => 'ok'];
        $this->adminServiceMock->expects($this->once())
            ->method('selfUpdateService')
            ->willReturn($response);

        $result = $this->controller->selfUpdate();
        $this->assertSame($response, $result);
    }

    #[Test]
    #[TestDox('performUpdate ejecuta actualizacion del sistema')]
    public function testPerformUpdateCallsService(): void
    {
        $response = ['status' => 'ok'];
        $this->adminServiceMock->expects($this->once())
            ->method('performUpdate')
            ->willReturn($response);

        $result = $this->controller->performUpdate();
        $this->assertSame($response, $result);
    }
}
