<?php

namespace Tests\App\Controllers\Error;

use App\Controllers\Error\ErrorController;
use App\Models\BlockModel;
use App\Models\PageModel;
use App\Views\ErrorViewModel;
use Framework\Attributes\InjectMocks;
use Framework\Attributes\SetupTestContainer;
use Framework\Core\View;
use Framework\Testing\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;

#[SetupTestContainer]
#[CoversClass(ErrorController::class)]
#[TestDox('Test del controlador ErrorController')]
class ErrorControllerTest extends TestCase
{
    #[InjectMocks]
    private ?ErrorController $controller = null;

    protected function setUp(): void
    {
        parent::setUp();
    }

    #[Test]
    #[TestDox('Verifica que index renderice correctamente')]
    public function testIndexRendersCorrectly(): void
    {
        $viewMock = $this->getMock(View::class);
        $viewModelMock = $this->getMock(ErrorViewModel::class);
        $blockModelMock = $this->getMock(BlockModel::class);
        $pageModelMock = $this->getMock(PageModel::class);

        $blocks = ['block1', 'block2'];
        $virtualPages = ['page1', 'page2'];
        $params = ['param1' => 'value1'];

        $blockModelMock->expects($this->once())
            ->method('getBlocks')
            ->willReturn($blocks);

        $pageModelMock->expects($this->once())
            ->method('getVirtualPages')
            ->willReturn($virtualPages);

        $viewModelMock->expects($this->once())
            ->method('setErrorParams')
            ->with([
                'blocks' => $blocks,
                'virtual_pages' => $virtualPages,
            ])
            ->willReturn($params);

        $viewMock->expects($this->once())
            ->method('render')
            ->with('error.html', $params)
            ->willReturn('rendered content');

        $result = $this->controller->index();

        $this->assertSame('rendered content', $result);
    }
}
