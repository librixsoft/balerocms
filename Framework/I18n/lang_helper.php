<?php

/**
 * Balero CMS
 * @author Anibal Gomez <balerocms@gmail.com>
 * @license GNU General Public License
 */

use Framework\I18n\LangManager;

function __(string $key, string $default = ''): string
{
    return LangManager::get($key, $default);
}
