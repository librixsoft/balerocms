<?php

declare(strict_types=1);

namespace Tests\Framework\Core;

use Framework\Attributes\Controller;
use Framework\Core\BaseController;
use Framework\Core\ConfigSettings;
use Framework\Exceptions\ControllerException;
use Framework\Http\Auth;
use Framework\Http\Get;
use Framework\Http\JsonResponse;
use Framework\Http\Post;
use Framework\Http\RequestHelper;
use Framework\I18n\LangSelector;
use Framework\Security\LoginManager;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

#[Controller('/demo')]
class DemoControllerForMetadata {}

class ControllerWithoutAttribute {}

#[Controller('/secured')]
#[Auth]
class ControllerWithAuthAttribute {}

#[Controller('/demo')]
class RouteController
{
    public ?string $capturedId = null;
    public bool $ranPost = false;

    #[Get('/item/{id}')]
    public function show(string $id): void
    {
        $this->capturedId = $id;
    }

    #[Post('/submit')]
    public function submit(): void
    {
        $this->ranPost = true;
    }
}

#[Controller('/demo')]
class AuthRouteController
{
    #[Get('/item/{id}')]
    #[Auth]
    public function show(string $id): void
    {
    }
}

final class BaseControllerTest extends TestCase
{
    public function testExtractControllerMetadataUsesAttributeAndCaches(): void
    {
        $bc = new BaseController(
            $this->createMock(RequestHelper::class),
            $this->createMock(ConfigSettings::class),
            $this->createMock(LoginManager::class),
            $this->createMock(LangSelector::class)
        );

        $m1 = $bc->getControllerMetadata(DemoControllerForMetadata::class);
        $m2 = $bc->getControllerMetadata(DemoControllerForMetadata::class);

        $this->assertSame('/demo', $m1->pathUrl);
        $this->assertSame($m1, $m2);
    }

    public function testExtractControllerMetadataDefaultsWhenNoAttribute(): void
    {
        $bc = new BaseController(
            $this->createMock(RequestHelper::class),
            $this->createMock(ConfigSettings::class),
            $this->createMock(LoginManager::class),
            $this->createMock(LangSelector::class)
        );

        $meta = $bc->getControllerMetadata(ControllerWithoutAttribute::class);

        $this->assertSame('/', $meta->pathUrl);
        $this->assertNull($meta->auth);
    }

    public function testExtractControllerMetadataIncludesAuthAttribute(): void
    {
        $bc = new BaseController(
            $this->createMock(RequestHelper::class),
            $this->createMock(ConfigSettings::class),
            $this->createMock(LoginManager::class),
            $this->createMock(LangSelector::class)
        );

        $meta = $bc->getControllerMetadata(ControllerWithAuthAttribute::class);

        $this->assertSame('/secured', $meta->pathUrl);
        $this->assertInstanceOf(Auth::class, $meta->auth);
        $this->assertTrue($meta->auth->required);
    }

    public function testInitControllerAndRouteMatchesGetAndBindsParams(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $requestHelper = $this->createMock(RequestHelper::class);
        $requestHelper->method('getPath')->willReturn('/demo/item/123');

        $loginManager = $this->createMock(LoginManager::class);
        $loginManager->method('isLoggedIn')->willReturn(true);

        $langSelector = $this->createMock(LangSelector::class);
        $langSelector->expects($this->once())
            ->method('getLanguageParams')
            ->with($requestHelper);

        $bc = new BaseController(
            $requestHelper,
            $this->createMock(ConfigSettings::class),
            $loginManager,
            $langSelector
        );

        $controller = new RouteController();
        $bc->initControllerAndRoute($controller);

        $this->assertSame('123', $controller->capturedId);
    }

    public function testInitControllerAndRouteMatchesPost(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $requestHelper = $this->createMock(RequestHelper::class);
        $requestHelper->method('getPath')->willReturn('/demo/submit');

        $loginManager = $this->createMock(LoginManager::class);
        $loginManager->method('isLoggedIn')->willReturn(true);

        $langSelector = $this->createMock(LangSelector::class);
        $langSelector->expects($this->once())
            ->method('getLanguageParams')
            ->with($requestHelper);

        $bc = new BaseController(
            $requestHelper,
            $this->createMock(ConfigSettings::class),
            $loginManager,
            $langSelector
        );

        $controller = new RouteController();
        $bc->initControllerAndRoute($controller);

        $this->assertTrue($controller->ranPost);
    }

