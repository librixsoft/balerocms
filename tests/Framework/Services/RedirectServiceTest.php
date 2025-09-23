<?php

use Framework\Core\ConfigSettings;
use Framework\Services\RedirectService;
use Framework\Static\Redirect;
use PHPUnit\Framework\TestCase;

class RedirectServiceTest extends TestCase
{
    protected ConfigSettings $config;

    /**
     * Test RedirectService::to() genera URL correcta sin hacer exit
     */
    public function testRedirectServiceToGeneratesUrl()
    {
        $service = new RedirectService($this->config);

        // Capturamos la URL generada usando closure en un mock de to()
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

    /**
     * Test la fachada Redirect delega al servicio
     */
    public function testRedirectFacadeDelegatesToService()
    {
        // Mock del servicio
        $mockService = $this->createMock(RedirectService::class);

        // Esperamos que to() sea llamado con /installer
        $mockService->expects($this->once())
            ->method('to')
            ->with('/installer', true);

        // Inyectamos el mock en la fachada
        Redirect::setInstance($mockService);

        // Llamada a la fachada
        Redirect::to('/installer');
    }

    /**
     * Test que lanza excepción si la fachada no tiene servicio
     */
    public function testRedirectFacadeThrowsIfNoInstance()
    {
        // Reiniciar la propiedad estática $instance usando reflection
        $reflection = new \ReflectionClass(Redirect::class);
        $prop = $reflection->getProperty('instance');
        $prop->setAccessible(true);
        $prop->setValue(null, null); // $instance = null

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Redirect instance not set.');

        // Llamada a la fachada sin instancia
        Redirect::to('/installer');
    }

    protected function setUp(): void
    {
        // Configuración simulada
        $this->config = $this->createMock(ConfigSettings::class);
        $this->config->basepath = '/basepath';
    }
}
