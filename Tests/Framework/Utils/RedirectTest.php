<?php

declare(strict_types=1);

namespace Tests\Framework\Utils;

use Framework\Config\SetupConfig;
use Framework\Core\ConfigSettings;
use Framework\Utils\Redirect;
use PHPUnit\Framework\TestCase;

final class RedirectTest extends TestCase
{
    public function testToBuildsNormalizedLocationHeaderWithoutExitWhenForceExitFalse(): void
    {
        if (!function_exists('xdebug_get_headers')) {
            $this->markTestSkipped('xdebug_get_headers is required for header assertion in CLI.');
        }

        $tmp = tempnam(sys_get_temp_dir(), 'cfg');
        file_put_contents($tmp, json_encode([
            'config' => [
                'database' => ['dbhost' => 'h', 'dbuser' => 'u', 'dbpass' => 'p', 'dbname' => 'd'],
                'admin' => ['username' => 'u', 'passwd' => 'p', 'email' => '', 'firstname' => '', 'lastname' => ''],
                'system' => ['installed' => 'yes', 'debug' => true],
                'site' => ['language' => 'en', 'title' => '', 'description' => '', 'url' => '', 'keywords' => '', 'basepath' => '/cms/', 'theme' => 'default', 'footer' => '', 'multilang' => false, 'editor' => ''],
            ],
        ], JSON_THROW_ON_ERROR));

        $cfg = new ConfigSettings(new SetupConfig($tmp));
        $cfg->getHandler();

        header_remove();

        (new Redirect($cfg))->to('/admin//pages', false);

        $headers = xdebug_get_headers();
        $this->assertContains('Location: /cms/admin/pages', $headers);
    }

    public function testToWorksWhenBasepathIsRoot(): void
    {
        if (!function_exists('xdebug_get_headers')) {
            $this->markTestSkipped('xdebug_get_headers is required for header assertion in CLI.');
        }

        $tmp = tempnam(sys_get_temp_dir(), 'cfg');
        file_put_contents($tmp, json_encode([
            'config' => [
                'database' => ['dbhost' => 'h', 'dbuser' => 'u', 'dbpass' => 'p', 'dbname' => 'd'],
                'admin' => ['username' => 'u', 'passwd' => 'p', 'email' => '', 'firstname' => '', 'lastname' => ''],
                'system' => ['installed' => 'yes', 'debug' => true],
                'site' => ['language' => 'en', 'title' => '', 'description' => '', 'url' => '', 'keywords' => '', 'basepath' => '/', 'theme' => 'default', 'footer' => '', 'multilang' => false, 'editor' => ''],
            ],
        ], JSON_THROW_ON_ERROR));

        $cfg = new ConfigSettings(new SetupConfig($tmp));
        $cfg->getHandler();

        header_remove();

        (new Redirect($cfg))->to('login', false);

        $headers = xdebug_get_headers();
        $this->assertContains('Location: /login', $headers);
    }
}
