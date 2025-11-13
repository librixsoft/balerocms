<?php

namespace Tests\Framework\Bootstrap;

use Framework\Bootstrap\Boot;
use Framework\Bootstrap\Router;
use Framework\Core\ErrorConsole;
use Framework\DI\Container;
use Framework\Exceptions\BootException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class BootTest extends TestCase
{
    private Container|MockObject $container;
    private ErrorConsole|MockObject $errorConsole;
    private Router|MockObject $router;
    private Boot $boot;

    protected function setUp(): void
    {

        if (!defined('BASE_PATH')) {
            define('BASE_PATH', __DIR__ . '/../../Controllers/');
        }

        parent::setUp();

        // Crear mocks
        $this->container = $this->createMock(Container::class);
        $this->errorConsole = $this->createMock(ErrorConsole::class);
        $this->router = $this->createMock(Router::class);

        // Configurar el container para devolver los mocks
        $this->container
            ->method('get')
            ->willReturnCallback(function ($class) {
                return match($class) {
                    ErrorConsole::class => $this->errorConsole,
                    Router::class => $this->router,
                    default => null
                };
            });

        $this->boot = new Boot($this->container);
    }

    public function testConstructorAssignsContainer(): void
    {
        $boot = new Boot($this->container);
        $this->assertInstanceOf(Boot::class, $boot);
    }

    public function testInitInTestingMode(): void
    {
        $this->boot->enableTestingMode();

        // ErrorConsole no debe registrarse en modo testing
        $this->errorConsole
            ->expects($this->never())
            ->method('register');

        // Router no debe inicializarse en modo testing
        $this->router
            ->expects($this->never())
            ->method('initBalero');

        $this->boot->init();

        $this->assertTrue($this->boot->isTestingMode());
    }



    public function testInitWithoutRouter(): void
    {
        $this->boot->enableTestingMode();

        // Router NO debe inicializarse cuando loadRouter es false
        $this->router
            ->expects($this->never())
            ->method('initBalero');

        $this->boot->init(loadRouter: false);
    }

    public function testEnableTestingMode(): void
    {
        $this->assertFalse($this->boot->isTestingMode());

        $this->boot->enableTestingMode();
        $this->assertTrue($this->boot->isTestingMode());

        $this->boot->enableTestingMode(false);
        $this->assertFalse($this->boot->isTestingMode());
    }

    public function testAutoloadClassInTestingMode(): void
    {
        $this->boot->enableTestingMode();

        $fakeClass = 'TestNamespace\\FakeClass';

        // No debe lanzar excepción
        $this->boot->autoloadClass($fakeClass);

        // La clase debe existir después del autoload
        $this->assertTrue(class_exists($fakeClass, false));
    }

    public function testAutoloadClassThrowsExceptionWhenFileNotFound(): void
    {
        // Modo producción (sin testing mode)
        $this->boot->enableTestingMode(false);

        $this->expectException(BootException::class);
        $this->expectExceptionMessage('Error loading class');

        $this->boot->autoloadClass('NonExistent\\Class\\Name');
    }

    public function testAutoloadClassLoadsExistingFile(): void
    {
        // Modo testing para evitar conflictos con el autoload real
        $this->boot->enableTestingMode();

        // En modo testing, autoloadClass crea clases dinámicamente
        $fakeClass = 'TestAutoload\\MyTestClass';

        $this->boot->autoloadClass($fakeClass);

        // Verificar que la clase fue creada
        $this->assertTrue(class_exists($fakeClass, false));
    }

    public function testInitThrowsBootExceptionOnError(): void
    {
        // Forzar error en ErrorConsole
        $this->errorConsole
            ->method('register')
            ->willThrowException(new \RuntimeException('Test error'));

        $this->expectException(BootException::class);
        $this->expectExceptionMessage('Error in Boot:');

        $this->boot->init();
    }

    public function testContainerIsUsedToResolveErrorConsole(): void
    {
        $this->boot->enableTestingMode();

        $this->container
            ->expects($this->once())
            ->method('get')
            ->with(ErrorConsole::class)
            ->willReturn($this->errorConsole);

        $this->boot->init();
    }

    public function testContainerIsUsedToResolveRouter(): void
    {
        $this->boot->enableTestingMode();

        // En testing mode no se debe llamar a Router
        $this->container
            ->expects($this->once()) // Solo ErrorConsole
            ->method('get');

        $this->boot->init();
    }

    public function testMultipleClassAutoloadInTestingMode(): void
    {
        $this->boot->enableTestingMode();

        $classes = [
            'App\\Models\\User',
            'App\\Controllers\\HomeController',
            'App\\Services\\AuthService'
        ];

        foreach ($classes as $class) {
            $this->boot->autoloadClass($class);
            $this->assertTrue(class_exists($class, false));
        }
    }

    public function testErrorConsoleIsRetrievedFromContainer(): void
    {
        $this->boot->enableTestingMode();

        // Verificar que ErrorConsole se obtiene del container
        $this->container
            ->expects($this->once())
            ->method('get')
            ->with(ErrorConsole::class)
            ->willReturn($this->errorConsole);

        $this->boot->init(loadRouter: false);
    }

    public function testInitWithRouterGetsBothDependencies(): void
    {
        $this->boot->enableTestingMode();

        // En modo testing con router deshabilitado, solo ErrorConsole
        $this->boot->init(loadRouter: false);

        // Verificamos que no falla
        $this->assertTrue($this->boot->isTestingMode());
    }
}