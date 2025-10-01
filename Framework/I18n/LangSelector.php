<?php

namespace Framework\I18n;

use Framework\Http\RequestHelper;

class LangSelector
{

    private string $langPath = LOCAL_DIR . '/resources/lang';

    private LangManager $langManager;
    private RequestHelper $requestHelper;

    public function __construct(LangManager $langManager, RequestHelper $requestHelper)
    {
        $this->langManager = $langManager;
        $this->requestHelper = $requestHelper;
    }

    public function getLanguageParams(): array
    {
        $lang = $this->requestHelper->hasGet('lang')
            ? $this->requestHelper->get('lang')
            : ($_SESSION['lang'] ?? substr($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'en', 0, 2));

        $supported = ['en', 'es'];
        if (!in_array($lang, $supported)) {
            $lang = 'en';
        }

        $_SESSION['lang'] = $lang;

        $this->langManager->load($lang, $this->getLangPAth());

        // Genera array plano 'archivo.clave' => 'Valor'
        $placeholders = [];
        foreach ($this->langManager->translations as $file => $translations) {
            foreach ($translations as $key => $value) {
                $placeholders["$file.$key"] = $value;
            }
        }

        return $placeholders;
    }

    /**
     * @return string
     */
    public function getLangPath(): string
    {
        return $this->langPath;
    }

    /**
     * @param string $langPath
     */
    public function setLangPath(string $langPath): void
    {
        $this->langPath = $langPath;
    }

}
