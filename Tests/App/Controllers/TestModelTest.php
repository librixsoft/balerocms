<?php

namespace Tests\App\Controllers;

use App\Models\TestModel;
use Framework\Attributes\InjectMocks;
use Framework\DI\TestContainer;
use PHPUnit\Framework\TestCase;
use Framework\Attributes\Inject;

class TestModelTest extends TestCase
{
    #[InjectMocks]
    private ?TestModel $model = null;

    #[Inject]
    private ?TestContainer $container = null;

    protected function setUp(): void
    {

// tests/bootstrap.php o al inicio de tu TestCase
        if (!defined('LOCAL_DIR')) {
            define('LOCAL_DIR', __DIR__ . '/../Modules/'); // ajusta según tu estructura
        }

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
