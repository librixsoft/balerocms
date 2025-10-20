<?php

namespace Framework\I18n;

class Translator
{
    private LangManager $langManager;
    private bool $loaded = false;

    public function __construct(LangManager $langManager)
    {
        $this->langManager = $langManager;
    }

    /**
     * Asegura que las traducciones estén cargadas
     */
    private function ensureLoaded(): void
    {
        if (!$this->loaded) {
            $lang = $_SESSION['lang'] ?? 'en';
            $this->langManager->load($lang, LOCAL_DIR . '/resources/lang');
            $this->loaded = true;
        }
    }

    /**
     * Traduce una clave de traducción
     * @param string $key Clave en formato 'archivo.clave'
     * @param string $default Valor por defecto si no existe
     * @return string
     */
    public function getText(string $key, string $default = ''): string
    {
        $this->ensureLoaded();
        return $this->langManager->get($key, $default ?: $key);
    }

    /**
     * Alias corto para trans()
     */
    public function t(string $key, string $default = ''): string
    {
        return $this->getText($key, $default);
    }

    /**
     * Traduce con reemplazo de parámetros
     * Ejemplo: trans('welcome.message', ['name' => 'John'])
     * donde welcome.message = "Hello {name}"
     */
    public function transParams(string $key, array $params = [], string $default = ''): string
    {
        $this->ensureLoaded();
        $text = $this->getText($key, $default);

        foreach ($params as $k => $v) {
            $text = str_replace("{{$k}}", $v, $text);
        }

        return $text;
    }

    /**
     * Obtiene el idioma actual
     */
    public function getCurrentLang(): string
    {
        $this->ensureLoaded();
        return $this->langManager->current();
    }

    /**
     * Cambia el idioma
     */
    public function setLang(string $lang): void
    {
        $this->langManager->setCurrentLang($lang);
        $_SESSION['lang'] = $lang;
        // Recargar las traducciones con el nuevo idioma
        $this->langManager->load($lang, LOCAL_DIR . '/resources/lang');
        $this->loaded = true;
    }
}