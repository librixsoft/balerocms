<?php

declare(strict_types=1);

namespace Tests\App\Views;

use App\Views\LoginViewModel;
use Framework\Core\ConfigSettings;
use Framework\Core\ViewModel;
use PHPUnit\Framework\TestCase;

final class LoginViewModelTest extends TestCase
{
    public function testSetLoginParamsReturnsDefaultsAndMergesExtras(): void
    {
        $vm = new LoginViewModel(
            $this->createMock(ConfigSettings::class),
            new ViewModel()
        );

        $params = $vm->setLoginParams(['notice' => 'hi']);

        $this->assertSame('Login', $params['lbl_login']);
        $this->assertSame('Username', $params['lbl_username']);
        $this->assertSame('Password', $params['lbl_password']);
        $this->assertSame('Sign In', $params['btn_login']);
        $this->assertSame('hi', $params['notice']);
    }
}
