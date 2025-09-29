<?php

/**
 * Balero CMS
 * @author Anibal Gomez <balerocms@gmail.com>
 * @license GNU General Public License
 */

namespace Framework\Security;

use Framework\Core\ConfigSettings;
use Framework\Http\RequestHelper;
use Framework\Static\Hash;

class LoginManager
{
    private Security $security;
    private ConfigSettings $config;
    private RequestHelper $request;
    private string $message = '';

    public function __construct(
        Security $security,
        ConfigSettings $config,
        RequestHelper $request
    )
    {
        $this->security = $security;
        $this->config = $config;
        $this->request = $request;
    }

    /**
     * Devuelve true si ya hay login válido (cookie o post)
     */
    public function isLoggedIn(): bool
    {
        return $this->handleLogin();
    }

    /**
     * Intenta autenticar usuario, devuelve true si login válido
     */
    public function handleLogin(): bool
    {
        $counter = $this->security->toInt($this->request->cookie('counter', 0));
        if ($counter >= 5) {
            die("MAX LOGIN ATTEMPS, WAIT 5 MINUTES!");
        }

        // Intento de login por formulario
        if ($this->request->hasPost('login')) {
            $usr = $this->request->post('usr', '');
            $pwd = $this->request->post('pwd', '');

            $verify = Hash::verify_hash($pwd, $this->config->pass);

            if ($usr === $this->config->username && $verify) {
                $value = base64_encode($usr . ':' . $this->config->pass);
                $this->setCookie('admin_god_balero', $value, 86400); // 1 día
                return true;
            }

            // Falló el login → incrementar contador
            $this->setCookie('counter', $counter + 1, 300);
            $this->message = __('login.message');
            return false;
        }

        // Validar cookie existente
        if ($cookie = $this->request->cookie('admin_god_balero')) {
            $decoded = base64_decode($cookie, true);
            if ($decoded !== false && str_contains($decoded, ':')) {
                [$cookieUsr, $cookiePwd] = explode(':', $decoded, 2);

                if ($cookieUsr === $this->config->username && $cookiePwd === $this->config->pass) {
                    return true;
                }
            }

            // Si llega aquí, la cookie no coincide
            $this->clearCookie('admin_god_balero');
            $this->message = 'Hash Error';
            return false;
        }

        return false;
    }

    private function setCookie(string $name, string $value, int $lifetime): void
    {
        setcookie($name, $value, time() + $lifetime, '/', '', false, true);
    }

    private function clearCookie(string $name): void
    {
        setcookie($name, '', time() - 3600, '/', '', false, true);
    }

    /**
     * Devuelve mensaje de error
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * Cierra sesión
     */
    public function logout(): void
    {
        $this->clearCookie('admin_god_balero');
    }
}
