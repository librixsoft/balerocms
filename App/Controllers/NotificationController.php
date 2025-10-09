<?php

namespace App\Controllers;

use App\Models\NotificationModel;
use Framework\Attributes\Controller;
use Framework\Attributes\Inject;
use Framework\Http\Get;
use Framework\Http\Post;
use Framework\Http\JsonResponse;
use Framework\Http\RequestHelper;
use Framework\Utils\Flash;

#[Controller('/notification')]
class NotificationController
{

    #[Inject]
    private RequestHelper $requestHelper;

    #[Inject]
    private NotificationModel $model;

    #[Inject]
    private Flash $flash;

    #[Get('/')]
    #[JsonResponse]
    public function getNotification()
    {
        return [
            'status' => 'success',
            'message' => "Endpoint /notification is up."
        ];
    }

    #[Post('/')]
    #[JsonResponse]
    public function postNotification()
    {
        $key = $this->requestHelper->post('key');

        $this->flash->delete($key);
        $status = 'success';
        $message = "Key '$key' deleted success.";

        return [
            'status' => $status,
            'message' => $message
        ];
    }

}
