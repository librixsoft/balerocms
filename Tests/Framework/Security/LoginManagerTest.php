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
use RuntimeException;

final class LoginManagerTest extends TestCase
{
    // ─── helpers ────────────────────────────────────────────────────────────────

    private function makeConfig(string $user = 'admin', string $plainPass = 'secret'): ConfigSettings
    {
        $hash = (new Hash())->genpwd($plainPass);
        $tmp  = tempnam(sys_get_temp_dir(), 'cfg');
        file_put_contents($tmp, json_encode([
            'config' => [
                'database' => ['dbhost' => '', 'dbuser' => '', 'dbpass' => '', 'dbname' => ''],
                'admin'    => [
                    'username'  => $user,
                    'passwd'    => $hash,
                    'email'     => '',
                    'firstname' => '',
                    'lastname'  => '',
                ],
                'system' => ['installed' => true, 'debug' => true],
                'site'   => [
                    'language'    => 'en', 'title' => '', 'description' => '', 'url' => '',
                    'keywords'    => '', 'basepath' => '/', 'theme' => 'default',
                    'footer'      => '', 'multilang' => false, 'editor' => '',
                ],
            ],
        ], JSON_THROW_ON_ERROR));

        $config = new ConfigSettings(new SetupConfig($tmp));
        $config->getHandler();
        return $config;
    }

    /** Construye un RequestHelper mock con counter=0, sin POST y sin cookie. */
    private function requestDefault(): RequestHelper
    {
        $request = $this->createMock(RequestHelper::class);
        $request->method('cookie')->willReturnMap([
            ['counter', 0, 0],
            ['admin_god_balero', null, null],
        ]);
        $request->method('hasPost')->with('login')->willReturn(false);
        return $request;
    }

    // ─── tests ───────────────────────────────────────────────────────────────────

    /**
     * Sin POST y sin cookie → false, mensaje vacío.
     */
    public function testHandleLoginReturnsFalseWhenNoPostAndNoCookie(): void
    {
        $manager = new LoginManager(new Security(), $this->makeConfig(), $this->requestDefault(), new Hash());

        $this->assertFalse($manager->handleLogin());
        $this->assertSame('', $manager->getMessage());
    }

    /**
     * isLoggedIn() es un alias de handleLogin().
     */
    public function testIsLoggedInDelegatesToHandleLogin(): void
    {
        $manager = new LoginManager(new Security(), $this->makeConfig(), $this->requestDefault(), new Hash());

        $this->assertFalse($manager->isLoggedIn());
    }

    /**
     * POST con credenciales incorrectas → false + mensaje 'login.message'.
     */
    public function testHandleLoginWithBadPostSetsMessage(): void
    {
        $request = $this->createMock(RequestHelper::class);
        $request->method('cookie')->willReturn(0);
        $request->method('hasPost')->with('login')->willReturn(true);
        $request->method('post')->willReturnMap([
            ['usr', '', 'admin'],
            ['pwd', '', 'wrong_password'],
        ]);

        $manager = new LoginManager(new Security(), $this->makeConfig(), $request, new Hash());

        $this->assertFalse($manager->isLoggedIn());
        $this->assertSame('login.message', $manager->getMessage());
    }

    /**
     * POST con credenciales correctas → true.
     */
    public function testHandleLoginWithCorrectCredentialsReturnsTrue(): void
    {
        $request = $this->createMock(RequestHelper::class);
        $request->method('cookie')->willReturn(0);
        $request->method('hasPost')->with('login')->willReturn(true);
        $request->method('post')->willReturnMap([
            ['usr', '', 'admin'],
            ['pwd', '', 'secret'],
        ]);

        $manager = new LoginManager(new Security(), $this->makeConfig(), $request, new Hash());

        $this->assertTrue($manager->handleLogin());
        // Mensaje debe quedar vacío cuando el login es exitoso
        $this->assertSame('', $manager->getMessage());
    }

    /**
     * Counter >= 5 invoca abortMaxAttempts().
     * Se usa subclase anónima para evitar que die() mate el proceso de PHPUnit.
     */
    public function testMaxAttemptsCallsAbort(): void
    {
        $request = $this->createMock(RequestHelper::class);
        $request->method('cookie')->willReturnMap([
            ['counter', 0, '5'],
            ['admin_god_balero', null, null],
        ]);
        $request->method('hasPost')->willReturn(false);

        // Subclase anónima que lanza excepción en lugar de die()
        $manager = new class(new Security(), $this->makeConfig(), $request, new Hash()) extends LoginManager {
            protected function abortMaxAttempts(): never
            {
                throw new RuntimeException('MAX_ATTEMPTS_REACHED');
            }
        };

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('MAX_ATTEMPTS_REACHED');

        $manager->handleLogin();
    }

