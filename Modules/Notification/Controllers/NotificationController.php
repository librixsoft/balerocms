<?php

namespace Modules\Notification\Controllers;

use Framework\Core\Controller;
use Framework\Http\Get;
use Framework\Http\Post;
use Framework\Http\JsonResponse;
use Framework\Static\Flash;

class NotificationController extends Controller
{

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
        $key = $this->request->post('key');

        Flash::delete($key);
        $status = 'success';
        $message = "Key '$key' deleted success.";

        return [
            'status' => $status,
            'message' => $message
        ];
    }

}
