<?php

namespace Framework\Preview;

class PreviewGenerator
{
    /**
     * Generate a dynamic preview image URL based on current page parameters.
     *
     * @param array $params View parameters
     * @return string Image URL
     */
    public function generatePreviewUrl(array $params): string
    {
        // Generamos un identificador único basado en tiempo para romper el cache (v=20240520)
        // Si prefieres que cambie cada segundo usa time(), pero date('YmdHi') es suficiente.
        $cacheBuster = date('YmdHis');

        // 1. If an explicit image is provided in the params, use it
        if (!empty($params['og_image'])) {
            $baseUrl = $params['url'] ?? '';

            // If it's an absolute URL, return it directly (añadiendo buster)
            if (strpos($params['og_image'], 'http') === 0) {
                $separator = (strpos($params['og_image'], '?') === false) ? '?' : '&';
                return $params['og_image'] . $separator . "v=" . $cacheBuster;
            }

            $fullPath = rtrim($baseUrl, '/') . '/' . ltrim($params['og_image'], '/');
            $separator = (strpos($fullPath, '?') === false) ? '?' : '&';
            return $fullPath . $separator . "v=" . $cacheBuster;
        }

        // 2. Identify the section/page title we are visiting
        $title = $params['title'] ?? 'Preview';

        if (isset($params['page'])) {
            if (is_array($params['page']) && !empty($params['page']['virtual_title'])) {
                $title = $params['page']['virtual_title'];
            } elseif (is_object($params['page']) && !empty($params['page']->virtual_title)) {
                $title = $params['page']->virtual_title;
            }
        } elseif (!empty($params['mod_name'])) {
            $title = $params['mod_name'];
        }

        // 3. Fallback: Generate a dynamic image URL internally
        $url = rtrim($params['url'] ?? '.', '/');

        // Check if we are viewing a page
        $slug = null;
        if (isset($params['page'])) {
            if (is_array($params['page']) && !empty($params['page']['static_url'])) {
                $slug = $params['page']['static_url'];
            } elseif (is_object($params['page']) && !empty($params['page']->static_url)) {
                $slug = $params['page']->static_url;
            }
        }

        // Si hay slug, retornamos la ruta absoluta con el parámetro de refresco
        if ($slug) {
            $encodedSlug = urlencode($slug);
            return "{$url}/page/og/{$encodedSlug}?v={$cacheBuster}";
        }

        // Fallback genérico con título y buster
        $encodedTitle = urlencode($title);
        return "{$url}/page/og/generic?title={$encodedTitle}&v={$cacheBuster}";
    }
}