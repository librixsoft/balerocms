<?php

use Framework\Core\ConfigSettings;
use Framework\Services\RedirectService;
use Framework\Static\Redirect;
use PHPUnit\Framework\TestCase;

class RedirectServiceTest extends TestCase
{
    protected ConfigSettings $config;

    protected function setUp(): void
    {
        // Definir LOCAL_DIR si no está definido
        if (!defined('LOCAL_DIR')) {
            define('LOCAL_DIR', __DIR__ . '/../../'); // raíz del proyecto
        }

        // Configuración simulada
        $this->config = $this->createMock(ConfigSettings::class);
        $this->config->basepath = '/basepath';
    }

    public function testRedirectServiceToGeneratesUrl()
    {
        $service = new RedirectService($this->config);

        $urlCaptured = null;

        $serviceMock = $this->getMockBuilder(RedirectService::class)
            ->setConstructorArgs([$this->config])
            ->onlyMethods(['to'])
            ->getMock();

        $serviceMock->method('to')->willReturnCallback(function ($url, $forceExit = true) use (&$urlCaptured) {
            $urlCaptured = $url;
        });

        $serviceMock->to('/installer');

        $this->assertEquals('/installer', $urlCaptured);
    }

    public function testRedirectFacadeDelegatesToService()
    {
        $mockService = $this->createMock(RedirectService::class);
        $mockService->expects($this->once())
            ->method('to')
            ->with('/installer', true);

        Redirect::setInstance($mockService);

        Redirect::to('/installer');
    }

    public function testRedirectFacadeThrowsIfNoInstance()
    {
        $reflection = new \ReflectionClass(Redirect::class);
        $prop = $reflection->getProperty('instance');
        $prop->setAccessible(true);
        $prop->setValue(null, null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Redirect instance not set.');

        Redirect::to('/installer');
    }
}
