<?php

namespace Framework\I18n;

use Framework\Http\RequestHelper;
use Framework\Static\Constant;

class LangSelector
{
    private LangManager $langManager;
    private RequestHelper $request;

    public function __construct(LangManager $langManager, RequestHelper $request)
    {
        $this->langManager = $langManager;
        $this->request = $request;
    }

    public function getLanguageParams(): array
    {
        $lang = $this->request->hasGet('lang')
            ? $this->request->get('lang')
            : ($_SESSION['lang'] ?? substr($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'en', 0, 2));

        $supported = ['en', 'es'];
        if (!in_array($lang, $supported)) {
            $lang = 'en';
        }

        $_SESSION['lang'] = $lang;

        $this->langManager->load($lang, Constant::LANG_PATH);

        // Genera array plano 'archivo.clave' => 'Valor'
        $placeholders = [];
        foreach ($this->langManager->translations as $file => $translations) {
            foreach ($translations as $key => $value) {
                $placeholders["$file.$key"] = $value;
            }
        }

        return $placeholders;
    }
}
