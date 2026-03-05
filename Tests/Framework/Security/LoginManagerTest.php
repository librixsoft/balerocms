<?php

declare(strict_types=1);

namespace Tests\Framework\Security;

use Framework\Config\SetupConfig;
use Framework\Core\ConfigSettings;
use Framework\Http\RequestHelper;
use Framework\Security\LoginManager;
use Framework\Security\Security;
use Framework\Utils\Hash;
use PHPUnit\Framework\TestCase;

final class LoginManagerTest extends TestCase
{
    private function makeConfig(string $user = 'admin', string $plainPass = 'secret'): ConfigSettings
    {
        $hash = (new Hash())->genpwd($plainPass);
        $tmp = tempnam(sys_get_temp_dir(), 'cfg');
        file_put_contents($tmp, json_encode([
            'config' => [
                'database' => ['dbhost' => '', 'dbuser' => '', 'dbpass' => '', 'dbname' => ''],
                'admin' => [
                    'username' => $user,
                    'passwd' => $hash,
                    'email' => '',
                    'firstname' => '',
                    'lastname' => '',
                ],
                'system' => ['installed' => true, 'debug' => true],
                'site' => [
                    'language' => 'en','title' => '','description' => '','url' => '',
                    'keywords' => '','basepath' => '/','theme' => 'default','footer' => '','multilang' => false,'editor' => ''
                ],
            ],
        ], JSON_THROW_ON_ERROR));

        $config = new ConfigSettings(new SetupConfig($tmp));
        $config->getHandler();
        return $config;
    }

    public function testHandleLoginReturnsFalseWhenNoPostAndNoCookie(): void
    {
        $request = $this->createMock(RequestHelper::class);
        $request->method('cookie')->willReturnMap([
            ['counter', 0, 0],
            ['admin_god_balero', null, null],
        ]);
        $request->method('hasPost')->with('login')->willReturn(false);

        $manager = new LoginManager(new Security(), $this->makeConfig(), $request, new Hash());

        $this->assertFalse($manager->handleLogin());
        $this->assertSame('', $manager->getMessage());
    }

    public function testHandleLoginWithBadPostSetsMessage(): void
    {
        $request = $this->createMock(RequestHelper::class);
        $request->method('cookie')->willReturn(0);
        $request->method('hasPost')->with('login')->willReturn(true);
        $request->method('post')->willReturnMap([
            ['usr', '', 'admin'],
            ['pwd', '', 'wrong'],
        ]);

        $manager = new LoginManager(new Security(), $this->makeConfig(), $request, new Hash());

        $this->assertFalse($manager->isLoggedIn());
        $this->assertSame('login.message', $manager->getMessage());
    }
}
