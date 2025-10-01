<?php

namespace Tests\Modules\Installer\Controllers;

use Framework\Core\View;
use Framework\Static\Flash;
use Framework\Http\RequestHelper;
use Modules\Installer\Controllers\InstallerController;
use Modules\Installer\Models\InstallerModel;
use Modules\Installer\Views\InstallerViewModel;
use PHPUnit\Framework\TestCase;

final class InstallerControllerTest extends TestCase
{
    private InstallerModel $modelMock;
    private InstallerViewModel $viewModelMock;
    private View $viewStub;
    private RequestHelper $requestMock;
    private InstallerController $controller;

    protected function setUp(): void
    {
        // Definir LOCAL_DIR si no está definido
        if (!defined('LOCAL_DIR')) {
            define('LOCAL_DIR', __DIR__ . '/../../../../'); // raíz del proyecto
        }

        // Mock del modelo
        $this->modelMock = $this->createMock(InstallerModel::class);
        $this->modelMock->method('canConnectToDatabase')->willReturn(true);

        // Mock del ViewModel
        $this->viewModelMock = $this->createMock(InstallerViewModel::class);
        $this->viewModelMock->method('setInstallerParams')->willReturn(['db_ok' => true]);

        // Stub de View
        $this->viewStub = $this->createStub(View::class);
        $this->viewStub->method('render')->willReturn('<html>HTML de prueba</html>');

        // Mock de RequestHelper
        $this->requestMock = $this->createMock(RequestHelper::class);

        // Controller anónimo que usa render() con el stub de View
        $this->controller = new class($this->modelMock, $this->viewModelMock) extends InstallerController {
            public View $view;

            protected function render($template, $params = [], $useTheme = true): string
            {
                return $this->view->render($template, $params, $useTheme);
            }
        };

        // Inyectamos el stub de View
        $this->controller->view = $this->viewStub;

        // Inyectamos el mock de RequestHelper respetando tipificación
        $refController = new \ReflectionObject($this->controller);
        $propRequestHelper = $refController->getProperty('requestHelper');
        $propRequestHelper->setAccessible(true);
        $propRequestHelper->setValue($this->controller, $this->requestMock);
    }

    public function testHomeReturnsHtml(): void
    {
        $html = $this->controller->home();
        $this->assertStringContainsString('<html>', $html);
    }

    public function testHomeFlowWithFlashErrors(): void
    {
        Flash::set('errors', ['username' => 'required']);

        $this->viewModelMock->expects($this->once())
            ->method('setInstallerParams')
            ->with($this->callback(fn($params) => isset($params['errors']) && $params['errors'] === ['username' => 'required']))
            ->willReturnCallback(fn($params) => $params);

        $html = $this->controller->home();
        $this->assertStringContainsString('<html>', $html);

        Flash::delete('errors');
    }

    public function testHomeFlowWithRelevantParams(): void
    {
        Flash::set('errors', ['username' => 'required']);

        $this->modelMock->expects($this->once())
            ->method('canConnectToDatabase')
            ->willReturn(true);

        $this->viewModelMock->expects($this->once())
            ->method('setInstallerParams')
            ->with($this->callback(fn($params) => isset($params['db_ok'])))
            ->willReturn(['html' => '<html>Fake HTML for test</html>']);

        $html = $this->controller->home();
        $this->assertStringContainsString('<html>', $html);

        Flash::delete('errors');
    }
}
