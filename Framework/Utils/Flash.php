<?php

namespace Framework\Utils;

/**
 * Sistema de mensajes flash que no persiste entre recargas.
 * Los mensajes se muestran una vez y se eliminan automáticamente.
 */
class Flash
{
    private string $flashKey = '_flash';

    public function __construct()
    {
        $this->ensureSessionStarted();
    }

    private function ensureSessionStarted(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Guarda un mensaje flash (solo durará hasta que se lea o se recargue la página)
     */
    public function set(string $key, mixed $value): void
    {
        $_SESSION[$this->flashKey][$key] = $value;
    }

    /**
     * Obtiene un mensaje flash y lo elimina inmediatamente después.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        if (!isset($_SESSION[$this->flashKey][$key])) {
            return $default;
        }

        $value = $_SESSION[$this->flashKey][$key];
        unset($_SESSION[$this->flashKey][$key]);

        // Si ya no quedan mensajes, limpiar todo el grupo
        if (empty($_SESSION[$this->flashKey])) {
            unset($_SESSION[$this->flashKey]);
        }

        return $value;
    }

    /**
     * Obtiene todos los mensajes flash y los elimina.
     */
    public function all(): array
    {
        $messages = $_SESSION[$this->flashKey] ?? [];
        unset($_SESSION[$this->flashKey]);
        return $messages;
    }

    /**
     * Verifica si existe un mensaje flash
     */
    public function has(string $key): bool
    {
        return isset($_SESSION[$this->flashKey][$key]);
    }

    /**
     * Limpia todos los mensajes flash
     */
    public function clear(): void
    {
        unset($_SESSION[$this->flashKey]);
    }

    public function delete(string $key): void
    {
        if (isset($_SESSION['_flash'][$key])) {
            unset($_SESSION['_flash'][$key]);
        }
    }

}
