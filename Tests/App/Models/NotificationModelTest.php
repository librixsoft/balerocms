<?php

declare(strict_types=1);

namespace Tests\App\Models;

use App\Models\NotificationModel;
use Framework\Core\Model;
use PHPUnit\Framework\TestCase;

final class NotificationModelTest extends TestCase
{
    public function testConnectReturnsSuccess(): void
    {
        $m = new NotificationModel($this->createMock(Model::class));
        $this->assertSame('success', $m->connect());
    }
}
