<?php

declare(strict_types=1);

namespace Tests\Framework\Rendering;

use Framework\Rendering\ProcessorFlattenParams;
use PHPUnit\Framework\TestCase;

final class ProcessorFlattenParamsTest extends TestCase
{
    public function testProcessFlattensNestedArrays(): void
    {
        $p = new ProcessorFlattenParams();
        $flat = $p->process(['errors' => ['username' => 'Required'], 'title' => 'X']);

        $this->assertSame('Required', $flat['errors.username']);
        $this->assertSame('X', $flat['title']);
    }
}
