<?php

namespace Tests\App\Controllers\Admin;

use App\Controllers\Admin\AdminPagesController;
use App\Services\Admin\AdminPagesService;
use Framework\Attributes\InjectMocks;
use Framework\Attributes\SetupTestContainer;
use Framework\Core\View;
use Framework\Http\RequestHelper;
use Framework\Testing\TestCase;
use Framework\Utils\Redirect;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;

#[SetupTestContainer]
#[CoversClass(AdminPagesController::class)]
#[TestDox('Test del controlador AdminPagesController')]
class AdminPagesControllerTest extends TestCase
{
    #[InjectMocks]
    private ?AdminPagesController $controller = null;

    private $adminServiceMock;
    private $viewMock;
    private $requestMock;
    private $redirectMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adminServiceMock = $this->getMock(AdminPagesService::class);
        $this->viewMock = $this->getMock(View::class);
        $this->requestMock = $this->getMock(RequestHelper::class);
        $this->redirectMock = $this->getMock(Redirect::class);
    }

    #[Test]
    #[TestDox('getPages renderiza admin/dashboard.html')]
    public function testGetPagesRenders(): void
    {
        $params = ['pages' => []];
        $this->adminServiceMock->expects($this->once())
            ->method('getNewPageViewParams')
            ->willReturn($params);

        $this->viewMock->expects($this->once())
            ->method('render')
            ->with("admin/dashboard.html", $params, false)
            ->willReturn('html content');

        $result = $this->controller->getPages();
        $this->assertSame('html content', $result);
    }

    #[Test]
    #[TestDox('getAllPages renderiza admin/dashboard.html')]
    public function testGetAllPagesRenders(): void
    {
        $params = ['pages' => []];
        $this->adminServiceMock->expects($this->once())
            ->method('getAllPagesViewParams')
            ->willReturn($params);

        $this->viewMock->expects($this->once())
            ->method('render')
            ->with("admin/dashboard.html", $params, false)
            ->willReturn('html content');

        $result = $this->controller->getAllPages();
        $this->assertSame('html content', $result);
    }

    #[Test]
    #[TestDox('postNewPage crea pagina y redirige')]
    public function testPostNewPageCreatesAndRedirects(): void
    {
        $this->requestMock->method('post')->willReturnCallback(function ($key) {
            $values = [
                'virtual_title' => 'Test Title',
                'static_url' => 'test-url',
                'visible' => '1',
                'date' => '2026-01-08',
                'sort_order' => '1',
            ];
            return $values[$key] ?? 'value';
        });
        $this->requestMock->method('raw')->willReturn('content');

        $this->adminServiceMock->expects($this->once())
            ->method('createPage')
            ->with($this->callback(function ($data) {
                return isset($data['sort_order']) && $data['sort_order'] === '1';
            }));

        $this->redirectMock->expects($this->once())
            ->method('to')
            ->with('/admin/pages');

        $this->controller->postNewPage();
    }

    #[Test]
    #[TestDox('editPage renderiza admin/dashboard.html')]
    public function testEditPageRenders(): void
    {
        $id = 1;
        $params = ['page' => []];
        $this->adminServiceMock->expects($this->once())
            ->method('getEditPageViewParams')
            ->with($id)
            ->willReturn($params);

        $this->viewMock->expects($this->once())
            ->method('render')
            ->with("admin/dashboard.html", $params, false)
            ->willReturn('html content');

        $result = $this->controller->editPage($id);
        $this->assertSame('html content', $result);
    }

    #[Test]
    #[TestDox('postEditPage actualiza pagina y redirige')]
    public function testPostEditPageUpdatesAndRedirects(): void
    {
        $id = 1;
        $this->requestMock->method('post')->willReturnCallback(function ($key) {
            $values = [
                'virtual_title' => 'Updated Title',
                'static_url' => 'updated-url',
                'visible' => '1',
                'sort_order' => '2',
            ];
            return $values[$key] ?? 'value';
        });
        $this->requestMock->method('raw')->willReturn('updated content');

        $this->adminServiceMock->expects($this->once())
            ->method('updatePage')
            ->with($id, $this->callback(function ($data) {
                return isset($data['sort_order']) && $data['sort_order'] === '2';
            }));

        $this->redirectMock->expects($this->once())
            ->method('to')
            ->with('/admin/pages');

        $this->controller->postEditPage($id);
    }

    #[Test]
    #[TestDox('postDeletePage elimina pagina y redirige')]
    public function testPostDeletePageDeletesAndRedirects(): void
    {
        $id = 1;
        $this->adminServiceMock->expects($this->once())
            ->method('deletePage')
            ->with($id);

        $this->redirectMock->expects($this->once())
            ->method('to')
            ->with('/admin/pages');

        $this->controller->deletePage($id);
    }
}
