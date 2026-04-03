<?php

namespace Tests\App\Controllers\Admin;

use App\Controllers\Admin\AdminSettingsController;
use App\DTO\SettingsDTO;
use App\Services\AdminService;
use Framework\Attributes\InjectMocks;
use Framework\Attributes\SetupTestContainer;
use Framework\Core\View;
use Framework\Http\RequestHelper;
use Framework\Testing\TestCase;
use Framework\Utils\Flash;
use Framework\Utils\Redirect;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;

#[SetupTestContainer]
#[CoversClass(AdminSettingsController::class)]
#[TestDox('Test del controlador AdminSettingsController')]
class AdminSettingsControllerTest extends TestCase
{
    #[InjectMocks]
    private ?AdminSettingsController $controller = null;

    private $adminServiceMock;
    private $viewMock;
    private $requestMock;
    private $flashMock;
    private $redirectMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adminServiceMock = $this->getMock(AdminService::class);
        $this->viewMock = $this->getMock(View::class);
        $this->requestMock = $this->getMock(RequestHelper::class);
        $this->flashMock = $this->getMock(Flash::class);
        $this->redirectMock = $this->getMock(Redirect::class);
    }

    #[Test]
    #[TestDox('settings renderiza admin/dashboard.html con parametros')]
    public function testSettingsRenders(): void
    {
        $this->flashMock->method('has')->willReturn(false);
        $params = ['some' => 'params'];
        $this->adminServiceMock->expects($this->once())
            ->method('getSettingsViewParams')
            ->with([])
            ->willReturn($params);

        $this->viewMock->expects($this->once())
            ->method('render')
            ->with("admin/dashboard.html", $params, false)
            ->willReturn('html content');

        $result = $this->controller->settings();
        $this->assertSame('html content', $result);
    }

    #[Test]
    #[TestDox('postSettings valida y guarda configuraciones')]
    public function testPostSettingsSuccess(): void
    {
        $this->adminServiceMock->expects($this->once())
            ->method('validateSettings')
            ->willReturn(true);
            
        $this->adminServiceMock->expects($this->once())
            ->method('mapAndSaveSettings');
            
        $this->redirectMock->expects($this->once())
            ->method('to')
            ->with('/admin/settings');

        $this->controller->postSettings();
    }

    #[Test]
    #[TestDox('postSettings redirige si validacion falla')]
    public function testPostSettingsValidationFails(): void
    {
        $this->adminServiceMock->expects($this->once())
            ->method('validateSettings')
            ->willReturn(false);
            
        $errors = ['field' => 'error'];
        $this->adminServiceMock->expects($this->once())
            ->method('getValidationErrors')
            ->willReturn($errors);
            
        $this->flashMock->expects($this->once())
            ->method('set')
            ->with('errors', $errors);
            
        $this->redirectMock->expects($this->once())
            ->method('to')
            ->with('/admin/settings');

        $this->controller->postSettings();
    }
}
