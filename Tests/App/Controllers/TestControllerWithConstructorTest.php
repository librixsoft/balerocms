<?php

namespace Tests\App\Controllers;

use App\Controllers\TestControllerWithConstructor;
use App\Models\TestModel;
use Framework\Attributes\InjectMocks;
use Framework\Attributes\SetupTestContainer;
use Framework\Core\View;
use Framework\Testing\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;

#[SetupTestContainer]
#[CoversClass(TestControllerWithConstructor::class)]
#[TestDox('Test del controlador TestControllerWithConstructor')]
class TestControllerWithConstructorTest extends TestCase
{
    #[InjectMocks]
    private ?TestControllerWithConstructor $controller = null;

    protected function setUp(): void
    {
        if (!defined('BASE_PATH')) {
            define('BASE_PATH', __DIR__ . '/../../Modules/');
        }

        parent::setUp();
    }

    #[Test]
    #[TestDox('Verifica que getNotification llame al render correctamente (inyección por constructor)')]
    public function testGetNotificationCallsRender(): void
    {
        $viewMock = $this->getMock(View::class);

        $viewMock
            ->expects($this->once())
            ->method('render')
            ->with('test.html', [], false)
            ->willReturn('rendered content');

        // Reemplazamos la dependencia View en el controlador
        $this->setPrivateProperty($this->controller, 'view', $viewMock);

        $result = $this->controller->getNotification();

        $this->assertSame('rendered content', $result);
    }


    /**
     * Helper para modificar propiedades privadas en pruebas.
     */
    private function setPrivateProperty(object $object, string $property, mixed $value): void
    {
        $ref = new \ReflectionClass($object);
        $prop = $ref->getProperty($property);
        $prop->setAccessible(true);
        $prop->setValue($object, $value);
    }
}
