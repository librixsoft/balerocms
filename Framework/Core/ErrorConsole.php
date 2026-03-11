<?php

namespace Framework\Core;

use App\Controllers\Error\ErrorController;
use Framework\DI\Container;
use Throwable;

/**
 * Class ErrorConsole
 *
 * Changes for testability:
 *  - $exitCallback   : callable invoked instead of exit(); default calls exit()
 *  - $outputCallback : callable invoked instead of echo; default echoes normally
 *  - $logCallback    : callable invoked instead of error_log(); injectable for spying
 *  - isRendered()    : public accessor for $rendered (assertions in tests)
 *  - reset()         : resets $rendered so a single instance can be reused across tests
 */
class ErrorConsole
{
    private bool $rendered = false;

    private ConfigSettings $configSettings;
    private Container $container;

    /** @var callable */
    private $exitCallback;

    /** @var callable */
    private $outputCallback;

    /** @var callable */
    private $logCallback;

    public function __construct(
        ConfigSettings $configSettings,
        Container $container,
        ?callable $exitCallback   = null,
        ?callable $outputCallback = null,
        ?callable $logCallback    = null
    ) {
        $this->configSettings   = $configSettings;
        $this->container        = $container;
        $this->exitCallback     = $exitCallback   ?? static function (): void { exit; };
        $this->outputCallback   = $outputCallback ?? static function (string $html): void { echo $html; };
        $this->logCallback      = $logCallback    ?? static function (string $msg): void { error_log($msg); };
    }

    // -------------------------------------------------------------------------
    // Public test-helpers
    // -------------------------------------------------------------------------

    public function isRendered(): bool
    {
        return $this->rendered;
    }

    /** Allow reuse of one instance across multiple test cases. */
    public function reset(): void
    {
        $this->rendered = false;
    }

    // -------------------------------------------------------------------------
    // Core API
    // -------------------------------------------------------------------------

    public function isProduction(): bool          // made public for direct assertions
    {
        return $this->configSettings->debug === 'prod';
    }

    public function register(): void
    {
        if (!ob_get_level()) {
            ob_start();
        }

        ini_set('display_errors', $this->isProduction() ? '0' : '1');
        error_reporting(E_ALL);

        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
        register_shutdown_function([$this, 'handleShutdown']);
    }

    public function handleError(int $errno, string $errstr, string $errfile, int $errline): void
    {
        $this->cleanOutput();

        $message  = "Error [$errno]";
        $detail   = htmlspecialchars($errstr);
        $location = htmlspecialchars($errfile) . " (Line: $errline)";

        $this->renderOutput($message, $detail, $location);
    }

    public function handleException(Throwable $e): void
    {
        $this->cleanOutput();

        $message  = "Exception: " . get_class($e);
        $detail   = htmlspecialchars($e->getMessage());
        $location = htmlspecialchars($e->getFile()) . " (Line: " . $e->getLine() . ")";

        $this->renderOutput($message, $detail, $location, $e);
    }