    /**
     * Cookie válida (base64 de "admin:hash") con usuario y pass que coinciden → true.
     */
    public function testHandleLoginWithValidCookieReturnsTrue(): void
    {
        $config  = $this->makeConfig('admin', 'secret');
        $cookieValue = base64_encode('admin:' . $config->pass);

        $request = $this->createMock(RequestHelper::class);
        $request->method('cookie')->willReturnMap([
            ['counter', 0, 0],
            ['admin_god_balero', null, $cookieValue],
        ]);
        $request->method('hasPost')->with('login')->willReturn(false);

        $manager = new LoginManager(new Security(), $config, $request, new Hash());

        $this->assertTrue($manager->handleLogin());
        $this->assertSame('', $manager->getMessage());
    }

    /**
     * Cookie existente pero usuario o contraseña no coinciden → false + 'Hash Error'.
     */
    public function testHandleLoginWithMismatchedCookieReturnsFalse(): void
    {
        $config = $this->makeConfig('admin', 'secret');

        // Construimos un valor base64 con usuario/pass DISTINTOS al de la config
        $cookieValue = base64_encode('hacker:wronghash');

        $request = $this->createMock(RequestHelper::class);
        $request->method('cookie')->willReturnMap([
            ['counter', 0, 0],
            ['admin_god_balero', null, $cookieValue],
        ]);
        $request->method('hasPost')->with('login')->willReturn(false);

        $manager = new LoginManager(new Security(), $config, $request, new Hash());

        $this->assertFalse($manager->handleLogin());
        $this->assertSame('Hash Error', $manager->getMessage());
    }

    /**
     * Cookie con base64 inválido (no decodificable) → false + 'Hash Error'.
     * base64_decode con strict=true devuelve false si el string no es base64 puro.
     */
    public function testHandleLoginWithInvalidBase64CookieReturnsFalse(): void
    {
        // Un valor que NO es base64 válido en modo estricto
        $invalidBase64 = '!!!not-valid-base64===';

        $request = $this->createMock(RequestHelper::class);
        $request->method('cookie')->willReturnMap([
            ['counter', 0, 0],
            ['admin_god_balero', null, $invalidBase64],
        ]);
        $request->method('hasPost')->with('login')->willReturn(false);

        $manager = new LoginManager(new Security(), $this->makeConfig(), $request, new Hash());

        $this->assertFalse($manager->handleLogin());
        $this->assertSame('Hash Error', $manager->getMessage());
    }

    /**
     * Cookie con base64 válido pero SIN el separador ':' → false + 'Hash Error'.
     */
    public function testHandleLoginWithCookieWithoutSeparatorReturnsFalse(): void
    {
        // base64_encode de un string sin el carácter ':'
        $noColonBase64 = base64_encode('adminNOCOLON');

        $request = $this->createMock(RequestHelper::class);
        $request->method('cookie')->willReturnMap([
            ['counter', 0, 0],
            ['admin_god_balero', null, $noColonBase64],
        ]);
        $request->method('hasPost')->with('login')->willReturn(false);

        $manager = new LoginManager(new Security(), $this->makeConfig(), $request, new Hash());

        $this->assertFalse($manager->handleLogin());
        $this->assertSame('Hash Error', $manager->getMessage());
    }

    /**
     * logout() limpia la cookie 'admin_god_balero'.
     * Verificamos que no lanza excepción y que getMessage() sigue siendo ''.
     */
    public function testLogoutClearsCookieWithoutException(): void
    {
        $manager = new LoginManager(
            new Security(),
            $this->makeConfig(),
            $this->requestDefault(),
            new Hash()
        );

        // No debe lanzar excepción
        $manager->logout();

        // El estado del manager no cambia tras logout
        $this->assertSame('', $manager->getMessage());
    }

    /**
     * getMessage() devuelve '' antes de cualquier intento de login.
     */
    public function testGetMessageReturnsEmptyStringInitially(): void
    {
        $manager = new LoginManager(
            new Security(),
            $this->makeConfig(),
            $this->requestDefault(),
            new Hash()
        );

        $this->assertSame('', $manager->getMessage());
    }
}
