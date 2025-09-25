<?php

namespace Framework\Rendering;

use Framework\I18n\LangManager;

class ProcessorKeyPath
{
    private LangManager $langManager;

    public function __construct(LangManager $langManager)
    {
        $this->langManager = $langManager;
    }

    public function process(string $content, array $flatParams = []): string
    {
        return preg_replace_callback(
            '/\{([a-zA-Z0-9_]+)\.([a-zA-Z0-9_]+)\}/',
            function ($matches) use ($flatParams) {
                $fullKey = $matches[1] . '.' . $matches[2];

                if (array_key_exists($fullKey, $flatParams)) {
                    return $flatParams[$fullKey];
                }

                return $this->langManager->get($fullKey, $matches[0]);
            },
            $content
        );
    }
}
