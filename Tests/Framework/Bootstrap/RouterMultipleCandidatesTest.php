<?php

namespace Tests\Framework\Bootstrap;

use Framework\Bootstrap\Router;
use Framework\Core\BaseController;
use Framework\Core\ConfigSettings;
use Framework\Config\SetupConfig;
use Framework\Core\ErrorConsole;
use Framework\DI\Context;
use Framework\Exceptions\RouterException;
use Framework\Http\RequestHelper;
use Framework\Utils\Redirect;
use PHPUnit\Framework\TestCase;

class RouterMultipleCandidatesTest extends TestCase
{
    private Router $router;
    private RequestHelper $requestHelper;
    private ConfigSettings $configSettings;
    private Context $context;
    private string $tempConfigFile;
    private string $tempCacheFile;

    protected function setUp(): void
    {
        $this->tempConfigFile = sys_get_temp_dir() . '/router_test_multi_' . uniqid() . '.json';
        $this->tempCacheFile = sys_get_temp_dir() . '/controllers_cache_multi_' . uniqid() . '.php';

        $configData = [
            'config' => [
                'system' => ['installed' => 'yes'],
                'site' => ['language' => 'en', 'url' => 'http://localhost', 'basepath' => '/']
            ]
        ];
        file_put_contents($this->tempConfigFile, json_encode($configData));

        $this->requestHelper = $this->createMock(RequestHelper::class);
        $this->configSettings = new ConfigSettings(new SetupConfig($this->tempConfigFile));
        $this->context = $this->createMock(Context::class);
        
        $this->router = new Router(
            $this->requestHelper,
            $this->configSettings,
            $this->context,
            $this->createMock(ErrorConsole::class),
            $this->createMock(Redirect::class)
        );

        $this->router->enableTestingMode(true);
        $this->router->setCachePath($this->tempCacheFile);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempConfigFile)) unlink($this->tempConfigFile);
        if (file_exists($this->tempCacheFile)) unlink($this->tempCacheFile);
    }

    public function testTriesNextControllerWhenRouteNotFound(): void
    {
        // Cache has two controllers with same path
        $cacheContent = <<<'PHP'
<?php
return [
    ['path' => '/admin', 'class' => 'App\Controllers\Admin\BlocksController'],
    ['path' => '/admin', 'class' => 'App\Controllers\Admin\SettingsController'],
];
PHP;
        file_put_contents($this->tempCacheFile, $cacheContent);

        $this->requestHelper->method('getPath')->willReturn('/admin/settings');

        $blocksController = new class {};
        $settingsController = new class {};
        $baseController = $this->createMock(BaseController::class);

        $this->context->method('get')->willReturnMap([
            ['App\Controllers\Admin\BlocksController', $blocksController],
            ['App\Controllers\Admin\SettingsController', $settingsController],
            [BaseController::class, $baseController]
        ]);

        // First call to initControllerAndRoute (for BlocksController) fails with Route not found
        $baseController->expects($this->exactly(2))
            ->method('initControllerAndRoute')
            ->willReturnCallback(function($instance) use ($blocksController, $settingsController) {
                if ($instance === $blocksController) {
                    throw new RouterException("Error loading controller 'App\Controllers\Admin\BlocksController': Route not found: '/admin/settings'");
                }
                if ($instance === $settingsController) {
                    return; // Success!
                }
            });

        $this->router->initBalero();
        $this->assertTrue(true); // Reached success
    }

    public function testFailsWhenAllCandidatesFail(): void
    {
        $cacheContent = <<<'PHP'
<?php
return [
    ['path' => '/admin', 'class' => 'App\Controllers\Admin\BlocksController'],
];
PHP;
        file_put_contents($this->tempCacheFile, $cacheContent);

        $this->requestHelper->method('getPath')->willReturn('/admin/unknown');

        $blocksController = new class {};
        $baseController = $this->createMock(BaseController::class);

        $this->context->method('get')->willReturnMap([
            ['App\Controllers\Admin\BlocksController', $blocksController],
            [BaseController::class, $baseController]
        ]);

        $baseController->method('initControllerAndRoute')
            ->willThrowException(new RouterException("Error loading controller 'App\Controllers\Admin\BlocksController': Route not found: '/admin/unknown'"));

        $this->expectException(RouterException::class);
        $this->expectExceptionMessage("Route not found: '/admin/unknown'");

        $this->router->initBalero();
    }
}
