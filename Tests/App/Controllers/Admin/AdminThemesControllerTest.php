<?php

namespace Tests\App\Controllers\Admin;

use App\Controllers\Admin\AdminThemesController;
use App\Services\Admin\AdminThemesService;
use Framework\Attributes\InjectMocks;
use Framework\Attributes\SetupTestContainer;
use Framework\Core\View;
use Framework\Testing\TestCase;
use Framework\Utils\Redirect;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;

#[SetupTestContainer]
#[CoversClass(AdminThemesController::class)]
#[TestDox('Test del controlador AdminThemesController')]
class AdminThemesControllerTest extends TestCase
{
    #[InjectMocks]
    private ?AdminThemesController $controller = null;

    private $adminServiceMock;
    private $viewMock;
    private $redirectMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adminServiceMock = $this->getMock(AdminThemesService::class);
        $this->viewMock = $this->getMock(View::class);
        $this->redirectMock = $this->getMock(Redirect::class);
    }

    #[Test]
    #[TestDox('getThemes renderiza admin/dashboard.html')]
    public function testGetThemesRenders(): void
    {
        $params = ['themes' => []];
        $this->adminServiceMock->expects($this->once())
            ->method('getThemesViewParams')
            ->willReturn($params);

        $this->viewMock->expects($this->once())
            ->method('render')
            ->with("admin/dashboard.html", $params, false)
            ->willReturn('html content');

        $result = $this->controller->getThemes();
        $this->assertSame('html content', $result);
    }

    #[Test]
    #[TestDox('activateTheme activa un tema y redirige')]
    public function testActivateThemeAndRedirects(): void
    {
        $themeName = 'test-theme';
        $this->adminServiceMock->expects($this->once())
            ->method('activateTheme')
            ->with($themeName);

        $this->redirectMock->expects($this->once())
            ->method('to')
            ->with('/admin/themes');

        $this->controller->activateTheme($themeName);
    }

    #[Test]
    #[TestDox('deleteTheme elimina un tema y redirige')]
    public function testDeleteThemeAndRedirects(): void
    {
        $themeName = 'test-theme';
        $this->adminServiceMock->expects($this->once())
            ->method('deleteTheme')
            ->with($themeName);

        $this->redirectMock->expects($this->once())
            ->method('to')
            ->with('/admin/themes');

        $this->controller->deleteTheme($themeName);
    }
}
