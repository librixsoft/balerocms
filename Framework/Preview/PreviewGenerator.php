<?php

namespace Framework\Preview;

class PreviewGenerator
{
    public function generatePreviewUrl(array $params): string
    {
        $cacheBuster = date('YmdHis');

        // Buscamos la URL base. Si 'url' en View.php ya es dinámica,
        // lo mejor es extraer el dominio o usar una variable que sea siempre la raíz.
        // Usaremos 'basepath' si contiene la URL completa, si no, calculamos la raíz de 'url'.

        $rawUrl = $params['url'] ?? '';
        $parsed = parse_url($rawUrl);
        $baseUrl = $parsed['scheme'] . '://' . $parsed['host'];

        // 1. Imagen explícita
        if (!empty($params['og_image'])) {
            if (strpos($params['og_image'], 'http') === 0) {
                return $params['og_image'] . (strpos($params['og_image'], '?') === false ? '?' : '&') . "v=" . $cacheBuster;
            }
            return $baseUrl . '/' . ltrim($params['og_image'], '/') . "?v=" . $cacheBuster;
        }

        // 2. Determinar Slug
        $slug = null;
        if (isset($params['page'])) {
            if (is_array($params['page']) && !empty($params['page']['static_url'])) {
                $slug = $params['page']['static_url'];
            } elseif (is_object($params['page']) && !empty($params['page']->static_url)) {
                $slug = $params['page']->static_url;
            }
        }

        // 3. Retornar URL ABSOLUTA
        if ($slug) {
            $encodedSlug = urlencode($slug);
            return "{$baseUrl}/page/og/{$encodedSlug}?v={$cacheBuster}";
        }

        $title = $params['title'] ?? 'Preview';
        $encodedTitle = urlencode($title);
        return "{$baseUrl}/page/og/generic?title={$encodedTitle}&v={$cacheBuster}";
    }
}