<?php

/**
 * Balero CMS
 * @author Anibal Gomez <balerocms@gmail.com>
 * @license GNU General Public License
 */

namespace Framework\Utils;

use Framework\Core\ConfigSettings;

class Redirect
{
    private ConfigSettings $config;

    public function __construct(ConfigSettings $config)
    {
        $this->config = $config;
    }

    public function to(string $url, bool $forceExit = true): void
    {
        $basepath = rtrim($this->config->basepath, '/');
        $url = ltrim($url, '/');
        $normalizedUrl = preg_replace('#(?<!:)//+#', '/', $basepath . '/' . $url);

        header("Location: " . $normalizedUrl);

        if ($forceExit) {
            exit;
        }
    }
}
