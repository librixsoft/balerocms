<?php

namespace Framework\Utils;

class Flash
{
    private const FLASH_KEY = '_flash';

    public function __construct()
    {
        $this->ensureSessionStarted();
    }

    /**
     * Guarda un valor en la sesión flash
     */
    public function set(string $key, mixed $value): void
    {
        $_SESSION[self::FLASH_KEY][$key] = $value;
    }

    /**
     * Asegura que la sesión esté iniciada
     */
    private function ensureSessionStarted(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Obtiene un valor flash (y lo elimina)
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[self::FLASH_KEY][$key] ?? $default;
    }

    /**
     * Verifica si existe un valor flash
     */
    public function has(string $key): bool
    {
        return isset($_SESSION[self::FLASH_KEY][$key]);
    }

    /**
     * Limpia todos los valores flash
     */
    public function clear(): void
    {
        unset($_SESSION[self::FLASH_KEY]);
    }

    /**
     * Elimina un valor flash específico usando clave aplanada y clave simple
     * Ej: 'errors.username' o 'campo'
     */
    public function delete(string $flatKey): void
    {
        if (!isset($_SESSION[self::FLASH_KEY])) {
            return;
        }

        $keys = explode('.', $flatKey);
        $ref =& $_SESSION[self::FLASH_KEY];

        // Clave simple
        if (count($keys) === 1 && isset($ref[$flatKey])) {
            unset($ref[$flatKey]);
            return;
        }

        // Arrays anidados
        foreach ($keys as $k) {
            if (isset($ref[$k])) {
                if ($k === end($keys)) {
                    unset($ref[$k]);
                } else {
                    $ref =& $ref[$k];
                }
            } else {
                break;
            }
        }
    }
}
