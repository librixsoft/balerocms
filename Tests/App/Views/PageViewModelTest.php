<?php

declare(strict_types=1);

namespace Tests\App\Views;

use App\Views\PageViewModel;
use Framework\Core\ConfigSettings;
use Framework\Core\ViewModel;
use PHPUnit\Framework\TestCase;

final class PageViewModelTest extends TestCase
{
    public function testSetPageParamsReturnsDefaultsAndMergesExtras(): void
    {
        $vm = new PageViewModel(
            $this->createMock(ConfigSettings::class),
            new ViewModel()
        );

        $params = $vm->setPageParams(['x' => 'y']);

        $this->assertSame('Virtual Pages', $params['lbl_virtual_pages']);
        $this->assertSame('Home', $params['lbl_home']);
        $this->assertSame('No virtual pages available.', $params['lbl_no_pages']);
        $this->assertSame('Refresh', $params['btn_refresh']);
        $this->assertSame('y', $params['x']);
    }
}