    public function handleShutdown(): void
    {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            $this->cleanOutput();

            $message  = "Fatal Error";
            $detail   = htmlspecialchars($error['message']);
            $location = htmlspecialchars($error['file']) . " (Line: {$error['line']})";

            $this->renderOutput($message, $detail, $location);
        }
    }

    // -------------------------------------------------------------------------
    // Internal rendering
    // -------------------------------------------------------------------------

    private function renderOutput(string $message, string $detail, string $location, ?Throwable $e = null): void
    {
        if ($this->rendered) {
            return;
        }
        $this->rendered = true;

        // Production + installed → delegate to ErrorController
        if ($this->configSettings->installed === 'yes' && $this->configSettings->debug === 'prod') {
            $this->logError($message . ": " . $detail . " in " . $location, $e);

            try {
                $errorController = $this->container->get(ErrorController::class);
                ($this->outputCallback)($errorController->index());
            } catch (Throwable $controllerError) {
                $this->renderConsole($message, $detail, $location, $e);
                return;                                    // exitCallback already called inside
            }

            ($this->exitCallback)();
            return;
        }

        $this->renderConsole($message, $detail, $location, $e);
    }

    private function cleanOutput(): void
    {
        if (ob_get_length()) {
            ob_clean();
        }
    }

    /**
     * Builds the HTML console string and hands it to the output/exit callbacks
     * so tests can intercept both without terminating the process.
     */
    private function renderConsole(string $message, string $detail, string $location, ?Throwable $e = null): void
    {
        $html  = '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Balero CMS Error Console</title>';
        $html .= '<style>' . $this->getCss() . '</style>';
        $html .= '</head><body>';
        $html .= '<div class="console">';
        $html .= '<div class="console-header">';
        $html .= '<div class="console-icon">&gt;_</div>';
        $html .= '<div class="console-title">Balero CMS Error Console</div>';
        $html .= '</div>';
        $html .= '<div class="console-body">';
        $html .= '<h2>' . $message . '</h2>';
        $html .= '<div class="detail">Message: ' . $detail . '</div>';
        $html .= '<div class="location">Location: ' . $location . '</div>';

        if ($e) {
            $html .= '<div class="trace">';
            foreach ($e->getTrace() as $i => $trace) {
                $file  = htmlspecialchars($trace['file'] ?? '[internal]');
                $line  = $trace['line'] ?? '?';
                $func  = htmlspecialchars($trace['function'] ?? '???');
                $html .= "<div class=\"trace-item\">#$i <code>$func()</code> in <code>$file</code> on line <code>$line</code></div>";
            }
            $html .= '</div>';
        }

        $html .= '</div></div></body></html>';

        ($this->outputCallback)($html);
        ($this->exitCallback)();
    }

    private function getCss(): string
    {
        return 'html, body { margin: 0; padding: 0; width: 100vw; height: 100vh; background: #2a2a2a; color: #33ff33; font-family: "Menlo", Monaco, Consolas, "Courier New", monospace; overflow: hidden; }' .
            'body { display: flex; flex-direction: column; align-items: center; justify-content: center; }' .
            '.console { background: #121212; border-radius: 10px; box-shadow: 0 10px 30px rgba(0,0,0,0.8); width: 95vw; height: 95vh; display: flex; flex-direction: column; overflow: hidden; }' .
            '.console-header { background: #2c2c2c; padding: 10px 20px; display: flex; align-items: center; gap: 10px; border-bottom: 1px solid #444; border-top-left-radius: 10px; border-top-right-radius: 10px; flex: 0 0 auto; }' .
            '.console-icon { border: 3px solid #bbb; padding: 2px 8px; font-weight: 700; color: #bbb; font-family: monospace; user-select: none; display: flex; align-items: center; justify-content: center; font-size: 16px; border-radius: 8px; flex-shrink: 0; height: 22px; }' .
            '.console-title { color: #33ff33; font-weight: 600; font-size: 16px; user-select: none; flex-grow: 1; text-align: center; letter-spacing: 1px; }' .
            '.console-body { padding: 20px; white-space: pre-wrap; word-break: break-word; font-size: 15px; line-height: 1.4; color: #33ff33; overflow-y: auto; flex-grow: 1; }' .
            'h2 { margin-top: 0; margin-bottom: 15px; font-weight: normal; font-size: 18px; color: #33ff33; word-break: break-word; }' .
            '.detail { margin-bottom: 10px; color: #88ff88; font-size: 15px; }' .
            '.location { margin-bottom: 20px; color: #99ff99; font-size: 14px; }' .
            '.trace { margin-top: 20px; color: #99ff99; font-size: 13px; line-height: 1.3; }' .
            '.trace-item { margin-bottom: 6px; }' .
            'code { background: #222222; padding: 2px 6px; border-radius: 4px; color: #8fef8f; font-family: "Menlo", Monaco, Consolas, "Courier New", monospace; word-break: break-word; }' .
            '::selection { background: #33ff33aa; color: #000; }';
    }

    private function logError(string $message, ?Throwable $e = null): void
    {
        $logMessage = $message;

        if ($e) {
            $logMessage .= " | BaleroCMS ::: Exception: " . get_class($e);
            $logMessage .= " | BaleroCMS ::: File: " . $e->getFile() . ":" . $e->getLine();
        }

        ($this->logCallback)($logMessage);
    }
}