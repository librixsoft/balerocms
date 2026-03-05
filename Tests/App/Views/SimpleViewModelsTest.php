<?php

declare(strict_types=1);

namespace Tests\App\Views;

use App\Views\BlockViewModel;
use App\Views\ErrorViewModel;
use App\Views\LoginViewModel;
use App\Views\PageViewModel;
use Framework\Core\ConfigSettings;
use Framework\Core\ViewModel;
use PHPUnit\Framework\TestCase;

final class SimpleViewModelsTest extends TestCase
{
    public function testLoginAndPageViewModelsMergeExtraParams(): void
    {
        $cfg = $this->createMock(ConfigSettings::class);

        $login = new LoginViewModel($cfg, new ViewModel());
        $l = $login->setLoginParams(['x' => 'y']);
        $this->assertSame('Login', $l['lbl_login']);
        $this->assertSame('y', $l['x']);

        $page = new PageViewModel($cfg, new ViewModel());
        $p = $page->setPageParams(['count' => 2]);
        $this->assertSame('Virtual Pages', $p['lbl_virtual_pages']);
        $this->assertSame(2, $p['count']);
    }

    public function testErrorAndBlockViewModels(): void
    {
        $cfg = $this->createMock(ConfigSettings::class);

        $err = new ErrorViewModel($cfg);
        $e = $err->setErrorParams(['code' => 500]);
        $this->assertSame('Error', $e['lbl_error_title']);
        $this->assertSame(500, $e['code']);

        $block = new BlockViewModel($cfg);
        $b = $block->setBlockParams(['n' => 1]);
        $this->assertSame('Blocks', $b['lbl_blocks']);
        $this->assertSame(1, $b['n']);
    }
}
