<?php

/**
 * Balero CMS
 * @author Anibal Gomez <balerocms@gmail.com>
 * @license GNU General Public License
 */

namespace Framework\Core;

use Throwable;

class ErrorConsole
{
    // Devuelve true si estamos en producción
    private static function isProduction(): bool
    {
        return defined('APP_ENV') && APP_ENV === 'prod';
    }

    public static function register()
    {
        ob_start();

        if (self::isProduction()) {
            ini_set('display_errors', '0'); // Producción: no mostrar errores
        } else {
            ini_set('display_errors', '1'); // Desarrollo: mostrar errores
        }

        error_reporting(E_ALL);

        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleShutdown']);
    }

    public static function handleError($errno, $errstr, $errfile, $errline)
    {
        self::cleanOutput();

        if (self::isProduction()) {
            self::renderGeneric();
        } else {
            $message = "Error [$errno]: $errstr in $errfile on line $errline";
            self::renderConsole($message);
        }
    }

    public static function handleException(Throwable $e)
    {
        self::cleanOutput();

        if (self::isProduction()) {
            self::renderGeneric();
        } else {
            $message = "Exception: " . get_class($e) . "\n";
            $message .= "Message: " . $e->getMessage() . "\n";
            $message .= "File: " . $e->getFile() . " (" . $e->getLine() . ")\n";
            $message .= "Trace:\n" . $e->getTraceAsString();
            self::renderConsole($message, $e);
        }
    }

    public static function handleShutdown()
    {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            self::cleanOutput();
            if (self::isProduction()) {
                self::renderGeneric();
            } else {
                $message = "Fatal Error: {$error['message']} in {$error['file']} on line {$error['line']}";
                self::renderConsole($message);
            }
        }
    }

    private static function renderGeneric()
    {
        echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Oops!</title></head><body>';
        echo '<h1>Oops! Something went wrong.</h1>';
        echo '<p>We are working to fix the issue. Please try again later.</p>';
        echo '</body></html>';
        exit;
    }


    private static function cleanOutput()
    {
        if (ob_get_length()) {
            ob_clean();
        }
    }

    private static function renderConsole($mainMessage, Throwable $e = null)
    {
        static $rendered = false;
        if ($rendered) return;
        $rendered = true;

        echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Balero CMS Error Console</title>';
        echo '<style>
            html, body { margin: 0; padding: 0; width: 100vw; height: 100vh; background: #2a2a2a; color: #33ff33; font-family: "Menlo", Monaco, Consolas, "Courier New", monospace; overflow: hidden; }
            body { display: flex; flex-direction: column; align-items: center; justify-content: center; }
            .console { background: #121212; border-radius: 10px; box-shadow: 0 10px 30px rgba(0,0,0,0.8); width: 95vw; height: 95vh; display: flex; flex-direction: column; overflow: hidden; }
            .console-header { background: #2c2c2c; padding: 10px 20px; display: flex; align-items: center; gap: 10px; border-bottom: 1px solid #444; border-top-left-radius: 10px; border-top-right-radius: 10px; flex: 0 0 auto; }
            .console-icon { border: 3px solid #bbb; padding: 2px 8px; font-weight: 700; color: #bbb; font-family: monospace; user-select: none; display: flex; align-items: center; justify-content: center; font-size: 16px; border-radius: 8px; flex-shrink: 0; height: 22px; }
            .console-title { color: #33ff33; font-weight: 600; font-size: 16px; user-select: none; flex-grow: 1; text-align: center; letter-spacing: 1px; }
            .console-body { padding: 20px; white-space: pre-wrap; word-break: break-word; font-size: 15px; line-height: 1.4; color: #33ff33; overflow-y: auto; flex-grow: 1; }
            h2 { margin-top: 0; font-weight: normal; font-size: 18px; color: #33ff33; word-break: break-word; }
            .trace { margin-top: 20px; color: #99ff99; font-size: 13px; line-height: 1.3; }
            .trace-item { margin-bottom: 6px; }
            code { background: #222222; padding: 2px 6px; border-radius: 4px; color: #8fef8f; font-family: "Menlo", Monaco, Consolas, "Courier New", monospace; word-break: break-word; }
            ::selection { background: #33ff33aa; color: #000; }
        </style>';
        echo '</head><body>';
        echo '<div class="console">';
        echo '<div class="console-header">';
        echo '<div class="console-icon">&gt;_</div>';
        echo '<div class="console-title">Balero CMS Error Console</div>';
        echo '</div>';
        echo '<div class="console-body">';
        echo "<h2>$mainMessage</h2>";

        if ($e) {
            echo "<div class=\"trace\">";
            foreach ($e->getTrace() as $i => $trace) {
                $file = $trace['file'] ?? '[internal]';
                $line = $trace['line'] ?? '?';
                $func = $trace['function'] ?? '???';
                echo "<div class=\"trace-item\">#$i <code>$func()</code> in <code>$file</code> on line <code>$line</code></div>";
            }
            echo "</div>";
        }

        echo '</div></div></body></html>';
        exit;
    }
}
