<?php

namespace App\Controllers\Admin;

use App\Services\AdminService;
use App\Services\UploaderService;
use Framework\Attributes\Controller;
use Framework\Attributes\Inject;
use Framework\Attributes\FlashStorage;
use Framework\Http\Get;
use Framework\Http\Post;
use Framework\Http\JsonResponse;
use Framework\Http\Auth;
use Framework\Http\RequestHelper;
use Framework\Core\View;
use Framework\Utils\Flash;
use Framework\Utils\Redirect;

#[Controller('/admin')]
#[Auth(required: true)]
class AdminMediaController
{
    #[Inject]
    private AdminService $adminService;

    #[Inject]
    private UploaderService $uploaderService;

    #[Inject]
    private View $view;

    #[Inject]
    private RequestHelper $request;

    #[Inject]
    #[FlashStorage]
    private Flash $flash;

    #[Inject]
    private Redirect $redirect;

    #[Post('/uploader')]
    #[JsonResponse]
    public function postUploader()
    {
        $file = $_FILES['file'] ?? null;

        $meta = [
            'original_name' => $this->request->post('meta_original_name') ?? '',
            'size'          => (int) ($this->request->post('meta_size') ?? 0),
            'mime'          => $this->request->post('meta_mime') ?? '',
            'uploaded_at'   => $this->request->post('meta_uploaded_at') ?? date('c'),
            'context'       => $this->request->post('meta_context') ?? 'unknown',
        ];

        try {
            $metadata = $this->uploaderService->uploadImage($file, $meta);
            $this->adminService->saveMediaMetadata($metadata);
            return ['status' => 'ok', 'url' => $metadata['url']];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    #[Get('/media')]
    public function getMediaList()
    {
        $params = $this->adminService->getMediaViewParams();
        return $this->view->render("admin/dashboard.html", $params, false);
    }

    #[Post('/media/delete/{name}')]
    public function deleteMedia(string $name)
    {
        try {
            $this->adminService->deleteMedia($name);
        } catch (\Framework\Exceptions\UploaderException $e) {
            $this->flash->set("danger", $e->getMessage());
        } catch (\Throwable $e) {
            $this->flash->set("danger", "Unknown error occurred while trying to delete media.");
        }

        $this->redirect->to('/admin/media');
    }
}
