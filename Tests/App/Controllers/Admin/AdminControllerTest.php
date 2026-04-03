<?php

namespace Tests\App\Controllers\Admin;

use App\Controllers\Admin\AdminController;
use Framework\Attributes\InjectMocks;
use Framework\Attributes\SetupTestContainer;
use Framework\Testing\TestCase;
use Framework\Utils\Redirect;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;

#[SetupTestContainer]
#[CoversClass(AdminController::class)]
#[TestDox('Test del controlador AdminController')]
class AdminControllerTest extends TestCase
{
    #[InjectMocks]
    private ?AdminController $controller = null;

    private $redirectMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->redirectMock = $this->getMock(Redirect::class);
    }

    #[Test]
    #[TestDox('home redirige a /admin/settings')]
    public function testHomeRedirects(): void
    {
        $this->redirectMock->expects($this->once())
            ->method('to')
            ->with('/admin/settings');

        $this->controller->home();
    }

    #[Test]
    #[TestDox('dashboard redirige a /admin/settings')]
    public function testDashboardRedirects(): void
    {
        $this->redirectMock->expects($this->once())
            ->method('to')
            ->with('/admin/settings');

        $this->controller->dashboard();
    }
}
