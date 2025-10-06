<?php

namespace Tests\App\Controllers;

use App\Models\TestModel;
use Framework\Attributes\InjectMocks;
use Framework\Attributes\SetupTestContainer;
use Framework\Testing\TestCase;

#[SetupTestContainer]
class TestModelTest extends TestCase
{
    #[InjectMocks]
    private ?TestModel $model = null;

    public function testDebugModel(): void
    {
        $this->assertNotNull($this->model, 'El modelo no debería ser null');
    }

    public function testHelloGetterAndSetter(): void
    {
        $this->assertNotNull($this->model, 'El modelo debe estar inyectado');
        $this->assertSame('hi', $this->model->getHello());
        $this->model->setHello('hello world');
        $this->assertSame('hello world', $this->model->getHello());
    }
}