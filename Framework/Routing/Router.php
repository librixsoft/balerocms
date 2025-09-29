<?php

/**
 * Balero CMS
 * @author Anibal Gomez <balerocms@gmail.com>
 * @license GNU General Public License
 */

namespace Framework\Routing;

use Framework\Config\Context;
use Framework\Core\ConfigSettings;
use Framework\Core\Container;
use Framework\Http\RequestHelper;
use Framework\Static\Redirect;
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

    private Container $container;
    private Context $context;
    private RequestHelper $request;
    private ConfigSettings $configSettings;
    private ?string $module = null;

    public function __construct()
    {
        $this->container = new Container();
        $this->context = new Context($this->container);

        $this->configSettings = $this->container->get(ConfigSettings::class);
        $this->request = $this->container->get(RequestHelper::class);

        $this->initSessionLang();
        $this->checkInstallerRedirect();
    }

    private function checkInstallerRedirect(): void
    {
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

        if ($installed === "no" && $currentModule !== 'installer') {
            Redirect::to('/installer');
            exit;
        }

        if ($installed === "yes" && $currentModule === 'installer') {
            Redirect::to('/');
            exit;
        }
    }

    public function initBalero(): void
    {
        $this->module = $this->request->get(self::PARAM_MODULE);

        if (!$this->module) {
            $this->loadController(self::DEFAULT_MODULE);
            exit;
        }

        $this->loadController(ucfirst($this->module));
    }

    /**
     * Carga un controller y aplica DI.
     *
     * @param string $module
     * @throws RouterException
     */
    public function loadController(string $module): void
    {
        $controllerClass = "Modules\\{$module}\\Controllers\\{$module}Controller";

        if (!class_exists($controllerClass)) {
            throw new RouterException("Controller class not found: $controllerClass");
        }

        try {
            $instance = $this->getFromContainer($controllerClass);

            if (method_exists($instance, 'initControllerAndInject')) {
                $instance->initControllerAndInject();
            }
        } catch (Throwable $e) {
            throw new RouterException(
                "Error loading controller '$controllerClass': " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    private function initSessionLang(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (!isset($_SESSION['lang'])) {
            $_SESSION['lang'] = $this->configSettings->language ?? 'en';
        }
    }

    /**
     * Instancia cualquier clase usando el contenedor.
     *
     * @param string $class
     * @return object
     */
    public function getFromContainer(string $class): object
    {
        return $this->container->get($class);
    }
}
