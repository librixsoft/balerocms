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

class Controller
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
     * @param object|null $controllerInstance Instancia de ModuleController opcional.
     */
    public function initControllerAndRoute(?object $controllerInstance = null): void
    {
        $this->initBasePath();

        $httpMethod = $_SERVER['REQUEST_METHOD'];
        $requestedTarget = trim($this->requestHelper->get(self::PARAM_TARGET) ?? '', '/');

        $instanceToScan = $controllerInstance ?? $this;

        $reflection = new ReflectionClass($instanceToScan);
        $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

        $classAuthAttr = $reflection->getAttributes(\Framework\Http\Auth::class);
        $classAuth = !empty($classAuthAttr) ? $classAuthAttr[0]->newInstance() : null;

        foreach ($methods as $method) {
            foreach ($method->getAttributes() as $attribute) {
                $attrName = $attribute->getName();
                $routeInstance = $attribute->newInstance();

                if (
                    ($attrName === Get::class && $httpMethod === 'GET') ||
                    ($attrName === Post::class && $httpMethod === 'POST')
                ) {
                    $routePattern = trim($routeInstance->target, '/');
                    $regex = preg_replace('#\{([^}]+)\}#', '(?P<$1>[^/]+)', $routePattern);
                    $regex = '#^' . $regex . '$#';

                    if (preg_match($regex, $requestedTarget, $matches)) {
                        $params = array_filter($matches, fn($key) => !is_int($key), ARRAY_FILTER_USE_KEY);

                        $methodAuthAttr = $method->getAttributes(\Framework\Http\Auth::class);
                        $auth = !empty($methodAuthAttr) ? $methodAuthAttr[0]->newInstance() : $classAuth;

                        if ($auth && $auth->required && !$this->loginManager->isLoggedIn()) {
                            throw new ControllerException("Unauthorized access - login required");
                        }

                        $this->runMethod($method, $params, $instanceToScan);
                        return;
                    }
                }
            }
        }

        throw new ControllerException("Route not found: '{$requestedTarget}'");
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
