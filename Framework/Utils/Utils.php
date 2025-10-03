<?php

namespace Framework\Utils;

class Utils
{

    /**
     * Genera un slug amigable para URLs basado en el título (opcional)
     */
    public function slugify(string $text): string
    {
        // Pasos básicos para crear un slug
        $text = preg_replace('~[^\pL\d]+~u', '-', $text); // Reemplaza espacios y no letras por guion
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text); // Transliterar caracteres
        $text = preg_replace('~[^-\w]+~', '', $text); // Eliminar caracteres no deseados
        $text = trim($text, '-');
        $text = preg_replace('~-+~', '-', $text);
        $text = strtolower($text);

        return $text ?: 'page';
    }

}