    public function testInitControllerAndRouteThrowsWhenUnauthorized(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $requestHelper = $this->createMock(RequestHelper::class);
        $requestHelper->method('getPath')->willReturn('/demo/item/123');

        $loginManager = $this->createMock(LoginManager::class);
        $loginManager->method('isLoggedIn')->willReturn(false);

        $bc = new BaseController(
            $requestHelper,
            $this->createMock(ConfigSettings::class),
            $loginManager,
            $this->createMock(LangSelector::class)
        );

        $controller = new AuthRouteController();

        $this->expectException(ControllerException::class);
        $this->expectExceptionMessage('Unauthorized access');

        $bc->initControllerAndRoute($controller);
    }

    public function testInitControllerAndRouteThrowsWhenRouteNotFound(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $requestHelper = $this->createMock(RequestHelper::class);
        $requestHelper->method('getPath')->willReturn('/demo/unknown');

        $bc = new BaseController(
            $requestHelper,
            $this->createMock(ConfigSettings::class),
            $this->createMock(LoginManager::class),
            $this->createMock(LangSelector::class)
        );

        $controller = new RouteController();

        $this->expectException(ControllerException::class);
        $this->expectExceptionMessage("Route not found");

        $bc->initControllerAndRoute($controller);
    }

    public function testRunMethodInitLanguageInvoked(): void
    {
        $requestHelper = $this->createMock(RequestHelper::class);

        $langSelector = $this->createMock(LangSelector::class);
        $langSelector->expects($this->once())
            ->method('getLanguageParams')
            ->with($requestHelper);

        $bc = new BaseController(
            $requestHelper,
            $this->createMock(ConfigSettings::class),
            $this->createMock(LoginManager::class),
            $langSelector
        );

        $controller = new class {
            public bool $ran = false;

            public function noop(): void
            {
                $this->ran = true;
            }
        };

        $method = new ReflectionMethod($controller, 'noop');
        $bc->runMethod($method, [], $controller);

        $this->assertTrue($controller->ran);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testRunMethodJsonResponseOutputsJson(): void
    {
        $bc = new BaseController(
            $this->createMock(RequestHelper::class),
            $this->createMock(ConfigSettings::class),
            $this->createMock(LoginManager::class),
            $this->createMock(LangSelector::class)
        );

        $controller = new class {
            #[JsonResponse]
            public function data(): array
            {
                return ['status' => 'ok'];
            }
        };

        $method = new ReflectionMethod($controller, 'data');

        $this->expectOutputString('{"status":"ok"}');

        $bc->runMethod($method, [], $controller);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testRunMethodJsonResponseInvalidReturnOutputsError(): void
    {
        $bc = new BaseController(
            $this->createMock(RequestHelper::class),
            $this->createMock(ConfigSettings::class),
            $this->createMock(LoginManager::class),
            $this->createMock(LangSelector::class)
        );

        $controller = new class {
            #[JsonResponse]
            public function data(): string
            {
                return 'nope';
            }
        };

        $method = new ReflectionMethod($controller, 'data');

        $this->expectOutputString('{"status":"error","message":"Controller marked for JSON response did not return an array or object."}');

        $bc->runMethod($method, [], $controller);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testRunMethodStringResponseOutputsString(): void
    {
        $bc = new BaseController(
            $this->createMock(RequestHelper::class),
            $this->createMock(ConfigSettings::class),
            $this->createMock(LoginManager::class),
            $this->createMock(LangSelector::class)
        );

        $controller = new class {
            public function hello(): string
            {
                return 'hello';
            }
        };

        $method = new ReflectionMethod($controller, 'hello');

        $this->expectOutputString('hello');

        $bc->runMethod($method, [], $controller);
    }
}
