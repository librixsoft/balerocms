<?php

/**
 * Balero CMS
 * @author Anibal Gomez <balerocms@gmail.com>
 * @license GNU General Public License
 */

namespace Framework\Core;

use Framework\Config\ViewConfig;
use Framework\Core\ConfigSettings;
use Framework\Rendering\TemplateEngine;
use Framework\I18n\LangManager;
use Framework\Exceptions\ViewException;

class View
{
    private string $viewsPath;
    private string $baseDir;
    private array $allowedExtensions;

    private ConfigSettings $configSettings;
    private TemplateEngine $templateEngine;
    private LangManager $langManager;
    private ViewConfig $viewConfig;

    /**
     * View constructor.
     *
     * @param ConfigSettings $configSettings
     * @param TemplateEngine $templateEngine
     * @param LangManager $langManager
     * @param ViewConfig $viewConfig
     */
    public function __construct(
        ConfigSettings $configSettings,
        TemplateEngine $templateEngine,
        LangManager $langManager,
        ViewConfig $viewConfig
    ) {
        $this->configSettings = $configSettings;
        $this->templateEngine = $templateEngine;
        $this->langManager = $langManager;
        $this->viewConfig = $viewConfig;

        $this->viewsPath = $viewConfig->viewsPath;
        $this->allowedExtensions = $viewConfig->allowedExtensions;
        $this->baseDir = $this->normalizePath($this->viewsPath);

        $this->templateEngine->setBaseDir($this->baseDir);
    }

    private function normalizePath(string $path): string
    {
        return rtrim($path, '/') . '/';
    }

    private function initializeDependencies(): void
    {
        if (empty($this->baseDir)) {
            $this->baseDir = $this->normalizePath($this->viewsPath);
        }

        $this->configSettings->LoadSettings();
        $this->templateEngine->setBaseDir($this->baseDir);
    }

    private function validateTemplateFile(string $filePath): void
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if (!in_array($extension, $this->allowedExtensions, true)) {
            throw new ViewException(
                "Invalid template file type. Only " . implode(', ', $this->allowedExtensions) .
                " files are supported. Got: .$extension"
            );
        }
    }

    public function render(string $templatePath, array $params = [], bool $useTheme = true): string
    {
        try {
            $this->initializeDependencies();

            // --- Idioma ---
            $sessionLang = $_SESSION['lang'] ?? 'en';
            $this->langManager->setCurrentLang($sessionLang);
            $this->langManager->load($sessionLang, $this->viewConfig->langBasePath);

            $templateFullPath = $this->resolveTemplatePath($templatePath, $useTheme);

            $this->validateTemplateFile($templateFullPath);

            $content = file_get_contents($templateFullPath);
            if ($content === false) {
                throw new ViewException("Failed to read template file: $templateFullPath");
            }

            $params = $this->getDefaultParams($params);
            $output = $this->templateEngine->processTemplate($content, $params);

            return $this->parsePlaceholders($output, $params);

        } catch (\Throwable $e) {
            throw new ViewException("Error rendering template: " . $e->getMessage(), 0, $e);
        }
    }

    private function resolveTemplatePath(string $templatePath, bool $useTheme): string
    {
        $currentExtension = pathinfo($templatePath, PATHINFO_EXTENSION);

        if (empty($currentExtension)) {
            $templatePath .= '.' . $this->allowedExtensions[0];
        }

        if ($useTheme) {
            $themeDir = $this->baseDir . "themes/" . $this->configSettings->theme . "/";
            $templateFullPath = $themeDir . ltrim($templatePath, '/');

            if (!file_exists($templateFullPath)) {
                $fallbackPath = $this->baseDir . "themes/default/" . ltrim($templatePath, '/');
                if (file_exists($fallbackPath)) {
                    $templateFullPath = $fallbackPath;
                } else {
                    throw new ViewException("Template not found in active theme nor default: $templateFullPath");
                }
            }
        } else {
            $templateFullPath = $this->baseDir . ltrim($templatePath, '/');
            if (!file_exists($templateFullPath)) {
                throw new ViewException("Template not found in base views: $templateFullPath");
            }
        }

        return $templateFullPath;
    }

    private function getDefaultParams(array $params = []): array
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

    private function parsePlaceholders(string $text, array $extraParams = []): string
    {
        $params = $this->getDefaultParams($extraParams);

        $text = $this->templateEngine->processTemplate($text, $params);

        return preg_replace_callback(
            '/\{([a-zA-Z0-9_]+)\.([a-zA-Z0-9_]+)\}/',
            function ($matches) use ($params) {
                $fullKey = $matches[1] . '.' . $matches[2];

                if (isset($params[$fullKey])) {
                    return $params[$fullKey];
                }

                return $this->langManager->get($fullKey, $matches[0]);
            },
            $text
        );
    }
}
