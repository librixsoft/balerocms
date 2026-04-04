<?php

namespace Tests\App\Controllers\Admin;

use App\Controllers\Admin\AdminBlocksController;
use App\Services\Admin\AdminBlocksService;
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
#[CoversClass(AdminBlocksController::class)]
#[TestDox('Test del controlador AdminBlocksController')]
class AdminBlocksControllerTest extends TestCase
{
    #[InjectMocks]
    private ?AdminBlocksController $controller = null;

    private $adminServiceMock;
    private $viewMock;
    private $requestMock;
    private $redirectMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adminServiceMock = $this->getMock(AdminBlocksService::class);
        $this->viewMock = $this->getMock(View::class);
        $this->requestMock = $this->getMock(RequestHelper::class);
        $this->redirectMock = $this->getMock(Redirect::class);
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
