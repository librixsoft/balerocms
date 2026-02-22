<?php

namespace Framework\Log;

use Throwable;
use Framework\Core\View;
use Framework\Core\ErrorConsole;

class Logger
{
    private View $view;
    private ErrorConsole $errorConsole;
    private bool $isProduction;

    public function __construct(View $view, ErrorConsole $errorConsole)
    {
        $this->view = $view;
        $this->errorConsole = $errorConsole;
        $this->isProduction = defined('APP_ENV') && APP_ENV === 'prod';
    }

    /**
     * Wrapper de ErrorConsole::handleException()
     */
    public function error(Throwable $e): void
    {
        if ($this->isProduction) {
            // Producción → vista genérica
            $this->view->render('main.html', [
                'title'    => 'Oops!',
                'message'  => 'We are working to fix the issue. Please try again later.',
                'is_error' => true
            ]);
        } else {
            // Desarrollo → usar ErrorConsole
            $this->errorConsole->handleException($e);
        }
    }
}
