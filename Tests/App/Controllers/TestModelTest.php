<?php

namespace Tests\App\Controllers;

use App\Models\TestModel;
use Framework\Attributes\InjectMocks;
use Framework\Attributes\SetupTestContainer;
use Framework\Testing\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;

#[SetupTestContainer]
#[CoversClass(TestModel::class)]
#[TestDox('Test del modelo TestModel')]
class TestModelTest extends TestCase
{
    #[InjectMocks]
    private ?TestModel $model = null;

    #[Test]
    #[TestDox('Verifica que el modelo se inyecte correctamente')]
    public function debugModel(): void
    {
        $this->assertNotNull($this->model, 'El modelo no debería ser null');
    }

    #[Test]
    #[TestDox('Verifica el funcionamiento de los métodos getter y setter de hello')]
    public function helloGetterAndSetter(): void
    {
        $this->assertNotNull($this->model, 'El modelo debe estar inyectado');
        $this->assertSame('hi', $this->model->getHello());
        $this->model->setHello('hello world');
        $this->assertSame('hello world', $this->model->getHello());
    }
}