<?php

namespace Tests\App\Controllers\Admin;

use App\Controllers\Admin\AdminMediaController;
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
#[CoversClass(AdminMediaController::class)]
#[TestDox('Test del controlador AdminMediaController')]
class AdminMediaControllerTest extends TestCase
{
    #[InjectMocks]
    private ?AdminMediaController $controller = null;

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
    #[TestDox('postUploader sube imagen')]
    public function testPostUploaderUploadsImage(): void
    {
        $_FILES['file'] = ['name' => 'test.jpg', 'tmp_name' => '/tmp/test', 'error' => 0];
        $response = ['status' => 'ok', 'url' => '/assets/images/uploads/test.jpg'];
        
        $this->uploaderServiceMock->expects($this->once())
            ->method('uploadImage')
            ->willReturn(['url' => '/assets/images/uploads/test.jpg']);

        $result = $this->controller->postUploader();
        $this->assertSame($response, $result);

        unset($_FILES['file']);
    }

    #[Test]
    #[TestDox('getMediaList renderiza admin/dashboard.html')]
    public function testGetMediaListRenders(): void
    {
        $params = ['media' => []];
        $this->adminServiceMock->expects($this->once())
            ->method('getMediaViewParams')
            ->willReturn($params);

        $this->viewMock->expects($this->once())
            ->method('render')
            ->with("admin/dashboard.html", $params, false)
            ->willReturn('html content');

        $result = $this->controller->getMediaList();
        $this->assertSame('html content', $result);
    }

    #[Test]
    #[TestDox('deleteMedia elimina medio y redirige')]
    public function testDeleteMediaDeletesAndRedirects(): void
    {
        $name = 'test.jpg';
        $this->adminServiceMock->expects($this->once())
            ->method('deleteMedia')
            ->with($name);

        $this->redirectMock->expects($this->once())
            ->method('to')
            ->with('/admin/media');

        $this->controller->deleteMedia($name);
    }
}
