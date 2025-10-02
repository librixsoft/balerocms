<?php

namespace Modules\Test\Controllers;

use Framework\Attributes\Inject;
use Framework\Core\Controller;
use Framework\Core\TestContainer;
use Framework\Http\Get;
use Framework\Http\JsonResponse;
use Modules\Test\Models\TestModel;

class TestController
{

    #[Inject]
    private Controller $controller;

    #[Inject]
    private TestModel $model;

    #[Get('/')]
    public function getNotification()
    {
        return $this->controller->render("test.html", [], useTheme: false);
    }

    #[Get('/debug')]
    #[JsonResponse]
    public function debugMocks()
    {
        // Creamos el contenedor de test
        $container = new TestContainer(fn($class) => new $class());

        // Llamamos debugCreateWithMocks sobre NotificationController
        $debugInfo = $container->debugCreateWithMocks(TestController::class);

        return $debugInfo;
    }

    #[Get('/test')]
    public function getNotification1()
    {
        return $this->controller->render("test.html", [], useTheme: false);
    }

    #[Get('/model-test')]
    #[JsonResponse]
    public function testModelConnectMethod()
    {
        return $this->model->connect();
    }

}
