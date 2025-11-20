<?php

/**
 * Balero CMS
 * ViewConfig - Configuración para la clase View
 *
 * @author Anibal Gomez
 * @license GNU General Public License
 */

namespace Framework\Config;

class ViewConfig
{
    public string $viewsPath;
    public string $langBasePath;
    public array $allowedExtensions;

    public function __construct(
        string $viewsPath,
        string $langBasePath,
        array $allowedExtensions = ['html']
    ) {
        $this->viewsPath = $viewsPath;
        $this->langBasePath = $langBasePath;
        $this->allowedExtensions = $allowedExtensions;
    }
}