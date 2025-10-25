<?php

/**
 * Balero CMS
 * @author Anibal Gomez <balerocms@gmail.com>
 * @license GNU General Public License
 */

namespace Framework\Core;

use Framework\Rendering\TemplateEngine;
use Framework\I18n\LangManager;
use Framework\Exceptions\ViewException;
use Framework\Attributes\Inject;

class View
{
    private string $viewsPath = BASE_PATH . '/resources/views';
    private string $baseDir;

    #[Inject]
    private ConfigSettings $configSettings;

    #[Inject]
    private TemplateEngine $templateEngine;

    #[Inject]
    private LangManager $langManager;

    public function __construct()
    {
        // Constructor vacío para DI
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

    public function render(string $templatePath, array $params = [], bool $useTheme = true): string
    {
        try {
            $this->initializeDependencies();

            // --- Idioma ---
            $sessionLang = $_SESSION['lang'] ?? 'en';
            $this->langManager->setCurrentLang($sessionLang);
            $this->langManager->load($sessionLang, BASE_PATH . '/resources/lang');

            $templateFullPath = $this->resolveTemplatePath($templatePath, $useTheme);
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

    public function getViewsPath(): string
    {
        return $this->viewsPath;
    }

    public function setViewsPath(string $viewsPath): void
    {
        $this->viewsPath = $viewsPath;
        $this->baseDir = $this->normalizePath($viewsPath);
        $this->templateEngine->setBaseDir($this->baseDir);
    }

    public function getBaseDir(): string
    {
        return $this->baseDir;
    }

    public function setBaseDir(string $baseDir): void
    {
        $this->baseDir = $baseDir;
        $this->templateEngine->setBaseDir($baseDir);
    }
}
