<?php

namespace Tests\Framework\Core;

use Framework\Core\Boot;
use Framework\Core\ErrorConsole;
use Framework\Exceptions\BootException;
use Framework\Routing\Router;
use PHPUnit\Framework\TestCase;

class BootTest extends TestCase
{
    private Boot $boot;

    protected function setUp(): void
    {
        // Crear un mock de Router para no ejecutar controladores reales
        $mockRouter = $this->createMock(Router::class);

        // Instanciar Boot con Router mockeado y sin inicializar initBalero
        $this->boot = new Boot(router: $mockRouter, loadRouter: false);

        // Definir LOCAL_DIR solo si no existe
        if (!defined('LOCAL_DIR')) {
            define('LOCAL_DIR', sys_get_temp_dir());
        }
    }

    public function testBootRegistersErrorConsole(): void
    {
        $reflection = new \ReflectionClass($this->boot);
        $property = $reflection->getProperty('errorConsole');
        $property->setAccessible(true);

        $errorConsole = $property->getValue($this->boot);
        $this->assertInstanceOf(ErrorConsole::class, $errorConsole);
    }

    public function testAutoloadClassLoadsFile(): void
    {
        // Creamos una clase temporal para probar autoload
        $tmpDir = sys_get_temp_dir() . '/TestClass.php';
        file_put_contents($tmpDir, "<?php class TestClass {}");

        // Llamamos autoload directamente
        $this->boot->autoloadClass('TestClass');

        // Verificamos que la clase ahora existe
        $this->assertTrue(class_exists('TestClass'));

        // Limpiar
        unlink($tmpDir);
    }

    public function testAutoloadClassThrowsExceptionForMissingClass(): void
    {
        $this->expectException(BootException::class);
        $this->boot->autoloadClass('NonExistentClass');
    }
}
