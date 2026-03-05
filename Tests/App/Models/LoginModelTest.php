<?php

declare(strict_types=1);

namespace Tests\App\Models;

use App\Models\LoginModel;
use PHPUnit\Framework\TestCase;

final class LoginModelTest extends TestCase
{
    public function testLoginModelIsInstantiableEmptyModelPlaceholder(): void
    {
        $model = new LoginModel();

        $this->assertInstanceOf(LoginModel::class, $model);
        $this->assertSame([], get_class_methods($model));
    }
}
