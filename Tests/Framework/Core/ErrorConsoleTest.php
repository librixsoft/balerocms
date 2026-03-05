<?php

declare(strict_types=1);

namespace Tests\Framework\Core;

use Framework\Config\SetupConfig;
use Framework\Core\ConfigSettings;
use Framework\Core\ErrorConsole;
use Framework\DI\Container;
use PHPUnit\Framework\TestCase;

final class ErrorConsoleTest extends TestCase
{
    private function makeConfig(string $debug): ConfigSettings
    {
        $tmp = tempnam(sys_get_temp_dir(), 'cfg');
        file_put_contents($tmp, json_encode([
            'config' => [
                'database' => ['dbhost' => 'h', 'dbuser' => 'u', 'dbpass' => 'p', 'dbname' => 'd'],
                'admin' => ['username' => 'u', 'passwd' => 'p', 'email' => '', 'firstname' => '', 'lastname' => ''],
                'system' => ['installed' => 'yes', 'debug' => $debug],
                'site' => ['language' => 'en', 'title' => '', 'description' => '', 'url' => '', 'keywords' => '', 'basepath' => '/', 'theme' => 'default', 'footer' => '', 'multilang' => false, 'editor' => ''],
            ],
        ], JSON_THROW_ON_ERROR));

        $cfg = new ConfigSettings(new SetupConfig($tmp));
        $cfg->getHandler();
        return $cfg;
    }

    public function testErrorConsoleClassAndProductionFlagLogic(): void
    {
        $ec = new ErrorConsole($this->makeConfig('prod'), new Container());

        $r = new \ReflectionClass($ec);
        $this->assertTrue($r->hasMethod('register'));
        $this->assertTrue($r->hasMethod('handleException'));

        $m = $r->getMethod('isProduction');
        $m->setAccessible(true);
        $this->assertTrue($m->invoke($ec));

        $ec2 = new ErrorConsole($this->makeConfig('dev'), new Container());
        $this->assertFalse($m->invoke($ec2));
    }
}
