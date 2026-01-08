<?php

namespace Tests\App\Controllers\Login;

use App\Controllers\Login\LoginController;
use App\Models\LoginModel;
use App\Views\LoginViewModel;
use Framework\Attributes\InjectMocks;
use Framework\Attributes\SetupTestContainer;
use Framework\Core\View;
use Framework\I18n\Translator;
use Framework\Security\LoginManager;
use Framework\Testing\TestCase;
use Framework\Utils\Flash;
use Framework\Utils\Redirect;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;

#[SetupTestContainer]
#[CoversClass(LoginController::class)]
#[TestDox('Test del controlador LoginController')]
class LoginControllerTest extends TestCase
{
    #[InjectMocks]
    private ?LoginController $controller = null;

    protected function setUp(): void
    {
        parent::setUp();
    }

    #[Test]
    #[TestDox('Verifica que home renderice correctamente sin errores')]
    public function testHomeRendersCorrectly(): void
    {
        $viewMock = $this->getMock(View::class);
        $flashMock = $this->getMock(Flash::class);

        $flashMock->expects($this->once())
            ->method('has')
            ->with('login_error')
            ->willReturn(false);

        $viewMock->expects($this->once())
            ->method('render')
            ->with('admin/login.html', [], false)
            ->willReturn('rendered content');

        $result = $this->controller->home();

        $this->assertSame('rendered content', $result);
    }

    #[Test]
    #[TestDox('Verifica que home renderice correctamente con error de login')]
    public function testHomeRendersCorrectlyWithLoginError(): void
    {
        $viewMock = $this->getMock(View::class);
        $flashMock = $this->getMock(Flash::class);

        $errorMessage = 'Invalid credentials';

        $flashMock->expects($this->once())
            ->method('has')
            ->with('login_error')
            ->willReturn(true);

        $flashMock->expects($this->once())
            ->method('get')
            ->with('login_error')
            ->willReturn($errorMessage);

        $viewMock->expects($this->once())
            ->method('render')
            ->with('admin/login.html', ['login_error' => $errorMessage], false)
            ->willReturn('rendered content');

        $result = $this->controller->home();

        $this->assertSame('rendered content', $result);
    }

    #[Test]
    #[TestDox('Verifica que login redirija a settings en caso de éxito')]
    public function testLoginRedirectsOnSuccess(): void
    {
        $loginManagerMock = $this->getMock(LoginManager::class);
        $redirectMock = $this->getMock(Redirect::class);

        $loginManagerMock->expects($this->once())
            ->method('handleLogin')
            ->willReturn(true);

        $redirectMock->expects($this->once())
            ->method('to')
            ->with('/admin/settings');

        $this->controller->login();
    }

    #[Test]
    #[TestDox('Verifica que login redirija a login con error en caso de fallo')]
    public function testLoginRedirectsOnFailure(): void
    {
        $loginManagerMock = $this->getMock(LoginManager::class);
        $redirectMock = $this->getMock(Redirect::class);
        $translatorMock = $this->getMock(Translator::class);
        $flashMock = $this->getMock(Flash::class);

        $rawMessage = 'auth.failed';
        $translatedMessage = 'Authentication failed';

        $loginManagerMock->expects($this->once())
            ->method('handleLogin')
            ->willReturn(false);

        $loginManagerMock->expects($this->once())
            ->method('getMessage')
            ->willReturn($rawMessage);

        $translatorMock->expects($this->once())
            ->method('t')
            ->with($rawMessage)
            ->willReturn($translatedMessage);

        $flashMock->expects($this->once())
            ->method('set')
            ->with('login_error', $translatedMessage);

        $redirectMock->expects($this->once())
            ->method('to')
            ->with('/login/');

        $this->controller->login();
    }

    #[Test]
    #[TestDox('Verifica que logout cierre sesión y redirija')]
    public function testLogout(): void
    {
        $loginManagerMock = $this->getMock(LoginManager::class);
        $redirectMock = $this->getMock(Redirect::class);

        $loginManagerMock->expects($this->once())
            ->method('logout');

        $redirectMock->expects($this->once())
            ->method('to')
            ->with('/login/');

        $this->controller->logout();
    }
}
