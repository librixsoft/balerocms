<?php

namespace Tests\App\Controllers\Page;

use App\Controllers\Page\PageController;
use App\Models\PageModel;
use App\Views\PageViewModel;
use Framework\Attributes\InjectMocks;
use Framework\Attributes\SetupTestContainer;
use Framework\Core\View;
use Framework\Testing\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use App\Services\PreviewService;

#[SetupTestContainer]
#[CoversClass(PageController::class)]
#[TestDox('Test del controlador PageController')]
class PageControllerTest extends TestCase
{
    #[InjectMocks]
    private ?PageController $controller = null;

    protected function setUp(): void
    {
        parent::setUp();
    }

    #[Test]
    #[TestDox('Verifica que home renderice correctamente')]
    public function testHomeRendersCorrectly(): void
    {
        $viewMock      = $this->getMock(View::class);
        $modelMock     = $this->getMock(PageModel::class);
        $viewModelMock = $this->getMock(PageViewModel::class);

        $modelMock->expects($this->once())
            ->method('getVirtualPages')
            ->willReturn(['page1', 'page2']);

        $viewModelMock->expects($this->once())
            ->method('setPageParams')
            ->with(['virtual_pages' => ['page1', 'page2']])
            ->willReturn(['params' => 'values']);

        $viewMock->expects($this->once())
            ->method('render')
            ->with('main.html', ['params' => 'values'])
            ->willReturn('rendered content');

        $result = $this->controller->home();

        $this->assertSame('rendered content', $result);
    }

    #[Test]
    #[TestDox('Verifica que page renderice correctamente cuando la pagina existe')]
    public function testPageRendersCorrectlyWhenPageExists(): void
    {
        $viewMock      = $this->getMock(View::class);
        $modelMock     = $this->getMock(PageModel::class);
        $viewModelMock = $this->getMock(PageViewModel::class);

        $slug     = 'existing-page';
        $pageData = ['id' => 1, 'slug' => $slug, 'title' => 'Existing Page'];

        $modelMock->expects($this->once())
            ->method('getVirtualPageBySlug')
            ->with($slug)
            ->willReturn($pageData);

        $modelMock->expects($this->once())
            ->method('getVirtualPages')
            ->willReturn(['page1', 'page2']);

        $viewModelMock->expects($this->once())
            ->method('setPageParams')
            ->with([
                'page'          => $pageData,
                'virtual_pages' => ['page1', 'page2'],
            ])
            ->willReturn(['params' => 'values']);

        $viewMock->expects($this->once())
            ->method('render')
            ->with('main.html', ['params' => 'values'])
            ->willReturn('rendered content');

        $result = $this->controller->page($slug);

        $this->assertSame('rendered content', $result);
    }

    #[Test]
    #[TestDox('Verifica que page renderice error cuando la pagina no existe')]
    public function testPageRendersErrorWhenPageDoesNotExist(): void
    {
        $viewMock      = $this->getMock(View::class);
        $modelMock     = $this->getMock(PageModel::class);
        $viewModelMock = $this->getMock(PageViewModel::class);

        $slug = 'non-existing-page';

        $modelMock->expects($this->once())
            ->method('getVirtualPageBySlug')
            ->with($slug)
            ->willReturn([]);

        $modelMock->expects($this->once())
            ->method('getVirtualPages')
            ->willReturn(['page1', 'page2']);

        $viewModelMock->expects($this->once())
            ->method('setPageParams')
            ->with([
                'error_message' => "La página solicitada no existe.",
                'virtual_pages' => ['page1', 'page2'],
            ])
            ->willReturn(['params' => 'values']);

        $viewMock->expects($this->once())
            ->method('render')
            ->with('main.html', ['params' => 'values'])
            ->willReturn('rendered content');

        $result = $this->controller->page($slug);

        $this->assertSame('rendered content', $result);
    }

    #[Test]
    #[TestDox('Verifica que ogImage sirva imagen estatica cuando url es generic')]
    public function testOgImageServesStaticImageWhenUrlIsGeneric(): void
    {
        $previewMock = $this->getMock(PreviewService::class);

        $previewMock->expects($this->once())
            ->method('serveOgImage')
            ->with(null);

        $this->controller->ogImage('generic');
    }

    #[Test]
    #[TestDox('Verifica que ogImage genere imagen dinamica cuando la pagina existe')]
    public function testOgImageGeneratesDynamicImageWhenPageExists(): void
    {
        $previewMock = $this->getMock(PreviewService::class);
        $modelMock   = $this->getMock(PageModel::class);

        $slug     = 'some-real-page';
        $pageData = ['virtual_title' => 'Page Title'];

        $modelMock->expects($this->once())
            ->method('getVirtualPageBySlug')
            ->with($slug)
            ->willReturn($pageData);

        $previewMock->expects($this->once())
            ->method('serveOgImage')
            ->with($pageData);

        $this->controller->ogImage($slug);
    }

    #[Test]
    #[TestDox('Verifica que ogImage sirva imagen estatica cuando la pagina no existe')]
    public function testOgImageServesStaticImageWhenPageDoesNotExist(): void
    {
        $previewMock = $this->getMock(PreviewService::class);
        $modelMock   = $this->getMock(PageModel::class);

        $slug = 'non-existing-page';

        $modelMock->expects($this->once())
            ->method('getVirtualPageBySlug')
            ->with($slug)
            ->willReturn([]);

        $previewMock->expects($this->once())
            ->method('serveOgImage')
            ->with([]);

        $this->controller->ogImage($slug);
    }
}