<?php

/**
 * Balero CMS
 * SetupConfig - Configuración para la clase ConfigSettings
 *
 * @author Anibal Gomez
 * @license GNU General Public License
 */

namespace Framework\Config;

class SetupConfig
{
    public string $configPath;

    public function __construct(string $configPath)
    {
        $this->configPath = $configPath;
    }
}
