<?php

declare(strict_types=1);

namespace Tests\Framework\Attributes;

use Framework\Attributes\Controller;
use PHPUnit\Framework\TestCase;

#[Controller('/admin')]
class ControllerDummy {}

final class ControllerTest extends TestCase
{
    public function testControllerAttributePath(): void
    {
        $attr = (new \ReflectionClass(ControllerDummy::class))->getAttributes(Controller::class)[0]->newInstance();
        $this->assertSame('/admin', $attr->pathUrl);
    }
}
