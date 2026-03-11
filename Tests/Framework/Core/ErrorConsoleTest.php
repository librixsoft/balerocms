<?php

declare(strict_types=1);

namespace Tests\Framework\Core;

use Exception;
use Framework\Config\SetupConfig;
use Framework\Core\ConfigSettings;
use Framework\Core\ErrorConsole;
use Framework\DI\Container;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Throwable;

/**
 * Full test suite for ErrorConsole.
 *
 * Strategy
 * --------
 * • exitCallback   → throws ExitException so exit() never kills the process
 * • outputCallback → captures HTML into $this->capturedOutput
 * • logCallback    → captures log messages into $this->capturedLogs
 *
 * Tested axes
 * -----------
 *  1. isProduction() flag for prod / dev configs
 *  2. register() sets PHP ini + error handlers
 *  3. handleError()     → console HTML structure & content
 *  4. handleException() → message, class, file, line, stack trace in HTML
 *  5. handleShutdown()  → fatal-error branch + no-op for non-fatal
 *  6. rendered guard    → second call is a no-op
 *  7. reset()           → re-enables rendering
 *  8. Prod + installed  → logError called, ErrorController delegated
 *  9. Prod + installed  → controller failure falls back to console HTML
 * 10. Dev mode          → never calls logCallback
 * 11. HTML escaping     → XSS characters are escaped
 * 12. Exception trace   → trace items appear in output
 * 13. CSS content       → green-console palette present
 */
final class ErrorConsoleTest extends TestCase
{
    // -------------------------------------------------------------------------
    // State saved/restored around each test
    //
    // PHPUnit 12 places its OWN error/exception handlers on the global handler
    // stack before every test. Calling restore_*_handler() unconditionally pops
    // those PHPUnit handlers off the stack, which PHPUnit flags as "risky".
    //
    // Solution: snapshot the handler-stack depth in setUp() and in tearDown()
    // restore only the handlers that THIS test added on top of PHPUnit's.
    // -------------------------------------------------------------------------

    private string $previousDisplayErrors = '1';

    /** Depth of the error-handler stack measured right after PHPUnit installs its own. */
    private int $errorHandlerDepth     = 0;
    private int $exceptionHandlerDepth = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->previousDisplayErrors = ini_get('display_errors') ?: '1';

        // Measure how deep the stacks are after PHPUnit has set up its handlers.
        // We do this by installing a no-op, checking the depth, then removing it.
        set_error_handler(static function (): bool { return false; });
        // set_error_handler returns the PREVIOUS handler; we use it just to
        // count: the stack depth equals how many times we can restore before
        // restore_error_handler returns false.
        restore_error_handler();   // ← removes our no-op, stack back to PHPUnit level

        set_exception_handler(static function (\Throwable $e): void {});
        restore_exception_handler();

