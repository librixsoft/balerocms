<?php

namespace Framework\Rendering;

use Framework\I18n\LangManager;

/**
 * Processor para el reemplazo de claves en notación de punto.
 *
 * Maneja {lang.key}, {auth.login}, {module.key}, etc.
 * Primero busca en los parámetros planos y, si no encuentra valor,
 * recurre a LangManager para obtener traducciones o usa la clave literal como fallback.
 */
class ProcessorKeyPath
{
    /**
     * Procesa el contenido reemplazando las claves {xxx.yyy} por su valor correspondiente.
     *
     * @param string $content El contenido de la plantilla.
     * @param array $flatParams Parámetros planos con claves como "module.key" => "valor".
     *
     * @return string Contenido con claves reemplazadas.
     */
    public function process(string $content, array $flatParams = []): string
    {
        return preg_replace_callback(
            '/\{([a-zA-Z0-9_]+)\.([a-zA-Z0-9_]+)\}/',
            function ($matches) use ($flatParams) {
                $fullKey = $matches[1] . '.' . $matches[2];

                // Primero buscar en flatParams
                if (array_key_exists($fullKey, $flatParams)) {
                    return $flatParams[$fullKey];
                }

                // Si no está en flatParams -> buscar traducción en LangManager
                return LangManager::get($fullKey, $matches[0]);
            },
            $content
        );
    }
}
