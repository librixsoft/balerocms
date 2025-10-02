<?php

namespace Modules\Test\Tests\Models;

use Modules\Test\Models\TestModel;
use Framework\Core\TestContainer;
use PHPUnit\Framework\TestCase;
use Framework\Attributes\InjectMocks;

class TestModelTest extends TestCase
{
    #[InjectMocks]
    private ?TestModel $model = null;

    #[Inject]
    private ?TestContainer $container = null;

    protected function setUp(): void
    {
        $this->container = new TestContainer(fn($class) => $this->createMock($class));
        $this->container->initTest($this); // inyecta mocks automáticamente en $model
    }


    public function testHelloGetterAndSetter(): void
    {
        // Verificar valor inicial del getter
        $this->assertSame('hi', $this->model->getHello());

        // Usar setter para cambiar el valor
        $this->model->setHello('hello world');

        // Verificar que el getter devuelve el valor actualizado
        $this->assertSame('hello world', $this->model->getHello());
    }

}
