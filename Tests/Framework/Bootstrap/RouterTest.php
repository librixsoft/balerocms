<?php

namespace Tests\Framework\Bootstrap;

use Framework\Bootstrap\Router;
use Framework\Core\BaseController;
use Framework\Core\ConfigSettings;
use Framework\Core\ErrorConsole;
use Framework\DI\Container;
use Framework\Exceptions\RouterException;
use Framework\Http\RequestHelper;
use Framework\Utils\Redirect;
use PHPUnit\Framework\TestCase;

class RouterTest extends TestCase
{

    private RequestHelper $requestHelper;
    private ConfigSettings $configSettings;
    private Container $container;
    private ErrorConsole $errorConsole;
    private Redirect $redirect;
    private Router $router;

    private string $cacheFile;

    protected function setUp(): void
    {
        if (!defined('BASE_PATH')) {
            define('BASE_PATH', __DIR__ . '/../../Controllers/');
        }

        $this->requestHelper = $this->createMock(RequestHelper::class);

        $inlineJson = json_encode([
            'config' => [
                'system' => [
                    'installed' => 'yes'
                ]
            ]
        ]);

        $this->configSettings = new ConfigSettings('/tmp/router_test.json', $inlineJson);

        $this->container = $this->createMock(Container::class);
        $this->errorConsole = $this->createMock(ErrorConsole::class);
        $this->redirect = $this->createMock(Redirect::class);

        $this->cacheFile = sys_get_temp_dir() . '/controllers.cache.php';
        file_put_contents(
            $this->cacheFile,
            "<?php return [['path' => '/home', 'class' => 'App\\\\Controllers\\\\HomeController']];"
        );

        $this->router = new Router(
            $this->requestHelper,
            $this->configSettings,
            $this->container,
            $this->errorConsole,
            $this->redirect
        );

        $this->router->enableTestingMode();
        $this->router->setCachePath($this->cacheFile);
    }


    protected function tearDown(): void
    {
        if (file_exists($this->cacheFile)) {
            unlink($this->cacheFile);
        }
    }

    public function testFindsControllerFromCache()
    {
        $this->requestHelper->method('getPath')->willReturn('/home');

        $baseController = $this->createMock(BaseController::class);
        $this->container
            ->method('get')
            ->willReturnMap([
                ['App\\Controllers\\HomeController', new \stdClass()],
                [BaseController::class, $baseController],
            ]);

        $baseController->expects($this->once())
            ->method('initControllerAndRoute');

        $this->router->initBalero();
    }

    public function testRedirectsWhenNotInstalled()
    {
        $this->configSettings->installed = 'no';
        $this->requestHelper->method('getPath')->willReturn('/dashboard');

        $this->redirect->expects($this->once())
            ->method('to')
            ->with('/installer');

        $this->router->initBalero();
    }

    public function testRedirectsWhenInstallerAfterInstalled()
    {
        $this->configSettings->installed = 'yes';
        $this->requestHelper->method('getPath')->willReturn('/installer');

        $this->redirect->expects($this->once())
            ->method('to')
            ->with('/');

        $this->router->initBalero();
    }

    public function testThrowsWhenControllerNotFound()
    {
        $this->requestHelper->method('getPath')->willReturn('/unknown');

        $this->expectException(RouterException::class);
        $this->router->initBalero();
    }
}
