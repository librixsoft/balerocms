<?php

declare(strict_types=1);

namespace Tests\App\Views;

use App\Views\ErrorViewModel;
use Framework\Core\ConfigSettings;
use PHPUnit\Framework\TestCase;

final class ErrorViewModelTest extends TestCase
{
    public function testSetErrorParamsReturnsDefaultsAndMergesExtras(): void
    {
        $vm = new ErrorViewModel($this->createMock(ConfigSettings::class));

        $params = $vm->setErrorParams(['code' => 500]);

        $this->assertSame('Error', $params['lbl_error_title']);
        $this->assertSame('An unexpected error has occurred.', $params['lbl_error_message']);
        $this->assertSame(500, $params['code']);
    }
}
