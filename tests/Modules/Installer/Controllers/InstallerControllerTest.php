<?php

use Framework\Core\View;
use Framework\Static\Flash;
use Modules\Installer\Controllers\InstallerController;
use Modules\Installer\Models\InstallerModel;
use Modules\Installer\Views\InstallerViewModel;
use PHPUnit\Framework\TestCase;

class InstallerControllerTest extends TestCase
{
    private $modelMock;
    private $viewModelMock;
    private $viewStub;
    private $controller;

    /**
     * Verifica que el método home retorna HTML
     */
    public function testHomeReturnsHtml()
    {
        $html = $this->controller->home();
        $this->assertStringContainsString('HTML', $html);
    }

    /**
     * Verifica el flujo del método home con errores en Flash
     */
    public function testHomeFlowWithFlashErrors()
    {
        /**
         * Expectation: el método del modelo será llamado
         */
        $this->modelMock->expects($this->once())
            ->method('canConnectToDatabase');

        /**
         * Expectation: setInstallerParams será llamado y recibirá los errores de Flash
         */
        $this->viewModelMock->expects($this->once())
            ->method('setInstallerParams')
            ->with($this->callback(fn($params) => isset($params['errors']) && $params['errors'] === ['username' => 'required']))
            ->willReturnCallback(fn($params) => $params);

        /**
         * Simular errores en Flash
         */
        Flash::set('errors', ['username' => 'required']);

        // Llamar al método
        $html = $this->controller->home();

        // Revisar que la salida sigue siendo HTML
        $this->assertStringContainsString('<html>', $html);

        // Limpiar Flash para otros tests
        Flash::delete('errors');
    }

    /**
     * Verifica el flujo del método home con parámetros relevantes
     */
    public function testHomeFlowWithRelevantParams()
    {
        /**
         * Configurar Flash con errores simulados
         */
        Flash::set('errors', ['username' => 'required']);

        /**
         * Mock del modelo: canConnectToDatabase devuelve true
         */
        $this->modelMock->expects($this->once())
            ->method('canConnectToDatabase')
            ->willReturn(true);

        /**
         * Mock del ViewModel: acepta cualquier array que tenga al menos 'db_ok'
         */
        $this->viewModelMock->expects($this->once())
            ->method('setInstallerParams')
            ->with($this->callback(function ($params) {
                // Permitimos claves extra (como 'errors') y solo verificamos 'db_ok'
                return isset($params['db_ok']);
            }))
            ->willReturn(['html' => '<html>Fake HTML for test</html>']);

        // Llamar al método home del controller
        $html = $this->controller->home();

        // Aserciones: salida HTML
        $this->assertStringContainsString('<html>', $html);

        // Limpiar Flash para otros tests
        Flash::delete('errors');
    }

    /**
     * Testea InstallerController::postInstall con datos válidos
     */
    public function testPostInstallWithValidData()
    {
        // Mock del RequestHelper para simular POST
        $requestMock = $this->createMock(\Framework\Http\RequestHelper::class);
        $requestMock->method('post')->willReturnMap([
            ['dbhost', null, 'localhost'],
            ['dbuser', null, 'root'],
            ['dbpass', null, ''],
            ['dbname', null, 'balerocms'],
            ['title', null, 'Mi sitio'],
            ['url', null, 'http://localhost'],
            ['description', null, 'Descripción'],
            ['keywords', null, 'cms,php'],
            ['basepath', null, '/'],
            ['username', null, 'anibal'],
            ['passwd', null, '123456'],
            ['passwd2', null, '123456'],
            ['firstname', null, 'Anibal'],
            ['lastname', null, 'Gomez'],
            ['email', null, 'anibal@example.com'],
        ]);

        // Mock del ConfigSettings
        $configMock = $this->createMock(\Framework\Core\ConfigSettings::class);

        // Mock de RedirectService y set en la fachada
        $redirectMock = $this->createMock(\Framework\Services\RedirectService::class);
        $redirectMock->expects($this->once())
            ->method('to')
            ->with('/installer/', true);

        \Framework\Static\Redirect::setInstance($redirectMock);

        // Inyectar mocks en el controller mediante reflection
        $refController = new \ReflectionObject($this->controller);

        $propRequest = $refController->getProperty('request');
        $propRequest->setAccessible(true);
        $propRequest->setValue($this->controller, $requestMock);

        $propConfig = $refController->getProperty('configSettings');
        $propConfig->setAccessible(true);
        $propConfig->setValue($this->controller, $configMock);

        // Llamada al método postInstall
        $this->controller->postInstall();

        // Comprobamos que Flash no tenga errores
        $errors = \Framework\Static\Flash::get('errors', []);
        $this->assertEmpty($errors);
    }

    protected function setUp(): void
    {
        /**
         * Mock del modelo
         */
        $this->modelMock = $this->createMock(InstallerModel::class);
        $this->modelMock->method('canConnectToDatabase')->willReturn(true);

        /**
         * Mock del ViewModel
         */
        $this->viewModelMock = $this->createMock(InstallerViewModel::class);
        $this->viewModelMock->method('setInstallerParams')
            ->willReturn(['db_ok' => true]);

        /**
         * Stub de View que devuelve HTML genérico
         */
        $this->viewStub = $this->createStub(View::class);
        $this->viewStub->method('render')->willReturn('<html>HTML de prueba</html>');

        /**
         * Controller con render() que llama al View real
         */
        $this->controller = new class($this->modelMock, $this->viewModelMock) extends InstallerController {
            public View $view;

            /**
             * Render que delega en el stub de View
             */
            protected function render($template, $params = [], $useTheme = true): string
            {
                return $this->view->render($template, $params, $useTheme);
            }
        };

        /**
         * Inyectamos el stub
         */
        $this->controller->view = $this->viewStub;
    }


}
