<?php

declare(strict_types=1);

namespace Tests\Framework\Attributes;

use Framework\Attributes\Controller;
use Framework\Attributes\DTO;
use Framework\Attributes\FlashStorage;
use Framework\Attributes\Inject;
use Framework\Attributes\InjectMocks;
use Framework\Attributes\Service;
use Framework\Attributes\SetupTestContainer;
use PHPUnit\Framework\TestCase;

final class CoreAttributesTest extends TestCase
{
    public function testControllerStoresPathUrl(): void
    {
        $this->assertSame('/admin', (new Controller('/admin'))->pathUrl);
        $this->assertSame('/', (new Controller())->pathUrl);
    }

    public function testSetupTestContainerStoresOptionalContainerClass(): void
    {
        $this->assertNull((new SetupTestContainer())->containerClass);
        $this->assertSame('My\\Container', (new SetupTestContainer('My\\Container'))->containerClass);
    }

    public function testMarkerAttributesAreInstantiable(): void
    {
        $this->assertInstanceOf(DTO::class, new DTO());
        $this->assertInstanceOf(FlashStorage::class, new FlashStorage());
        $this->assertInstanceOf(Inject::class, new Inject());
        $this->assertInstanceOf(InjectMocks::class, new InjectMocks());
        $this->assertInstanceOf(Service::class, new Service());
    }
}
