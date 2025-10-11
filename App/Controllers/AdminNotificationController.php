<?php

namespace App\Controllers;

use Framework\Attributes\Controller;
use Framework\Attributes\FlashStorage;
use Framework\Attributes\Inject;
use Framework\Http\Auth;
use Framework\Http\Get;
use Framework\Http\Post;
use Framework\Http\JsonResponse;
use Framework\Http\RequestHelper;
use Framework\Utils\Flash;

#[Controller('/admin/notification')]
#[Auth(required: true)]
class AdminNotificationController
{
    #[Inject]
    private RequestHelper $requestHelper;

    #[Inject]
    #[FlashStorage('_admin_flash')]
    private Flash $flash;

    /**
     * Clave del namespace para separar notificaciones del admin del frontend
     */
    private const ADMIN_FLASH_KEY = '_admin_flash';

    #[Get('/')]
    #[JsonResponse]
    public function getNotification()
    {
        // Obtiene solo los valores del admin, no del frontend
        $adminFlash = $_SESSION[self::ADMIN_FLASH_KEY] ?? [];

        // Si no hay nada, devolver vacío
        if (empty($adminFlash)) {
            return [
                'status' => 'empty',
                'data' => []
            ];
        }

        return [
            'status' => 'ok',
            'data' => $adminFlash
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