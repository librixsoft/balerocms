<?php

/**
 * Balero CMS
 * @author Anibal Gomez <balerocms@gmail.com>
 * @license GNU General Public License
 */

namespace Framework\Security;

class Security
{

    private string $var;

    /**
     * Anti-XSS Method for tech rich editor
     * @param $val Input String
     * @param int $rich Is Rich Text?
     * @return Proccesed String
     */
    public function antiXSS($val)
    {
        if (!is_string($val)) {
            return $val; // Solo procesar strings
        }

        // Eliminar caracteres de control (ASCII 0-31 y 127)
        $val = preg_replace('/[\x00-\x1F\x7F]/u', '', $val);

        // Eliminar etiquetas peligrosas
        $val = preg_replace(
            '#<(script|iframe|object|embed|link|meta|base|form)(.*?)>(.*?)</\1>#is',
            '',
            $val
        );

        // Eliminar atributos peligrosos (ej: onclick, onerror, onload, etc.)
        $val = preg_replace('/\s+on\w+="[^"]*"/i', '', $val);
        $val = preg_replace("/\s+on\w+='[^']*'/i", '', $val);

        // 🔹 Bloquear javascript: en href o src
        $val = preg_replace('/(href|src)\s*=\s*["\']\s*javascript:[^"\']*["\']/i', '$1="#"', $val);

        return $val;
    }


    public function sanitize(string $val): string
    {
        return htmlspecialchars($val);
    }

    /**
     * Security Fix
     * @param $var
     * @return int
     */
    public function toInt($var)
    {
        $this->var = $var;
        $this->var = preg_replace('/[^0-9,.]+/i', '', $this->var);
        $this->var = htmlentities($this->var);
        return (int)$this->var;
    }

    public function __toString()
    {
        return (string)$this->var;
    }

}
