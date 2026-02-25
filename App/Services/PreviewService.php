<?php

namespace App\Services;

use Framework\Preview\PreviewGenerator;
use Framework\Attributes\Inject;

class PreviewService
{
    #[Inject]
    private PreviewGenerator $generator;

    /**
     * El controlador llama aquí para mostrar la imagen física.
     */
    public function serveOgImage(mixed $page): void
    {
        $title = null;
        if (!empty($page)) {
            $title = is_object($page) ? ($page->virtual_title ?? null) : ($page['virtual_title'] ?? null);
        }

        $this->generator->render($title);
    }
    
}