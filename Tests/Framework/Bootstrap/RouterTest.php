<?php

namespace Tests\Framework\Bootstrap;

use Framework\Bootstrap\Router;
use Framework\Core\BaseController;
use Framework\Core\ConfigSettings;
use Framework\Config\SetupConfig;
use Framework\Core\ErrorConsole;
use Framework\DI\Container;
use Framework\Exceptions\RouterException;
use Framework\Http\RequestHelper;
use Framework\Utils\Redirect;
use PHPUnit\Framework\TestCase;

class RouterTest extends TestCase
{
    private Router $router;
    private RequestHelper $requestHelper;
    private ConfigSettings $configSettings;
    private Container $container;
    private ErrorConsole $errorConsole;
    private Redirect $redirect;
    private string $tempConfigFile;
    private string $tempCacheFile;

    protected function setUp(): void
    {
        // Crear archivo de configuración temporal
        $this->tempConfigFile = sys_get_temp_dir() . '/router_test_' . uniqid() . '.json';
        $this->tempCacheFile = sys_get_temp_dir() . '/controllers_cache_' . uniqid() . '.php';

        // Crear configuración JSON válida
        $configData = [
            'config' => [
                'database' => [
                    'dbhost' => 'localhost',
                    'dbuser' => 'test',
                    'dbpass' => 'test',
                    'dbname' => 'test'
                ],
                'admin' => [
                    'username' => 'admin',
                    'passwd' => 'admin',
                    'email' => 'admin@test.com',
                    'firstname' => 'Admin',
                    'lastname' => 'Test'
                ],
                'system' => [
                    'installed' => 'yes'
                ],
                'site' => [
                    'language' => 'en',
                    'title' => 'Test Site',
                    'description' => 'Test Description',
                    'url' => 'http://localhost',
                    'keywords' => 'test',
                    'basepath' => '/test/',
                    'theme' => 'default',
                    'footer' => 'Test Footer',
                    'multilang' => 'no',
                    'editor' => 'default'
                ]
            ]
        ];
        file_put_contents($this->tempConfigFile, json_encode($configData, JSON_PRETTY_PRINT));

        // Crear mocks
        $this->requestHelper = $this->createMock(RequestHelper::class);
        $this->configSettings = new ConfigSettings(new SetupConfig($this->tempConfigFile));
        $this->container = $this->createMock(Container::class);
        $this->errorConsole = $this->createMock(ErrorConsole::class);
        $this->redirect = $this->createMock(Redirect::class);

        // Crear instancia del router
        $this->router = new Router(
            $this->requestHelper,
            $this->configSettings,
            $this->container,
            $this->errorConsole,
            $this->redirect
        );

        // Activar modo de pruebas
        $this->router->enableTestingMode(true);
        $this->router->setCachePath($this->tempCacheFile);

        // Inicializar sesión para tests
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        // Limpiar archivos temporales
        if (file_exists($this->tempConfigFile)) {
            unlink($this->tempConfigFile);
        }
        if (file_exists($this->tempCacheFile)) {
            unlink($this->tempCacheFile);
        }
    }

