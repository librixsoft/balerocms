<?php

declare(strict_types=1);

namespace Tests\Framework\Http;

use Framework\Http\Get;
use PHPUnit\Framework\TestCase;

class GetDummy
{
    #[Get('/admin/list')]
    public function index(): void {}
}

final class GetTest extends TestCase
{
    public function testGetTargetStored(): void
    {
        $attr = (new \ReflectionMethod(GetDummy::class, 'index'))->getAttributes(Get::class)[0]->newInstance();
        $this->assertSame('/admin/list', $attr->target);
    }
}
