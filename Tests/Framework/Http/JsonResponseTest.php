<?php

declare(strict_types=1);

namespace Tests\Framework\Http;

use Framework\Http\JsonResponse;
use PHPUnit\Framework\TestCase;

class JsonResponseDummyController
{
    #[JsonResponse]
    public function index(): array
    {
        return ['ok' => true];
    }
}

final class JsonResponseTest extends TestCase
{
    public function testJsonResponseAttributeCanBeAppliedToMethod(): void
    {
        $m = new \ReflectionMethod(JsonResponseDummyController::class, 'index');
        $attrs = $m->getAttributes(JsonResponse::class);

        $this->assertCount(1, $attrs);
    }
}