    public function testFindsControllerFromCache(): void
    {
        // Crear archivo de caché con controladores
        $cacheContent = <<<'PHP'
<?php
return [
    ['path' => '/', 'class' => 'App\Controllers\HomeController'],
    ['path' => '/admin', 'class' => 'App\Controllers\AdminController'],
    ['path' => '/admin/users', 'class' => 'App\Controllers\Admin\UsersController'],
];
PHP;
        file_put_contents($this->tempCacheFile, $cacheContent);

        $this->requestHelper->method('getPath')->willReturn('/admin/users/edit');

        $mockController = new class {
        };
        $mockBaseController = $this->createMock(BaseController::class);

        $this->container->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                ['App\Controllers\Admin\UsersController', $mockController],
                [BaseController::class, $mockBaseController]
            ]);

        $mockBaseController->expects($this->once())
            ->method('initControllerAndRoute')
            ->with($mockController);

        $this->router->initBalero();
    }

    public function testRedirectsWhenNotInstalled(): void
    {
        // Crear nueva configuración con installed = 'no'
        $configData = [
            'config' => [
                'database' => [
                    'dbhost' => 'localhost',
                    'dbuser' => 'test',
                    'dbpass' => 'test',
                    'dbname' => 'test'
                ],
                'admin' => [
                    'username' => 'admin',
                    'passwd' => 'admin',
                    'email' => 'admin@test.com',
                    'firstname' => 'Admin',
                    'lastname' => 'Test'
                ],
                'system' => [
                    'installed' => 'no'  // Cambiado a 'no'
                ],
                'site' => [
                    'language' => 'en',
                    'title' => 'Test Site',
                    'description' => 'Test Description',
                    'url' => 'http://localhost',
                    'keywords' => 'test',
                    'basepath' => '/test/',
                    'theme' => 'default',
                    'footer' => 'Test Footer',
                    'multilang' => 'no',
                    'editor' => 'default'
                ]
            ]
        ];
        file_put_contents($this->tempConfigFile, json_encode($configData, JSON_PRETTY_PRINT));

        // Recrear ConfigSettings y Router con la nueva configuración
        $this->configSettings = new ConfigSettings(new SetupConfig($this->tempConfigFile));
        $this->router = new Router(
            $this->requestHelper,
            $this->configSettings,
            $this->container,
            $this->errorConsole,
            $this->redirect
        );
        $this->router->enableTestingMode(true);
        $this->router->setCachePath($this->tempCacheFile);

        $this->requestHelper->method('getPath')->willReturn('/dashboard');

        $this->redirect->expects($this->once())
            ->method('to')
            ->with('/installer');

        $result = $this->router->initBalero();

        $this->assertNull($result);
    }

    public function testRedirectsWhenInstallerAfterInstalled(): void
    {
        // Ya está instalado por defecto en setUp (installed = 'yes')
        $this->requestHelper->method('getPath')->willReturn('/installer');

        $this->redirect->expects($this->once())
            ->method('to')
            ->with('/');

        $result = $this->router->initBalero();

        $this->assertNull($result);
    }

    public function testThrowsWhenControllerNotFound(): void
    {
        // Crear archivo de caché sin el controlador buscado
        $cacheContent = <<<'PHP'
<?php
return [
    ['path' => '/', 'class' => 'App\Controllers\HomeController'],
];
PHP;
        file_put_contents($this->tempCacheFile, $cacheContent);

        $this->requestHelper->method('getPath')->willReturn('/nonexistent');

        $this->expectException(RouterException::class);
        $this->expectExceptionMessage('No controller found for path: /nonexistent');

        $this->router->initBalero();
    }

    public function testAllowsInstallerWhenNotInstalled(): void
    {
        // Crear nueva configuración con installed = 'no'
        $configData = [
            'config' => [
                'database' => [
                    'dbhost' => 'localhost',
                    'dbuser' => 'test',
                    'dbpass' => 'test',
                    'dbname' => 'test'
                ],
                'admin' => [
                    'username' => 'admin',
                    'passwd' => 'admin',
                    'email' => 'admin@test.com',
                    'firstname' => 'Admin',
                    'lastname' => 'Test'
                ],
                'system' => [
                    'installed' => 'no'  // No instalado
                ],
                'site' => [
                    'language' => 'en',
                    'title' => 'Test Site',
                    'description' => 'Test Description',
                    'url' => 'http://localhost',
                    'keywords' => 'test',
                    'basepath' => '/test/',
                    'theme' => 'default',
                    'footer' => 'Test Footer',
                    'multilang' => 'no',
                    'editor' => 'default'
                ]
            ]
        ];
        file_put_contents($this->tempConfigFile, json_encode($configData, JSON_PRETTY_PRINT));

        // Recrear ConfigSettings y Router
        $this->configSettings = new ConfigSettings(new SetupConfig($this->tempConfigFile));
        $this->router = new Router(
            $this->requestHelper,
            $this->configSettings,
            $this->container,
            $this->errorConsole,
            $this->redirect
        );
        $this->router->enableTestingMode(true);
        $this->router->setCachePath($this->tempCacheFile);

        $this->requestHelper->method('getPath')->willReturn('/installer');

        // Crear caché con el instalador
        $cacheContent = <<<'PHP'
<?php
return [
    ['path' => '/installer', 'class' => 'App\Controllers\InstallerController'],
];
PHP;
        file_put_contents($this->tempCacheFile, $cacheContent);

        $mockController = new class {
        };
        $mockBaseController = $this->createMock(BaseController::class);

        $this->container->method('get')->willReturnMap([
            ['App\Controllers\InstallerController', $mockController],
            [BaseController::class, $mockBaseController]
        ]);

        // No debe redirigir, debe ejecutar el controlador
        $this->redirect->expects($this->never())->method('to');

        $this->router->initBalero();
    }

    public function testMatchesExactPath(): void
    {
        $cacheContent = <<<'PHP'
<?php
return [
    ['path' => '/about', 'class' => 'App\Controllers\AboutController'],
];
PHP;
        file_put_contents($this->tempCacheFile, $cacheContent);

        $this->requestHelper->method('getPath')->willReturn('/about');

        $mockController = new class {
        };
        $mockBaseController = $this->createMock(BaseController::class);

        $this->container->method('get')->willReturnMap([
            ['App\Controllers\AboutController', $mockController],
            [BaseController::class, $mockBaseController]
        ]);

        $mockBaseController->expects($this->once())
            ->method('initControllerAndRoute');

        $this->router->initBalero();
    }

    public function testPrioritizesLongerPaths(): void
    {
        $cacheContent = <<<'PHP'
<?php
return [
    ['path' => '/admin', 'class' => 'App\Controllers\AdminController'],
    ['path' => '/admin/settings', 'class' => 'App\Controllers\Admin\SettingsController'],
];
PHP;
        file_put_contents($this->tempCacheFile, $cacheContent);

        $this->requestHelper->method('getPath')->willReturn('/admin/settings');

        $mockController = new class {
        };
        $mockBaseController = $this->createMock(BaseController::class);

        $this->container->method('get')->willReturnMap([
            ['App\Controllers\Admin\SettingsController', $mockController],
            [BaseController::class, $mockBaseController]
        ]);

        $mockBaseController->expects($this->once())
            ->method('initControllerAndRoute')
            ->with($mockController);

        $this->router->initBalero();
    }

    public function testHandlesRootPath(): void
    {
        $cacheContent = <<<'PHP'
<?php
return [
    ['path' => '/', 'class' => 'App\Controllers\HomeController'],
];
PHP;
        file_put_contents($this->tempCacheFile, $cacheContent);

        $this->requestHelper->method('getPath')->willReturn('/');

        $mockController = new class {
        };
        $mockBaseController = $this->createMock(BaseController::class);

        $this->container->method('get')->willReturnMap([
            ['App\Controllers\HomeController', $mockController],
            [BaseController::class, $mockBaseController]
        ]);

        $this->router->initBalero();

        $this->assertTrue(true); // Si llegamos aquí, no hubo excepción
    }

    public function testWarnsWhenCacheFileDoesNotExist(): void
    {
        // No crear el archivo de caché
        $this->requestHelper->method('getPath')->willReturn('/test');

        $this->errorConsole->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('Routes cache file does not exist'));

        $this->expectException(RouterException::class);
        $this->expectExceptionMessage('No controller found for path: /test');

        $this->router->initBalero();
    }

    public function testInitializesSessionLanguage(): void
    {
        // Limpiar sesión
        $_SESSION = [];

        $cacheContent = <<<'PHP'
<?php
return [
    ['path' => '/', 'class' => 'App\Controllers\HomeController'],
];
PHP;
        file_put_contents($this->tempCacheFile, $cacheContent);

        $this->requestHelper->method('getPath')->willReturn('/');

        $mockController = new class {
        };
        $mockBaseController = $this->createMock(BaseController::class);

        $this->container->method('get')->willReturnMap([
            ['App\Controllers\HomeController', $mockController],
            [BaseController::class, $mockBaseController]
        ]);

        $this->router->initBalero();

        $this->assertArrayHasKey('lang', $_SESSION);
        $this->assertEquals('en', $_SESSION['lang']);
    }
}