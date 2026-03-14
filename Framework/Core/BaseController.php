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
use Framework\Attributes\Controller as ControllerAttr;
use Framework\Http\Auth as AuthAttr;
use ReflectionClass;
use ReflectionMethod;

class BaseController
{

    private RequestHelper $requestHelper;
    private ConfigSettings $configSettings;
    private LoginManager $loginManager;
    private LangSelector $langSelector;

    /** Cache para metadata de controladores */
    private array $metadataCache = [];

    public function __construct(
        RequestHelper $requestHelper,
        ConfigSettings $configSettings,
        LoginManager $loginManager,
        LangSelector $langSelector
    ) {
        $this->requestHelper = $requestHelper;
        $this->configSettings = $configSettings;
        $this->loginManager = $loginManager;
        $this->langSelector = $langSelector;
    }

    /**
     * --- Helpers de metadata del #[Controller] ---
     */

    public function extractControllerMetadata(string $className): object
    {
        if (isset($this->metadataCache[$className])) {
            return $this->metadataCache[$className];
        }

        $reflector = new ReflectionClass($className);

        // Controller::pathUrl
        $classAttrs = $reflector->getAttributes(ControllerAttr::class);
        $pathUrl = '/';
        if (!empty($classAttrs)) {
            $pathUrl = rtrim($classAttrs[0]->newInstance()->pathUrl, '/') ?: '/';
        }

        // Auth a nivel de clase (opcional)
        $authAttrs = $reflector->getAttributes(AuthAttr::class);
        $classAuth = !empty($authAttrs) ? $authAttrs[0]->newInstance() : null;

        $meta = (object)[
            'class'   => $className,
            'pathUrl' => $pathUrl,
            'auth'    => $classAuth,
        ];

        return $this->metadataCache[$className] = $meta;
    }

    public function getControllerMetadata(string $className): object
    {
        return $this->extractControllerMetadata($className);
    }

    /**
     * Inicializa el Controller y ejecuta la ruta correspondiente.
     * Llamado desde Router::class
     * @param object|null $controllerInstance Instancia de ModuleController opcional.
     */
    public function initControllerAndRoute(?object $controllerInstance = null): void
    {

        $httpMethod    = $_SERVER['REQUEST_METHOD'];
        $requestedPath = $this->requestHelper->getPath();

        $instanceToScan = $controllerInstance ?? $this;
        $className      = get_class($instanceToScan);

        $metadata  = $this->getControllerMetadata($className);
        $pathUrl   = $metadata->pathUrl;
        $classAuth = $metadata->auth;

        $reflection = new ReflectionClass($instanceToScan);
        $methods    = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

        foreach ($methods as $method) {
            foreach ($method->getAttributes() as $attribute) {
                $attrName      = $attribute->getName();
                $routeInstance = $attribute->newInstance();

                if (
                    ($attrName === Get::class && $httpMethod === 'GET') ||
                    ($attrName === Post::class && $httpMethod === 'POST')
                ) {
                    // Combinar path base del controller + target del método
                    $routePath = rtrim($pathUrl, '/') . '/' . ltrim($routeInstance->target, '/');
                    $routePath = rtrim($routePath, '/');

                    // Extraer parámetros dinámicos {param}
                    $pattern = preg_replace('#\{([^/]+)\}#', '(?P<$1>[^/]+)', $routePath);
                    $pattern = '#^' . $pattern . '$#';

                    if (preg_match($pattern, $requestedPath, $matches)) {
                        // Extraer parámetros nombrados
                        $params = array_filter(
                            $matches,
                            fn($key) => !is_int($key),
                            ARRAY_FILTER_USE_KEY
                        );

                        // Auth de método o clase
                        $methodAuthAttr = $method->getAttributes(AuthAttr::class);
                        $auth = !empty($methodAuthAttr)
                            ? $methodAuthAttr[0]->newInstance()
                            : $classAuth;

                        if ($auth && $auth->required && !$this->loginManager->isLoggedIn()) {
                            throw new ControllerException("Unauthorized access - login required");
                        }

                        $this->runMethod($method, $params, $instanceToScan);
                        return;
                    }
                }
            }
        }

        throw new ControllerException("Route not found: '{$requestedPath}'");
    }

    public function runMethod(ReflectionMethod $method, array $params = [], ?object $controllerInstance = null): void
    {
        $controllerInstance ??= $this;
        $this->initLanguage();

        // Ajustar parámetros por nombre
        $methodParams = [];
        foreach ($method->getParameters() as $param) {
            $name = $param->getName();
            $methodParams[] = $params[$name] ?? null;
        }

        $result = $method->invoke($controllerInstance, ...$methodParams);

        $jsonAttribute = $method->getAttributes(JsonResponse::class);

        if (!empty($jsonAttribute)) {
            if (is_array($result) || is_object($result)) {
                header('Content-Type: application/json');
                echo json_encode($result);
                return;
            }

            header('Content-Type: application/json', true, 500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Controller marked for JSON response did not return an array or object.'
            ]);
            return;
        }

        if (is_string($result)) {
            echo $result;
        }

    }

    public function initLanguage(): void
    {
        if ($this->requestHelper) {
            $this->langSelector->getLanguageParams($this->requestHelper);
        }
    }
}
