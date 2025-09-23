<?php

namespace Framework\I18n;

use Framework\Http\RequestHelper;
use Framework\Static\Constant;

class LangSelector
{
    /**
     * Determina el idioma, lo guarda en sesión y carga los archivos necesarios
     *
     * @param RequestHelper $request
     * @return array Parámetros para la vista
     */
    public static function getLanguageParams(RequestHelper $request): array
    {
        // Priorizar GET sobre SESSION y HTTP_ACCEPT_LANGUAGE
        $lang = $request->hasGet('lang')
            ? $request->get('lang')
            : ($_SESSION['lang'] ?? substr($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'en', 0, 2));

        // Validar contra idiomas soportados
        $supported = ['en', 'es'];
        if (!in_array($lang, $supported)) {
            $lang = 'en';
        }

        // Guardar en sesión
        // TODO: Use request wrapper for session
        $_SESSION['lang'] = $lang;

        // Cargar archivos de idioma
        if (class_exists(LangManager::class)) {
            LangManager::load($lang, Constant::LANG_PATH);
        }

        // Retornar parámetros para la vista
        return [
            'lang_selected_en' => $lang === 'en' ? 'selected' : '',
            'lang_selected_es' => $lang === 'es' ? 'selected' : '',
            'lang' => ['select_language' => __('select_language')],
        ];
    }
}
