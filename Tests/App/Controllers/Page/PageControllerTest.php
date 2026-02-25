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
use Framework\Http\RequestHelper;

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
        $viewMock = $this->getMock(View::class);
        $modelMock = $this->getMock(PageModel::class);
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
        $viewMock = $this->getMock(View::class);
        $modelMock = $this->getMock(PageModel::class);
        $viewModelMock = $this->getMock(PageViewModel::class);

        $slug = 'existing-page';
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
                'page' => $pageData,
                'virtual_pages' => ['page1', 'page2']
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
        $viewMock = $this->getMock(View::class);
        $modelMock = $this->getMock(PageModel::class);
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
                'virtual_pages' => ['page1', 'page2']
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
    #[TestDox('Verifica que ogImage genere imagen generica cuando url es generic')]
    public function testOgImageGeneratesGenericWhenUrlIsGeneric(): void
    {
        $requestMock = $this->getMock(RequestHelper::class);
        $previewMock = $this->getMock(PreviewService::class);

        // Simulated query parameter
        $requestMock->expects($this->once())
            ->method('get')
            ->with('title')
            ->willReturn(urlencode('Test Title'));

        $previewMock->expects($this->once())
            ->method('generateOpenGraphImage')
            ->with('Test Title');

        // Execute method
        $this->controller->ogImage('generic');
    }

    #[Test]
    #[TestDox('Verifica que ogImage genere imagen de la pagina cuando la url no es generic')]
    public function testOgImageGeneratesPageImageWhenUrlIsNotGeneric(): void
    {
        $previewMock = $this->getMock(PreviewService::class);
        $modelMock = $this->getMock(PageModel::class);

        $slug = 'some-real-page';
        $pageData = ['virtual_title' => 'Page Title'];

        $modelMock->expects($this->once())
            ->method('getVirtualPageBySlug')
            ->with($slug)
            ->willReturn($pageData);

        $previewMock->expects($this->once())
            ->method('generateOpenGraphImage')
            ->with('Page Title');

        // Execute method
        $this->controller->ogImage($slug);
    }
}
