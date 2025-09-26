<?php

/**
 * Balero CMS
 * @author Anibal Gomez <balerocms@gmail.com>
 * @license GNU General Public License
 */

namespace Framework\Routing;

use Exception;
use Framework\Core\Boot;
use Framework\Core\ConfigSettings;
use Framework\Core\ErrorConsole;
use Framework\Http\RequestHelper;
use Framework\Static\Redirect;
use Modules\Admin\AdminElements;
use Framework\Exceptions\RouterException;
use Throwable;

class Router
{

    /**
     * Load default app controller
     */
    private const DEFAULT_MODULE = 'Block';

    /**
     * Constante que define el nombre del parámetro index.php?module={module}
     */
    private const PARAM_MODULE = 'module';

    private RequestHelper $request;
    private ConfigSettings $configSettings;
    private string $module;

    private Boot $boot;

    public function __construct(
        ConfigSettings $configSettings,
        RequestHelper $request,
        Boot $boot
    )
    {
        $this->configSettings = $configSettings;
        $this->request = $request;

        $this->boot = $boot;

        $this->initSessionLang();
        $this->checkInstallerRedirect();
    }

    private function checkInstallerRedirect(): void
    {
        try {

            if (
                !isset($this->configSettings->basepath) ||
                $this->configSettings->basepath === ''
            ) {
                $this->configSettings->basepath = rtrim($this->configSettings->getFullBasepath(), '/') . '/';
            }

            $currentModule = $this->request->get(self::PARAM_MODULE);
            if ($currentModule === 'notification') {
                return;
            }
            $installed = $this->configSettings->installed;

            // Si no está instalado, forzar acceso al instalador
            if ($installed === "no" && $currentModule !== 'installer') {
                Redirect::to('/installer');
                exit;
            }

            // Si ya está instalado, impedir acceder al instalador
            if ($installed === "yes" && $currentModule === 'installer') {
                Redirect::to('/');
                exit;
            }

        } catch (Throwable $e) {
            ErrorConsole::handleException($e);
        }
    }

    public function initBalero(): self
    {

        // Resolver application
        $module = $this->request->get(self::PARAM_MODULE);

        if (!$module) {
            $this->initModule(self::DEFAULT_MODULE);
            exit;
        }

        // Default load
        match ($module) {
            default => $this->initModule(ucfirst($module)),
        };

        return $this;
    }

    private function initModule(string $module): void
    {
        $this->module = $module;
        $controllerClass = "Modules\\{$module}\\Controllers\\{$module}Controller";

        if (!class_exists($controllerClass)) {
            ErrorConsole::handleException(new RouterException("Controller class not found: $controllerClass"));
        }

        $this->boot->loadController($controllerClass);
    }

    private function initSessionLang(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        // Solo inicializa si no existe
        if (!isset($_SESSION['lang'])) {
            $_SESSION['lang'] = $this->configSettings->language ?? 'en';
        }
    }

}
