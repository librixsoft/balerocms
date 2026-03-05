<?php

declare(strict_types=1);

namespace Tests\Framework\Config;

use Framework\Config\ContextConfig;
use Framework\Core\ConfigSettings;
use Framework\Core\View;
use Framework\DI\Container;
use PHPUnit\Framework\TestCase;

final class ContextConfigTest extends TestCase
{
    public function testRegisterWiresExpectedServicesAndInitializesConfigHandler(): void
    {
        $contextConfig = new ContextConfig();

        $configSettings = $this->createMock(ConfigSettings::class);
        $configSettings->expects($this->once())->method('getHandler');

        $view = $this->createMock(View::class);

        $container = $this->createMock(Container::class);

        $container->expects($this->exactly(5))
            ->method('singleton')
            ->with(
                $this->logicalOr(
                    $this->equalTo(\Framework\Config\SetupConfig::class),
                    $this->equalTo(ConfigSettings::class),
                    $this->equalTo(\Framework\Config\ViewConfig::class),
                    $this->equalTo(\Framework\Core\ErrorConsole::class),
                    $this->equalTo(\Framework\Utils\Redirect::class)
                ),
                $this->isCallable()
            );

        $container->method('get')
            ->willReturnCallback(function (string $id) use ($configSettings, $view) {
                return match ($id) {
                    ConfigSettings::class => $configSettings,
                    View::class => $view,
                    default => throw new \RuntimeException('Unexpected get for ' . $id),
                };
            });

        $container->expects($this->once())
            ->method('set')
            ->with(View::class, $view);

        $contextConfig->register($container);
    }
}
