<?php

declare(strict_types=1);

namespace Tests\Framework\Core;

use Framework\Attributes\Controller;
use Framework\Core\BaseController;
use Framework\Core\ConfigSettings;
use Framework\Http\RequestHelper;
use Framework\I18n\LangSelector;
use Framework\Security\LoginManager;
use PHPUnit\Framework\TestCase;

#[Controller('/demo')]
class DemoControllerForMetadata {}

final class BaseControllerTest extends TestCase
{
    public function testExtractControllerMetadataUsesAttributeAndCaches(): void
    {
        $bc = new BaseController(
            $this->createMock(RequestHelper::class),
            $this->createMock(ConfigSettings::class),
            $this->createMock(LoginManager::class),
            $this->createMock(LangSelector::class)
        );

        $m1 = $bc->getControllerMetadata(DemoControllerForMetadata::class);
        $m2 = $bc->getControllerMetadata(DemoControllerForMetadata::class);

        $this->assertSame('/demo', $m1->pathUrl);
        $this->assertSame($m1, $m2);
    }
}
