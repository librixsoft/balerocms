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

class View
{
    private string $viewsPath = LOCAL_DIR . '/resources/views';
    private string $baseDir;

    private ConfigSettings $configSettings;
    private TemplateEngine $templateEngine;
    private LangManager $langManager;

    public function __construct(ConfigSettings $config, TemplateEngine $templateEngine, LangManager $langManager)
    {
        $this->configSettings = $config;
        $this->templateEngine = $templateEngine;
        $this->langManager = $langManager;

        $this->baseDir = $this->normalizePath($this->getViewsPath());

        $this->configSettings->LoadSettings();
        $this->templateEngine->setBaseDir($this->baseDir);
    }

    private function normalizePath(string $path): string
    {
        return rtrim($path, '/') . '/';
    }

    public function render(string $templatePath, array $params = [], bool $useTheme = true): string
    {
        try {
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

    public function parsePlaceholders(string $text, array $extraParams = []): string
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
    }

    public function getBaseDir(): string
    {
        return $this->baseDir;
    }

    public function setBaseDir(string $baseDir): void
    {
        $this->baseDir = $baseDir;
    }
}
