<?php

namespace Framework\Rendering;

/**
 * Processor para incluir archivos HTML en plantillas usando comentarios tipo <%-- @include "ruta.html" -->
 */
class ProcessorIncludes
{
    /**
     * Procesa inclusiones tipo <%-- @include "ruta/al/archivo.html" -->
     *
     * @param string $content El contenido de la plantilla.
     * @param string $baseDir Directorio base donde buscar los archivos incluidos.
     *
     * @return string Contenido con los includes procesados.
     */
    public function process(string $content, string $baseDir): string
    {
        if (!$baseDir) {
            return '<%-- INCLUDE ERROR: baseDir no definido -->';
        }

        return preg_replace_callback(
            '/<%--\s*@include\s+"([^"]+)"\s*-->/',
            function ($matches) use ($baseDir) {
                $includePath = $matches[1];
                $fullPath = realpath($baseDir . $includePath);

                if (!$fullPath || !file_exists($fullPath)) {
                    return "<%-- INCLUDE ERROR: Archivo no encontrado $includePath -->";
                }

                return file_get_contents($fullPath);
            },
            $content
        );
    }
}
