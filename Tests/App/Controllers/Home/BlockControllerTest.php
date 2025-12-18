<?php

namespace Tests\App\Controllers\Home;

use App\Controllers\Home\BlockController;
use App\Models\BlockModel;
use App\Models\PageModel;
use App\Views\BlockViewModel;
use Framework\Attributes\InjectMocks;
use Framework\Attributes\SetupTestContainer;
use Framework\Core\View;
use Framework\Testing\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;

#[SetupTestContainer]
#[CoversClass(BlockController::class)]
#[TestDox('Test del controlador BlockController')]
class BlockControllerTest extends TestCase
{
    #[InjectMocks]
    private ?BlockController $controller = null;

    protected function setUp(): void
    {
        parent::setUp();
    }

    #[Test]
    #[TestDox('Verifica que index renderice correctamente')]
    public function testIndexRendersCorrectly(): void
    {
        $viewMock = $this->getMock(View::class);
        $blockModelMock = $this->getMock(BlockModel::class);
        $pageModelMock = $this->getMock(PageModel::class);
        $viewModelMock = $this->getMock(BlockViewModel::class);

        $blockModelMock->expects($this->once())
            ->method('getBlocks')
            ->willReturn(['block1', 'block2']);

        $pageModelMock->expects($this->once())
            ->method('getVirtualPages')
            ->willReturn(['page1', 'page2']);

        $viewModelMock->expects($this->once())
            ->method('setBlockParams')
            ->with([
                'blocks' => ['block1', 'block2'],
                'virtual_pages' => ['page1', 'page2']
            ])
            ->willReturn(['params' => 'values']);

        $viewMock->expects($this->once())
            ->method('render')
            ->with('main.html', ['params' => 'values'])
            ->willReturn('rendered content');

        $result = $this->controller->index();

        $this->assertSame('rendered content', $result);
    }
}
