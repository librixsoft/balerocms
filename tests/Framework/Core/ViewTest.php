<?php

namespace Tests\Framework\Core;

use Framework\Core\View;
use PHPUnit\Framework\TestCase;

class ViewTest extends TestCase
{
    public function testNormalizePath(): void
    {
        // Mock de View sin constructor
        $view = $this->getMockBuilder(View::class)
            ->disableOriginalConstructor()
            ->getMock();

        $reflection = new \ReflectionClass($view);
        $method = $reflection->getMethod('normalizePath');
        $method->setAccessible(true);

        // Casos de prueba correctos según la implementación real
        $cases = [
            'folder/subfolder/' => 'folder/subfolder/',
            'folder/subfolder'  => 'folder/subfolder/',
            '/'                 => '/',
            ''                  => '/',
            'folder///'         => 'folder/',
            '///multiple///'    => '///multiple/',
            'no/trailing'       => 'no/trailing/',
            'trailing/'         => 'trailing/',
        ];

        foreach ($cases as $input => $expected) {
            $result = $method->invoke($view, $input);
            $this->assertEquals($expected, $result, "Failed asserting normalizePath('$input')");
        }
    }
}
