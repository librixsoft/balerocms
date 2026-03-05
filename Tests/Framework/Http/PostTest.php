<?php

declare(strict_types=1);

namespace Tests\Framework\Http;

use Framework\Http\Post;
use PHPUnit\Framework\TestCase;

class PostDummy
{
    #[Post('/admin/save/')]
    public function save(): void {}
}

final class PostTest extends TestCase
{
    public function testPostNormalizesTarget(): void
    {
        $attr = (new \ReflectionMethod(PostDummy::class, 'save'))->getAttributes(Post::class)[0]->newInstance();
        $this->assertSame('admin/save', $attr->target);
    }
}
