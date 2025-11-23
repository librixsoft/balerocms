<?php

namespace Framework\Core;

use Throwable;

/**
 * Manejador de errores minimalista para errores tempranos del bootstrap.
 * Se usa ANTES de que el Container y servicios estén disponibles.
 */
class EarlyErrorConsole
{
    /**
     * Renderiza un error temprano sin dependencias externas
     */
    public function render(Throwable $e): void
    {
        if (ob_get_level()) {
            ob_clean();
        }

        $message = "Early Bootstrap Error: " . get_class($e);
        $detail = "Message: " . htmlspecialchars($e->getMessage());
        $location = "File: " . htmlspecialchars($e->getFile()) . " (Line: " . $e->getLine() . ")";

        $this->getHtmlTemplate($message, $detail, $location, $e);
        exit;
    }

    /**
     * Genera el HTML completo para la consola de errores tempranos
     */
    private function getHtmlTemplate(string $message, string $detail, string $location, Throwable $e): void
    {
        $traceHtml = $this->generateTraceHtml($e);
        $css = $this->getCss();

        echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Balero CMS Early Error</title>';
        echo '<style>' . $css . '</style>';
        echo '</head><body>';
        echo '<div class="console">';
        echo '<div class="console-header">';
        echo '<div class="console-icon">⚠</div>';
        echo '<div class="console-title">Balero CMS Early Bootstrap Error</div>';
        echo '</div>';
        echo '<div class="console-body">';
        echo '<div class="warning">⚠️ This error occurred before the full error handler could be initialized.</div>';
        echo '<h2>' . $message . '</h2>';
        echo '<div class="detail">' . $detail . '</div>';
        echo '<div class="location">' . $location . '</div>';
        echo $traceHtml;
        echo '</div></div></body></html>';
    }

    /**
     * Genera el HTML del stack trace
     */
    private function generateTraceHtml(Throwable $e): string
    {
        $html = '<div class="trace">';

        foreach ($e->getTrace() as $i => $trace) {
            $file = htmlspecialchars($trace['file'] ?? '[internal]');
            $line = $trace['line'] ?? '?';
            $func = htmlspecialchars($trace['function'] ?? '???');

            $html .= "<div class=\"trace-item\">";
            $html .= "#$i <code>{$func}()</code> in <code>{$file}</code> on line <code>{$line}</code>";
            $html .= "</div>";
        }

        $html .= '</div>';
        return $html;
    }

    /**
     * Retorna el CSS de la consola de errores tempranos
     */
    private function getCss(): string
    {
        return 'html, body { margin: 0; padding: 0; width: 100vw; height: 100vh; background: #1a1a1a; color: #ff3333; font-family: "Menlo", Monaco, Consolas, "Courier New", monospace; overflow: hidden; }' .
            'body { display: flex; flex-direction: column; align-items: center; justify-content: center; }' .
            '.console { background: #0a0a0a; border-radius: 10px; box-shadow: 0 10px 30px rgba(255, 0, 0, 0.3); width: 95vw; height: 95vh; display: flex; flex-direction: column; overflow: hidden; border: 2px solid #ff3333; }' .
            '.console-header { background: #1c1c1c; padding: 10px 20px; display: flex; align-items: center; gap: 10px; border-bottom: 2px solid #ff3333; flex: 0 0 auto; }' .
            '.console-icon { border: 3px solid #ff3333; padding: 2px 8px; font-weight: 700; color: #ff3333; font-family: monospace; user-select: none; display: flex; align-items: center; justify-content: center; font-size: 16px; border-radius: 8px; flex-shrink: 0; height: 22px; }' .
            '.console-title { color: #ff3333; font-weight: 600; font-size: 16px; user-select: none; flex-grow: 1; text-align: center; letter-spacing: 1px; }' .
            '.console-body { padding: 20px; white-space: pre-wrap; word-break: break-word; font-size: 15px; line-height: 1.4; color: #ff6666; overflow-y: auto; flex-grow: 1; }' .
            'h2 { margin-top: 0; margin-bottom: 15px; font-weight: normal; font-size: 18px; color: #ff3333; word-break: break-word; }' .
            '.warning { background: #331111; padding: 15px; margin-bottom: 20px; border-left: 4px solid #ff3333; border-radius: 4px; font-size: 14px; }' .
            '.detail { margin-bottom: 10px; color: #ff8888; }' .
            '.location { margin-bottom: 20px; color: #ff9999; font-size: 14px; }' .
            '.trace { margin-top: 20px; color: #ff9999; font-size: 13px; line-height: 1.3; }' .
            '.trace-item { margin-bottom: 6px; }' .
            'code { background: #1a1a1a; padding: 2px 6px; border-radius: 4px; color: #ffaaaa; font-family: "Menlo", Monaco, Consolas, "Courier New", monospace; word-break: break-word; }' .
            '::selection { background: #ff333388; color: #fff; }';
    }
}