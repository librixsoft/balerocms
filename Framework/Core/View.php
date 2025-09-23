<?php

namespace Framework\Core;

use Framework\Rendering\TemplateEngine;
use Framework\Static\Constant;

class View
{
    /**
     * Carpeta base de todas las vistas (sin "themes/")
     * Ej: /path/to/balerocms/resources/views/
     */
    private string $baseDir;

    private ConfigSettings $configSettings;
    private TemplateEngine $templateEngine;

    public function __construct(ConfigSettings $config, TemplateEngine $templateEngine)
    {
        $this->configSettings = $config;
        $this->templateEngine = $templateEngine;

        // Solo la carpeta base de vistas, sin temas ni default
        $this->baseDir = $this->normalizePath(Constant::VIEWS_PATH);

        // Cargar settings y setear baseDir en el template engine
        $this->configSettings->LoadSettings();
        $this->templateEngine->setBaseDir($this->baseDir);
    }

    /**
     * Normaliza un path para que termine con exactamente una sola barra.
     */
    private function normalizePath(string $path): string
    {
        return rtrim($path, '/') . '/';
    }

    /**
     * Renderiza una plantilla.
     *
     * @param string $templatePath Path relativo dentro de views o themes.
     * @param array $params Parámetros dinámicos a pasar a la plantilla.
     * @param bool $useTheme Si es true, buscará en themes/{theme}/, fallback a themes/default/.
     *                       Si es false, buscará directamente en la carpeta base de vistas.
     */
    public function render(string $templatePath, array $params = [], bool $useTheme = true): string
    {
        try {
            // Determinar path final según si se usa theme o no
            if ($useTheme) {
                $themeDir = $this->baseDir . "themes/" . $this->configSettings->theme . "/";
                $templateFullPath = $themeDir . ltrim($templatePath, '/');

                // Fallback a default si no existe en theme activo
                if (!file_exists($templateFullPath)) {
                    $fallbackPath = $this->baseDir . "themes/default/" . ltrim($templatePath, '/');
                    if (file_exists($fallbackPath)) {
                        $templateFullPath = $fallbackPath;
                    } else {
                        throw new \RuntimeException("Plantilla no encontrada en theme activo ni en default: $templateFullPath");
                    }
                }
            } else {
                // Render directo, **sin theme ni fallback**
                $templateFullPath = $this->baseDir . ltrim($templatePath, '/');

                if (!file_exists($templateFullPath)) {
                    throw new \RuntimeException("Plantilla no encontrada en vistas base: $templateFullPath");
                }
            }

            // Leer contenido de la plantilla
            $content = file_get_contents($templateFullPath);
            if ($content === false) {
                throw new \RuntimeException("No se pudo leer la plantilla: $templateFullPath");
            }

            // Merge de parámetros por defecto + dinámicos
            $params = $this->getDefaultParams($params);

            // Procesar plantilla con template engine
            $output = $this->templateEngine->processTemplate($content, $params);

            /**
             * Pasar el resultado final otra vez por parsePlaceholders
             *     Esto asegura que si hay placeholders dentro del contenido dinámico
             *     (ej: posts guardados con {year}), también se reemplazan.
             */
            return $this->parsePlaceholders($output, $params);

        } catch (\Throwable $e) {
            ErrorConsole::handleException($e);
            return '';
        }
    }

    /**
     * Devuelve los parámetros por defecto de todas las vistas
     * y mezcla con parámetros dinámicos si se pasan.
     */
    public function getDefaultParams(array $params = []): array
    {
        return array_merge([
            'title' => $this->configSettings->title,
            'url' => $this->configSettings->url,
            'keywords' => $this->configSettings->keywords,
            'description' => $this->configSettings->description,
            'basepath' => $this->configSettings->basepath,
            'year' => date('Y'),
            'footer' => $this->configSettings->footer,
            'theme' => $this->configSettings->theme,
        ], $params);
    }

    /**
     * Método global para procesar cualquier texto dinámico
     *     (ej: contenido de posts con placeholders como {year}, {title}, etc.)
     */
    public function parsePlaceholders(string $text, array $extraParams = []): string
    {
        $params = $this->getDefaultParams($extraParams);
        return $this->templateEngine->processTemplate($text, $params);
    }
}
