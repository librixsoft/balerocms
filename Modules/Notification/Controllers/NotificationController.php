<?php

namespace Modules\Notification\Controllers;

use Framework\Core\Controller;
use Framework\Http\Get;
use Framework\Http\Post;
use Framework\Static\Flash;

class NotificationController extends Controller
{

    #[Get('/')]
    public function getNotification()
    {
        return "/notification endpoint is up!";
    }

    #[Post('/')]
    public function postNotification()
    {
        $key = $this->request->post('key');

        Flash::delete($key);
        $status = 'success';
        $message = "Key '$key' deleted success.";

        header('Content-Type: application/json');
        return json_encode([
            'status' => $status,
            'message' => $message
        ]);
    }

}
