<?php

declare(strict_types=1);

namespace Tests\Framework\Log;

use Framework\Core\ErrorConsole;
use Framework\Core\View;
use Framework\Log\Logger;
use PHPUnit\Framework\TestCase;

final class LoggerTest extends TestCase
{
    public function testErrorUsesErrorConsoleInNonProd(): void
    {
        $view = $this->createMock(View::class);
        $view->expects($this->never())->method('render');

        $errorConsole = $this->createMock(ErrorConsole::class);
        $errorConsole->expects($this->once())->method('handleException');

        $logger = new Logger($view, $errorConsole);
        $logger->error(new \RuntimeException('x'));
    }

}