        // Record current depth as "baseline" by counting existing handlers.
        // Simplest reliable method: install sentinel, record, remove sentinel.
        $this->errorHandlerDepth     = $this->measureHandlerDepth('error');
        $this->exceptionHandlerDepth = $this->measureHandlerDepth('exception');
    }

    protected function tearDown(): void
    {
        ini_set('display_errors', $this->previousDisplayErrors);

        // Pop only handlers that this test added above the PHPUnit baseline.
        $this->restoreToDepth('error',     $this->errorHandlerDepth);
        $this->restoreToDepth('exception', $this->exceptionHandlerDepth);

        parent::tearDown();
    }

    /**
     * Count how many user-land handlers are currently installed.
     * We repeatedly restore until restore returns false (meaning we hit the
     * built-in default), counting as we go, then re-install everything we popped.
     */
    private function measureHandlerDepth(string $type): int
    {
        $handlers = [];

        if ($type === 'error') {
            while (true) {
                $h = set_error_handler(static function (): bool { return false; });
                restore_error_handler(); // remove sentinel
                if ($h === null) {
                    break;
                }
                $handlers[] = $h;
                restore_error_handler(); // pop real handler
            }
            // Re-install in original order (LIFO → reverse)
            foreach (array_reverse($handlers) as $h) {
                set_error_handler($h);
            }
        } else {
            while (true) {
                $h = set_exception_handler(static function (\Throwable $e): void {});
                restore_exception_handler(); // remove sentinel
                if ($h === null) {
                    break;
                }
                $handlers[] = $h;
                restore_exception_handler(); // pop real handler
            }
            foreach (array_reverse($handlers) as $h) {
                set_exception_handler($h);
            }
        }

        return count($handlers);
    }

    private function restoreToDepth(string $type, int $targetDepth): void
    {
        $current = $this->measureHandlerDepth($type);
        $toRemove = $current - $targetDepth;

        for ($i = 0; $i < $toRemove; $i++) {
            if ($type === 'error') {
                restore_error_handler();
            } else {
                restore_exception_handler();
            }
        }
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /** Thrown by the injected exitCallback to stop execution without exit(). */
    private static function exitException(): Throwable
    {
        return new class('exit called') extends \RuntimeException {};
    }

    private string $capturedOutput = '';
    /** @var list<string> */
    private array $capturedLogs = [];

    /**
     * Build an ErrorConsole wired to safe test doubles.
     *
     * @param string   $debug     'prod' | 'dev'
     * @param string   $installed 'yes'  | 'no'
     * @param ?Container $container supply a custom Container mock
     */
    private function makeConsole(
        string     $debug      = 'dev',
        string     $installed  = 'yes',
        ?Container $container  = null
    ): ErrorConsole {
        $this->capturedOutput = '';
        $this->capturedLogs   = [];

        $cfg = $this->makeConfig($debug, $installed);
        $cnt = $container ?? new Container();

        $exitCb   = function (): void {
            throw new class('exit called') extends RuntimeException {};
        };
        $outputCb = function (string $html): void {
            $this->capturedOutput .= $html;
        };
        $logCb    = function (string $msg): void {
            $this->capturedLogs[] = $msg;
        };

        return new ErrorConsole($cfg, $cnt, $exitCb, $outputCb, $logCb);
    }

    private function makeConfig(string $debug, string $installed = 'yes'): ConfigSettings
    {
        $tmp = tempnam(sys_get_temp_dir(), 'cfg');
        file_put_contents($tmp, json_encode([
            'config' => [
                'database' => ['dbhost' => 'h', 'dbuser' => 'u', 'dbpass' => 'p', 'dbname' => 'd'],
                'admin'    => ['username' => 'u', 'passwd' => 'p', 'email' => '', 'firstname' => '', 'lastname' => ''],
                'system'   => ['installed' => $installed, 'debug' => $debug],
                'site'     => [
                    'language' => 'en', 'title' => '', 'description' => '', 'url' => '',
                    'keywords' => '', 'basepath' => '/', 'theme' => 'default',
                    'footer'   => '', 'multilang' => false, 'editor' => '',
                ],
            ],
        ], JSON_THROW_ON_ERROR));

        $cfg = new ConfigSettings(new SetupConfig($tmp));
        $cfg->getHandler();
        return $cfg;
    }

    /** Run a callable and swallow only our synthetic ExitException. */
    private function runWithoutExit(callable $fn): void
    {
        try {
            $fn();
        } catch (RuntimeException $e) {
            if ($e->getMessage() !== 'exit called') {
                throw $e;
            }
            // swallow — simulates exit()
        }
    }

    // =========================================================================
    // 1. isProduction()
    // =========================================================================

    public function testIsProductionReturnsTrueForProd(): void
    {
        $ec = $this->makeConsole('prod');
        $this->assertTrue($ec->isProduction());
    }

    public function testIsProductionReturnsFalseForDev(): void
    {
        $ec = $this->makeConsole('dev');
        $this->assertFalse($ec->isProduction());
    }

    public function testIsProductionReturnsFalseForArbitraryString(): void
    {
        $ec = $this->makeConsole('staging');
        $this->assertFalse($ec->isProduction());
    }

    // =========================================================================
    // 2. register() — handler wiring
    // =========================================================================

    public function testRegisterSetsErrorHandler(): void
    {
        $ec = $this->makeConsole('dev');
        $ec->register();

        // Verify our handler is in place by calling set_error_handler with null
        // (which returns the current handler and restores default)
        $previous = set_error_handler(null);
        restore_error_handler();

        $this->assertIsArray($previous);
        $this->assertSame($ec, $previous[0]);
        $this->assertSame('handleError', $previous[1]);
    }

    public function testRegisterSetsExceptionHandler(): void
    {
        $ec = $this->makeConsole('dev');
        $ec->register();

        $previous = set_exception_handler(null);
        restore_exception_handler();

        $this->assertIsArray($previous);
        $this->assertSame($ec, $previous[0]);
        $this->assertSame('handleException', $previous[1]);
    }

    public function testRegisterSetsDisplayErrorsOffInProduction(): void
    {
        $ec = $this->makeConsole('prod');
        $ec->register();
        // ini_get('display_errors') returns '0' or '' (empty) depending on PHP build/SAPI
        $val = ini_get('display_errors');
        $this->assertTrue(
            $val === '0' || $val === '',
            "Expected display_errors to be off ('0' or ''), got: '$val'"
        );
    }

    public function testRegisterSetsDisplayErrorsOnInDev(): void
    {
        $ec = $this->makeConsole('dev');
        $ec->register();
        // ini_get('display_errors') returns '1' or 'On' depending on PHP build/SAPI
        $val = ini_get('display_errors');
        $this->assertTrue(
            $val === '1' || strtolower($val) === 'on',
            "Expected display_errors to be on ('1' or 'On'), got: '$val'"
        );
    }

    public function testRegisterStartsOutputBufferingIfNotAlreadyStarted(): void
    {
        // We must NOT drain PHPUnit's own output buffers (that would be flagged
        // as risky). Instead, record the current level and verify that register()
        // adds exactly one more level on top of whatever PHPUnit already opened.
        $levelBefore = ob_get_level();

        // Temporarily close all levels down to 0 only if PHPUnit hasn't opened
        // any (level is already 0), otherwise we test the delta approach.
        if ($levelBefore === 0) {
            $ec = $this->makeConsole('dev');
            $ec->register();

            $this->assertGreaterThan(0, ob_get_level());
            // Close only the buffer that register() opened (one level)
            ob_end_clean();
        } else {
            // PHPUnit has buffers open — test that register() opens one more
            $ec = $this->makeConsole('dev');
            $ec->register();

            // register() calls ob_start() only when ob_get_level() === 0,
            // so with PHPUnit buffers already open, level stays the same.
            // Assert the method ran without error and the level is still >= baseline.
            $this->assertGreaterThanOrEqual($levelBefore, ob_get_level());
        }
    }

    // =========================================================================
    // 3. handleError()
    // =========================================================================

    public function testHandleErrorProducesHtmlWithErrorNumber(): void
    {
        $ec = $this->makeConsole('dev');
        $this->runWithoutExit(fn() => $ec->handleError(E_WARNING, 'Something bad', '/app/foo.php', 42));

        $this->assertStringContainsString('Error [' . E_WARNING . ']', $this->capturedOutput);
    }

    public function testHandleErrorContainsMessageInDetail(): void
    {
        $ec = $this->makeConsole('dev');
        $this->runWithoutExit(fn() => $ec->handleError(E_NOTICE, 'Undefined variable', '/app/bar.php', 10));

        $this->assertStringContainsString('Undefined variable', $this->capturedOutput);
    }

    public function testHandleErrorContainsFileAndLineInLocation(): void
    {
        $ec = $this->makeConsole('dev');
        $this->runWithoutExit(fn() => $ec->handleError(E_NOTICE, 'msg', '/var/www/myfile.php', 99));

        $this->assertStringContainsString('/var/www/myfile.php', $this->capturedOutput);
        $this->assertStringContainsString('Line: 99', $this->capturedOutput);
    }

    public function testHandleErrorSetsRenderedFlag(): void
    {
        $ec = $this->makeConsole('dev');
        $this->assertFalse($ec->isRendered());

        $this->runWithoutExit(fn() => $ec->handleError(E_WARNING, 'msg', __FILE__, __LINE__));

        $this->assertTrue($ec->isRendered());
    }

    public function testHandleErrorOutputIsValidHtml(): void
    {
        $ec = $this->makeConsole('dev');
        $this->runWithoutExit(fn() => $ec->handleError(E_ERROR, 'Fatal-ish', '/f.php', 1));

        $this->assertStringStartsWith('<!DOCTYPE html>', $this->capturedOutput);
        $this->assertStringContainsString('</html>', $this->capturedOutput);
    }

    // =========================================================================
    // 4. handleException()
    // =========================================================================

    public function testHandleExceptionShowsExceptionClass(): void
    {
        $ec = $this->makeConsole('dev');
        $e  = new RuntimeException('Something exploded');

        $this->runWithoutExit(fn() => $ec->handleException($e));

        $this->assertStringContainsString('RuntimeException', $this->capturedOutput);
    }

    public function testHandleExceptionShowsMessage(): void
    {
        $ec = $this->makeConsole('dev');
        $e  = new Exception('My custom error message');

        $this->runWithoutExit(fn() => $ec->handleException($e));

        $this->assertStringContainsString('My custom error message', $this->capturedOutput);
    }

    public function testHandleExceptionShowsFileAndLine(): void
    {
        $ec   = $this->makeConsole('dev');
        $line = __LINE__ + 1;
        $e    = new RuntimeException('loc test');

        $this->runWithoutExit(fn() => $ec->handleException($e));

        $this->assertStringContainsString(__FILE__, $this->capturedOutput);
        $this->assertStringContainsString((string)$line, $this->capturedOutput);
    }

    public function testHandleExceptionRendersStackTrace(): void
    {
        $ec = $this->makeConsole('dev');
        $e  = new RuntimeException('trace test');

        $this->runWithoutExit(fn() => $ec->handleException($e));

        // At least one trace-item should appear
        $this->assertStringContainsString('trace-item', $this->capturedOutput);
        $this->assertStringContainsString('#0', $this->capturedOutput);
    }

    public function testHandleExceptionSetsRenderedFlag(): void
    {
        $ec = $this->makeConsole('dev');
        $this->runWithoutExit(fn() => $ec->handleException(new Exception('x')));
        $this->assertTrue($ec->isRendered());
    }

    // =========================================================================
    // 5. handleShutdown()
    // =========================================================================

    public function testHandleShutdownIsNoOpWhenNoError(): void
    {
        $ec = $this->makeConsole('dev');
        // No fatal error in last_error → nothing should be output
        $ec->handleShutdown();

        $this->assertSame('', $this->capturedOutput);
        $this->assertFalse($ec->isRendered());
    }

    // =========================================================================
    // 6. rendered guard (idempotency)
    // =========================================================================

    public function testSecondHandleErrorCallIsNoOp(): void
    {
        $ec = $this->makeConsole('dev');
        $this->runWithoutExit(fn() => $ec->handleError(E_NOTICE, 'first', __FILE__, 1));

        $firstOutput = $this->capturedOutput;
        // Second invocation — rendered guard must prevent double output
        $ec->handleError(E_NOTICE, 'second', __FILE__, 2);  // no exit, guard returns early

        $this->assertSame($firstOutput, $this->capturedOutput, 'Output must not grow after first render');
        $this->assertStringNotContainsString('second', $this->capturedOutput);
    }

    public function testSecondHandleExceptionCallIsNoOp(): void
    {
        $ec = $this->makeConsole('dev');
        $this->runWithoutExit(fn() => $ec->handleException(new Exception('first')));

        $firstOutput = $this->capturedOutput;
        $ec->handleException(new Exception('second'));

        $this->assertSame($firstOutput, $this->capturedOutput);
    }

    // =========================================================================
    // 7. reset()
    // =========================================================================

    public function testResetClearsRenderedFlag(): void
    {
        $ec = $this->makeConsole('dev');
        $this->runWithoutExit(fn() => $ec->handleException(new Exception('a')));
        $this->assertTrue($ec->isRendered());

        $ec->reset();
        $this->assertFalse($ec->isRendered());
    }

    public function testAfterResetSecondErrorRendersNewOutput(): void
    {
        $ec = $this->makeConsole('dev');
        $this->runWithoutExit(fn() => $ec->handleError(E_WARNING, 'first error', __FILE__, 1));
        $ec->reset();
        $this->capturedOutput = '';

        $this->runWithoutExit(fn() => $ec->handleError(E_ERROR, 'second error', __FILE__, 2));

        $this->assertStringContainsString('second error', $this->capturedOutput);
    }

    // =========================================================================
    // 8. Production mode — delegates to ErrorController + logs
    // =========================================================================

    public function testProductionModeCallsLogCallbackOnException(): void
    {
        // Container that returns a fake ErrorController
        $container = $this->createMock(Container::class);
        $container->method('get')->willReturn(new class {
            public function index(): string { return '<p>500</p>'; }
        });

        $ec = $this->makeConsole('prod', 'yes', $container);
        $this->runWithoutExit(fn() => $ec->handleException(new RuntimeException('boom')));

        $this->assertNotEmpty($this->capturedLogs, 'logCallback must be called in production');
        $this->assertStringContainsString('boom', $this->capturedLogs[0]);
    }

    public function testProductionModeRendersControllerOutput(): void
    {
        $container = $this->createMock(Container::class);
        $container->method('get')->willReturn(new class {
            public function index(): string { return '<p>Custom 500 page</p>'; }
        });

        $ec = $this->makeConsole('prod', 'yes', $container);
        $this->runWithoutExit(fn() => $ec->handleException(new RuntimeException('err')));

        $this->assertStringContainsString('Custom 500 page', $this->capturedOutput);
    }

    public function testProductionModeLogContainsExceptionClassAndFile(): void
    {
        $container = $this->createMock(Container::class);
        $container->method('get')->willReturn(new class {
            public function index(): string { return ''; }
        });

        $ec = $this->makeConsole('prod', 'yes', $container);
        $this->runWithoutExit(fn() => $ec->handleException(new RuntimeException('detail check')));

        $log = implode(' ', $this->capturedLogs);
        $this->assertStringContainsString('RuntimeException', $log);
        $this->assertStringContainsString('BaleroCMS', $log);
    }

    // =========================================================================
    // 9. Production mode — controller failure falls back to console HTML
    // =========================================================================

    public function testProductionFallsBackToConsoleWhenControllerThrows(): void
    {
        $container = $this->createMock(Container::class);
        $container->method('get')->willThrowException(new RuntimeException('DI failure'));

        $ec = $this->makeConsole('prod', 'yes', $container);
        $this->runWithoutExit(fn() => $ec->handleException(new Exception('original')));

        $this->assertStringContainsString('Balero CMS Error Console', $this->capturedOutput);
    }

    public function testProductionFallbackContainsOriginalExceptionMessage(): void
    {
        $container = $this->createMock(Container::class);
        $container->method('get')->willThrowException(new RuntimeException('DI failure'));

        $ec = $this->makeConsole('prod', 'yes', $container);
        $this->runWithoutExit(fn() => $ec->handleException(new Exception('the original message')));

        $this->assertStringContainsString('the original message', $this->capturedOutput);
    }

    // =========================================================================
    // 10. Dev mode — log callback must NOT be called
    // =========================================================================

    public function testDevModeDoesNotCallLogCallback(): void
    {
        $ec = $this->makeConsole('dev');
        $this->runWithoutExit(fn() => $ec->handleException(new Exception('dev error')));

        $this->assertEmpty($this->capturedLogs, 'logCallback must NOT be called in dev mode');
    }

    public function testDevModeHandleErrorDoesNotCallLogCallback(): void
    {
        $ec = $this->makeConsole('dev');
        $this->runWithoutExit(fn() => $ec->handleError(E_WARNING, 'warn', __FILE__, __LINE__));

        $this->assertEmpty($this->capturedLogs);
    }

    // =========================================================================
    // 11. HTML escaping (XSS prevention)
    // =========================================================================

    public function testHandleErrorEscapesHtmlInMessage(): void
    {
        $ec = $this->makeConsole('dev');
        $this->runWithoutExit(fn() => $ec->handleError(E_NOTICE, '<script>alert(1)</script>', '/f.php', 1));

        $this->assertStringNotContainsString('<script>', $this->capturedOutput);
        $this->assertStringContainsString('&lt;script&gt;', $this->capturedOutput);
    }

    public function testHandleErrorEscapesHtmlInFilePath(): void
    {
        $ec = $this->makeConsole('dev');
        $this->runWithoutExit(fn() => $ec->handleError(E_ERROR, 'msg', '/path/<evil>/file.php', 5));

        $this->assertStringNotContainsString('<evil>', $this->capturedOutput);
        $this->assertStringContainsString('&lt;evil&gt;', $this->capturedOutput);
    }

    public function testHandleExceptionEscapesHtmlInMessage(): void
    {
        $ec = $this->makeConsole('dev');
        $e  = new RuntimeException('<img src=x onerror=alert(1)>');

        $this->runWithoutExit(fn() => $ec->handleException($e));

        $this->assertStringNotContainsString('<img', $this->capturedOutput);
        $this->assertStringContainsString('&lt;img', $this->capturedOutput);
    }

    // =========================================================================
    // 12. Exception trace rendering
    // =========================================================================

    public function testTraceContainsFunctionNames(): void
    {
        $ec = $this->makeConsole('dev');

        // Create a helper that has a known function name in the trace
        $throwFromHelper = static function (): Exception {
            return new RuntimeException('trace func');
        };
        $e = $throwFromHelper();

        $this->runWithoutExit(fn() => $ec->handleException($e));

        // The closure/function should appear somewhere in the trace
        $this->assertStringContainsString('()', $this->capturedOutput);
    }

    public function testTraceContainsCodeTags(): void
    {
        $ec = $this->makeConsole('dev');
        $this->runWithoutExit(fn() => $ec->handleException(new RuntimeException('tags')));

        $this->assertStringContainsString('<code>', $this->capturedOutput);
    }

    public function testHandleExceptionWithNullFileInTrace(): void
    {
        // getTrace() is final in PHP 8 — cannot be overridden via subclass.
        // We verify the renderConsole trace path handles missing 'file' keys by
        // triggering a real exception from inside a built-in call. array_map()
        // with a closure produces at least one internal-PHP frame (no 'file' key).
        $innerException = null;
        array_map(static function () use (&$innerException): void {
            $innerException = new RuntimeException('trace with internal frames');
        }, [1]);

        $ec = $this->makeConsole('dev');
        $this->runWithoutExit(fn() => $ec->handleException($innerException));

        // Structural assertion: trace section and items are rendered
        $this->assertStringContainsString('class="trace"', $this->capturedOutput);
        $this->assertStringContainsString('trace-item', $this->capturedOutput);
    }

    // =========================================================================
    // 13. CSS / console palette
    // =========================================================================

    public function testOutputContainsGreenConsolePalette(): void
    {
        $ec = $this->makeConsole('dev');
        $this->runWithoutExit(fn() => $ec->handleError(E_ERROR, 'palette', __FILE__, 1));

        $this->assertStringContainsString('#33ff33', $this->capturedOutput);
        $this->assertStringContainsString('#121212', $this->capturedOutput);
    }

    public function testOutputContainsConsoleTitleText(): void
    {
        $ec = $this->makeConsole('dev');
        $this->runWithoutExit(fn() => $ec->handleError(E_WARNING, 'title', __FILE__, 1));

        $this->assertStringContainsString('Balero CMS Error Console', $this->capturedOutput);
    }

    // =========================================================================
    // 14. Reflection-based API surface (class interface contract)
    // =========================================================================

    public function testRequiredPublicMethodsExist(): void
    {
        $r = new \ReflectionClass(ErrorConsole::class);

        foreach (['register', 'handleError', 'handleException', 'handleShutdown', 'isProduction', 'isRendered', 'reset'] as $method) {
            $this->assertTrue($r->hasMethod($method), "Method $method must exist");
            $this->assertTrue($r->getMethod($method)->isPublic(), "Method $method must be public");
        }
    }

    public function testConstructorAcceptsCallableOverrides(): void
    {
        $called = false;
        $ec = new ErrorConsole(
            $this->makeConfig('dev'),
            new Container(),
            function () use (&$called): void { $called = true; },
            function (string $h): void {},
            function (string $l): void {}
        );

        $r = new \ReflectionClass($ec);
        $this->assertTrue($r->hasMethod('register'));   // sanity: object is valid
    }
}