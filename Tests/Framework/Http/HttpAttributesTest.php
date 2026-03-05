<?php

declare(strict_types=1);

namespace Tests\Framework\Http;

use Framework\Http\Auth;
use Framework\Http\Get;
use Framework\Http\JsonResponse;
use Framework\Http\Post;
use PHPUnit\Framework\TestCase;

final class HttpAttributesTest extends TestCase
{
    public function testGetStoresTargetAsProvided(): void
    {
        $get = new Get('/admin/pages');
        $this->assertSame('/admin/pages', $get->target);
    }

    public function testPostNormalizesTargetByTrimmingSlashes(): void
    {
        $post = new Post('/admin/pages/');
        $this->assertSame('admin/pages', $post->target);
    }

    public function testAuthDefaultAndCustomRequiredFlag(): void
    {
        $this->assertTrue((new Auth())->required);
        $this->assertFalse((new Auth(false))->required);
    }

    public function testJsonResponseIsInstantiableAttributeMarker(): void
    {
        $json = new JsonResponse();
        $this->assertInstanceOf(JsonResponse::class, $json);
    }
}
