<?php

declare(strict_types=1);

namespace Tests\Framework\Rendering;

use Framework\Rendering\ProcessorVariables;
use Framework\Security\Security;
use PHPUnit\Framework\TestCase;

final class ProcessorVariablesTest extends TestCase
{
    public function testProcessReplacesSimpleVariables(): void
    {
        $p = new ProcessorVariables(new Security());
        $out = $p->process('Hi {name}, id={id}', ['name' => 'Ana', 'id' => 5]);
        $this->assertSame('Hi Ana, id=5', $out);
    }
}
