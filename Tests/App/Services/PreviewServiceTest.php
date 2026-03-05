<?php

declare(strict_types=1);

namespace Tests\App\Services;

use App\Services\PreviewService;
use Framework\Preview\PreviewGenerator;
use PHPUnit\Framework\TestCase;

final class PreviewServiceTest extends TestCase
{
    public function testServeOgImageDelegatesTitleToGenerator(): void
    {
        $generator = $this->createMock(PreviewGenerator::class);
        $generator->expects($this->once())->method('render')->with('Page title');

        $service = new PreviewService();
        $r = new \ReflectionClass($service);
        $p = $r->getProperty('generator');
        $p->setAccessible(true);
        $p->setValue($service, $generator);

        $service->serveOgImage(['virtual_title' => 'Page title']);
    }
}
