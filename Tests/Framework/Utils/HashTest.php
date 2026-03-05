<?php

declare(strict_types=1);

namespace Tests\Framework\Utils;

use Framework\Utils\Hash;
use PHPUnit\Framework\TestCase;

final class HashTest extends TestCase
{
    public function testGenpwdAndVerifyHash(): void
    {
        $hash = new Hash();
        $encoded = $hash->genpwd('secret123');

        $this->assertNotSame('secret123', $encoded);
        $this->assertTrue($hash->verify_hash('secret123', $encoded));
        $this->assertFalse($hash->verify_hash('wrong', $encoded));
    }
}
