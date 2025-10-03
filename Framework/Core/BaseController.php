<?php

/**
 * Balero CMS
 * @author Anibal Gomez
 * @license GNU General Public License
 */

namespace Framework\Core;

use Framework\Http\Get;
use Framework\Http\Post;
use Framework\Http\RequestHelper;
use Framework\I18n\LangSelector;
use Framework\Http\JsonResponse;
use Framework\Security\LoginManager;
use Framework\Exceptions\ControllerException;
use ReflectionClass;
use ReflectionMethod;

class BaseController
{
    private const PARAM_TARGET = 'target';

    private View $view;
    private RequestHelper $requestHelper;
    private ConfigSettings $configSettings;
    private LoginManager $loginManager;
    private LangSelector $langSelector;

    public function __construct(
        View $view,
        RequestHelper $requestHelper,
        ConfigSettings $configSettings,
        LoginManager $loginManager,
        LangSelector $langSelector
    ) {
        $this->view = $view;
        $this->requestHelper = $requestHelper;
        $this->configSettings = $configSettings;
        $this->loginManager = $loginManager;
        $this->langSelector = $langSelector;
    }

    /**
     * Inicializa el Controller y ejecuta la ruta correspondiente.
     * Llamado desde Container::class
     * @param object|null $controllerInstance Instancia de ModuleController opcional.
     */
    public function initControllerAndRoute(?object $controllerInstance = null): void
    {
        $this->initBasePath();

        $httpMethod = $_SERVER['REQUEST_METHOD'];
        $requestedPath = $this->requestHelper->getPath(); // usamos RequestHelper

        $instanceToScan = $controllerInstance ?? $this;

        $reflection = new \ReflectionClass($instanceToScan);
        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);

        // Obtener ruta base del Controller
        $classAttrs = $reflection->getAttributes(\Framework\Attributes\Controller::class);
        $pathUrl = '/';
        if (!empty($classAttrs)) {
            $pathUrl = rtrim($classAttrs[0]->newInstance()->pathUrl, '/'); // <-- usamos pathUrl
        }

        // Opcional: Auth a nivel de clase
        $classAuthAttr = $reflection->getAttributes(\Framework\Http\Auth::class);
        $classAuth = !empty($classAuthAttr) ? $classAuthAttr[0]->newInstance() : null;

        foreach ($methods as $method) {
            foreach ($method->getAttributes() as $attribute) {
                $attrName = $attribute->getName();
                $routeInstance = $attribute->newInstance();

                // Solo procesar GET/POST según el método HTTP
                if (
                    ($attrName === \Framework\Http\Get::class && $httpMethod === 'GET') ||
                    ($attrName === \Framework\Http\Post::class && $httpMethod === 'POST')
                ) {
                    // Construir ruta completa combinando pathUrl + método
                    $routePath = rtrim($pathUrl, '/') . '/' . ltrim($routeInstance->target, '/');
                    $routePath = rtrim($routePath, '/');

                    if ($requestedPath === $routePath) {
                        // Auth a nivel de método
                        $methodAuthAttr = $method->getAttributes(\Framework\Http\Auth::class);
                        $auth = !empty($methodAuthAttr) ? $methodAuthAttr[0]->newInstance() : $classAuth;

                        if ($auth && $auth->required && !$this->loginManager->isLoggedIn()) {
                            throw new \Framework\Exceptions\ControllerException("Unauthorized access - login required");
                        }

                        // Ejecutar el método
                        $this->runMethod($method, [], $instanceToScan);
                        return;
                    }
                }
            }
        }

        throw new \Framework\Exceptions\ControllerException("Route not found: '{$requestedPath}'");
    }

    private function initBasePath(): void
    {
        $basepath = trim($this->configSettings->basepath ?? '');
        if ($basepath === '') {
            $basepath = $this->configSettings->getFullBasepath();
        }
        $this->configSettings->basepath = $basepath;
    }

    /**
     * Ejecuta el método del controller y procesa JSON, render o string.
     */
    private function runMethod(ReflectionMethod $method, array $params = [], ?object $controllerInstance = null): void
    {
        $controllerInstance ??= $this;

        $this->initLanguage();

        $result = $method->invoke($controllerInstance, ...$params);

        $jsonAttribute = $method->getAttributes(JsonResponse::class);

        if (!empty($jsonAttribute)) {
            if (is_array($result) || is_object($result)) {
                header('Content-Type: application/json');
                echo json_encode($result);
                exit;
            } else {
                header('Content-Type: application/json', true, 500);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Controller marked for JSON response did not return an array or object.'
                ]);
                exit;
            }
        }

        if (is_string($result)) {
            echo $result;
            exit;
        }

        if (is_array($result) && isset($result['view'])) {
            echo $this->view->render($result['view'], $result['params'] ?? []);
            exit;
        }
    }

    protected function initLanguage(): void
    {
        if ($this->requestHelper) {
            $this->langSelector->getLanguageParams($this->requestHelper);
        }
    }

}
