<?php

declare(strict_types=1);

namespace Tests\App\Views;

use App\Views\BlockViewModel;
use Framework\Core\ConfigSettings;
use PHPUnit\Framework\TestCase;

final class BlockViewModelTest extends TestCase
{
    public function testSetBlockParamsReturnsDefaultsAndMergesExtras(): void
    {
        $vm = new BlockViewModel($this->createMock(ConfigSettings::class));

        $params = $vm->setBlockParams(['custom' => 'ok']);

        $this->assertSame('Blocks', $params['lbl_blocks']);
        $this->assertSame('No blocks available.', $params['lbl_no_blocks']);
        $this->assertSame('Refresh', $params['btn_refresh']);
        $this->assertSame('ok', $params['custom']);
    }
}
