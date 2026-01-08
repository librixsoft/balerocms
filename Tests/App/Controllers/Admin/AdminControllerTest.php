<?php

namespace Tests\App\Controllers\Admin;

use App\Controllers\Admin\AdminController;
use App\DTO\SettingsDTO;
use App\Services\AdminService;
use App\Services\UploaderService;
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
#[CoversClass(AdminController::class)]
#[TestDox('Test del controlador AdminController')]
class AdminControllerTest extends TestCase
{
    #[InjectMocks]
    private ?AdminController $controller = null;

    private $adminServiceMock;
    private $uploaderServiceMock;
    private $viewMock;
    private $requestMock;
    private $flashMock;
    private $redirectMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adminServiceMock = $this->getMock(AdminService::class);
        $this->uploaderServiceMock = $this->getMock(UploaderService::class);
        $this->viewMock = $this->getMock(View::class);
        $this->requestMock = $this->getMock(RequestHelper::class);
        $this->flashMock = $this->getMock(Flash::class);
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

    #[Test]
    #[TestDox('getSettings renderiza admin/dashboard.html con parametros')]
    public function testGetSettingsRenders(): void
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

        $result = $this->controller->getSettings();
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

        $this->controller->postDeletePage($id);
    }

    #[Test]
    #[TestDox('postUploader sube imagen')]
    public function testPostUploaderUploadsImage(): void
    {
        $_FILES['file'] = ['name' => 'test.jpg', 'tmp_name' => '/tmp/test', 'error' => 0];
        $response = ['status' => 'success'];
        $this->uploaderServiceMock->expects($this->once())
            ->method('uploadImage')
            ->with($_FILES['file'])
            ->willReturn($response);

        $result = $this->controller->postUploader();
        $this->assertSame($response, $result);

        unset($_FILES['file']);
    }

    #[Test]
    #[TestDox('listBlocks renderiza admin/dashboard.html')]
    public function testListBlocksRenders(): void
    {
        $params = ['blocks' => []];
        $this->adminServiceMock->expects($this->once())
            ->method('getAllBlocksViewParams')
            ->willReturn($params);

        $this->viewMock->expects($this->once())
            ->method('render')
            ->with("admin/dashboard.html", $params, false)
            ->willReturn('html content');

        $result = $this->controller->listBlocks();
        $this->assertSame('html content', $result);
    }

    #[Test]
    #[TestDox('newBlock renderiza admin/dashboard.html')]
    public function testNewBlockRenders(): void
    {
        $params = ['block' => []];
        $this->adminServiceMock->expects($this->once())
            ->method('getNewBlockViewParams')
            ->willReturn($params);

        $this->viewMock->expects($this->once())
            ->method('render')
            ->with("admin/dashboard.html", $params, false)
            ->willReturn('html content');

        $result = $this->controller->newBlock();
        $this->assertSame('html content', $result);
    }

    #[Test]
    #[TestDox('createBlock crea bloque y redirige')]
    public function testCreateBlockCreatesAndRedirects(): void
    {
        $this->requestMock->method('post')->willReturn('value');
        $this->requestMock->method('raw')->willReturn('content');

        $this->adminServiceMock->expects($this->once())
            ->method('createBlock');

        $this->redirectMock->expects($this->once())
            ->method('to')
            ->with('/admin/blocks');

        $this->controller->createBlock();
    }

    #[Test]
    #[TestDox('getEditBlock renderiza admin/dashboard.html')]
    public function testGetEditBlockRenders(): void
    {
        $id = 1;
        $params = ['block' => []];
        $this->adminServiceMock->expects($this->once())
            ->method('getEditBlockViewParams')
            ->with($id)
            ->willReturn($params);

        $this->viewMock->expects($this->once())
            ->method('render')
            ->with("admin/dashboard.html", $params, false)
            ->willReturn('html content');

        $result = $this->controller->getEditBlock($id);
        $this->assertSame('html content', $result);
    }

    #[Test]
    #[TestDox('postEditBlock actualiza bloque y redirige')]
    public function testPostEditBlockUpdatesAndRedirects(): void
    {
        $id = 1;
        $this->requestMock->method('post')->willReturn('value');
        $this->requestMock->method('raw')->willReturn('content');

        $this->adminServiceMock->expects($this->once())
            ->method('updateBlock')
            ->with($id, $this->anything());

        $this->redirectMock->expects($this->once())
            ->method('to')
            ->with('/admin/blocks');

        $this->controller->postEditBlock($id);
    }

    #[Test]
    #[TestDox('deleteBlock elimina bloque y redirige')]
    public function testDeleteBlockDeletesAndRedirects(): void
    {
        $id = 1;
        $this->adminServiceMock->expects($this->once())
            ->method('deleteBlock')
            ->with($id);

        $this->redirectMock->expects($this->once())
            ->method('to')
            ->with('/admin/blocks');

        $this->controller->deleteBlock($id);
    }
}
