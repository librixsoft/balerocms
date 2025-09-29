<?php

/**
 * Balero CMS
 * @author Anibal Gomez <balerocms@gmail.com>
 * @license GNU General Public License
 */

namespace Framework\Core;

use Framework\Http\Get;
use Framework\Http\Post;
use Framework\Http\RequestHelper;
use Framework\I18n\LangSelector;
use Framework\Security\LoginManager;
use Framework\Exceptions\ControllerException;
use ReflectionClass;
use ReflectionMethod;

class Controller
{
    private const PARAM_TARGET = 'target';

    /**
     * Will be inherited by child controllers
     */
    #[Inject]
    protected View $view;

    #[Inject]
    protected RequestHelper $request;

    #[Inject]
    protected ConfigSettings $configSettings;

    #[Inject]
    protected LoginManager $loginManager;

    #[Inject]
    protected LangSelector $langSelector;

    /**
     * Builds the base template of Balero CMS controllers
     */
    public function initControllerAndInject(): void
    {
        $this->run();
    }

    public function run(): void
    {
        $this->initBasePath();

        $httpMethod = $_SERVER['REQUEST_METHOD'];
        $requestedTarget = trim($this->request->get(self::PARAM_TARGET) ?? '', '/');

        $reflection = new ReflectionClass($this);
        $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

        $classAuthAttr = $reflection->getAttributes(\Framework\Http\Auth::class);
        $classAuth = !empty($classAuthAttr) ? $classAuthAttr[0]->newInstance() : null;

        foreach ($methods as $method) {
            foreach ($method->getAttributes() as $attribute) {
                $attrName = $attribute->getName();
                $instance = $attribute->newInstance();

                if (
                    ($attrName === Get::class && $httpMethod === 'GET') ||
                    ($attrName === Post::class && $httpMethod === 'POST')
                ) {
                    $routePattern = trim($instance->target, '/');
                    $regex = preg_replace('#\{([^}]+)\}#', '(?P<$1>[^/]+)', $routePattern);
                    $regex = '#^' . $regex . '$#';

                    if (preg_match($regex, $requestedTarget, $matches)) {
                        $params = array_filter($matches, fn($key) => !is_int($key), ARRAY_FILTER_USE_KEY);

                        $methodAuthAttr = $method->getAttributes(\Framework\Http\Auth::class);
                        $auth = !empty($methodAuthAttr) ? $methodAuthAttr[0]->newInstance() : $classAuth;

                        if ($auth && $auth->required && !$this->loginManager->isLoggedIn()) {
                            throw new ControllerException("Unauthorized access - login required");
                        }

                        $this->runMethod($method->getName(), $params);
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

    private function runMethod(string $methodName, array $params = []): void
    {
        $this->initLanguage();

        $result = $this->{$methodName}(...$params);

        if (is_string($result)) {
            echo $result;
            exit;
        }

        if (is_array($result) && isset($result['view'])) {
            echo $this->render($result['view'], $result['params'] ?? []);
            exit;
        }
    }

    protected function initLanguage(): void
    {
        if ($this->request) {
            $this->langSelector->getLanguageParams($this->request);
        }
    }

    protected function render(string $template, array $params = [], bool $useTheme = true): string
    {
        $langParams = $this->langSelector->getLanguageParams($this->request);

        return $this->view->render($template, array_merge($langParams, $params), $useTheme);
    }
}
