<?php

declare(strict_types=1);

namespace Tests\Framework\Http;

use Framework\Http\RequestHelper;
use PHPUnit\Framework\TestCase;

final class RequestHelperTest extends TestCase
{
    protected function setUp(): void
    {
        $_GET = [];
        $_POST = [];
        $_COOKIE = [];
        $_SERVER['REQUEST_URI'] = '/';
    }

    public function testGetPostCookieAndHasMethods(): void
    {
        $_GET['q'] = '<b>x</b>';
        $_POST['name'] = '<script>alert(1)</script>';
        $_COOKIE['token'] = '<i>abc</i>';

        $helper = new RequestHelper();

        $this->assertTrue($helper->hasGet('q'));
        $this->assertTrue($helper->hasPost('name'));
        $this->assertTrue($helper->hasCookie('token'));

        $this->assertSame('&lt;b&gt;x&lt;/b&gt;', $helper->get('q'));
        $this->assertSame('&lt;script&gt;alert(1)&lt;/script&gt;', $helper->post('name'));
        $this->assertSame('&lt;i&gt;abc&lt;/i&gt;', $helper->cookie('token'));
    }

    public function testRawAppliesAntiXssAndAllowsNullDefault(): void
    {
        $_POST['html'] = '<a href="javascript:alert(1)" onclick="x()">ok</a>';
        $helper = new RequestHelper();

        $this->assertSame('<a href="#">ok</a>', $helper->raw('html'));
        $this->assertNull($helper->raw('missing'));
    }

    public function testGetPathTrimsTrailingSlashAndSanitizes(): void
    {
        $_SERVER['REQUEST_URI'] = '/admin/<b>panel</b>/';
        $helper = new RequestHelper();

        $this->assertSame('/admin/&lt;b&gt;panel&lt;/b&gt;', $helper->getPath());
    }
}
