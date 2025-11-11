<?php

namespace Tests\Framework\Bootstrap;

use Framework\Bootstrap\Boot;
use Framework\DI\Container;
use Framework\Core\ErrorConsole;
use Framework\Bootstrap\Router;
use Framework\Exceptions\BootException;
use PHPUnit\Framework\TestCase;

class BootTest extends TestCase
{
    private Boot $boot;
    private Container $container;

    protected function setUp(): void
    {

        if (!defined('BASE_PATH')) {
            define('BASE_PATH', __DIR__ . '/../../Controllers/');
        }

        $this->container = $this->createMock(Container::class);

        // Mock ErrorConsole y Router
        $errorConsoleMock = $this->createMock(ErrorConsole::class);
        $routerMock = $this->createMock(Router::class);

        $this->container->method('get')
            ->willReturnMap([
                [ErrorConsole::class, $errorConsoleMock],
                [Router::class, $routerMock],
            ]);

        $this->boot = new Boot($this->container);
        $this->boot->enableTestingMode(); // evita cualquier efecto secundario
    }

    public function testBootCanBeInstantiatedWithoutInit(): void
    {
        $this->assertInstanceOf(Boot::class, $this->boot);
        $this->assertTrue($this->boot->isTestingMode());
    }

    public function testBootRegistersErrorConsole(): void
    {
        // init() no debería fallar en modo testing
        $this->boot->init(loadRouter: false);
        $this->assertTrue($this->boot->isTestingMode());
    }

    public function testAutoloadClassCreatesFakeClassInTestingMode(): void
    {
        $className = 'App\\FakeClass';
        $this->boot->autoloadClass($className);

        $this->assertTrue(class_exists($className));
    }

    public function testAutoloadClassThrowsExceptionInProdMode(): void
    {
        $this->boot->enableTestingMode(false); // modo producción
        $this->expectException(BootException::class);
        $this->boot->autoloadClass('NonExistent\\ClassName');
    }

    public function testSetAndGetTestingMode(): void
    {
        $this->boot->enableTestingMode();
        $this->assertTrue($this->boot->isTestingMode());

        $this->boot->enableTestingMode(false);
        $this->assertFalse($this->boot->isTestingMode());
    }
}
