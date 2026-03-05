<?php

declare(strict_types=1);

namespace Tests\Framework\Exceptions;

use Framework\Exceptions\AutoloadException;
use Framework\Exceptions\BootException;
use Framework\Exceptions\ConfigException;
use Framework\Exceptions\ContainerException;
use Framework\Exceptions\ContainerInitializationException;
use Framework\Exceptions\ControllerException;
use Framework\Exceptions\DTOCacheException;
use Framework\Exceptions\JSONHandlerException;
use Framework\Exceptions\ModelException;
use Framework\Exceptions\MysqlException;
use Framework\Exceptions\RouterException;
use Framework\Exceptions\RouterInitializationException;
use Framework\Exceptions\UploaderException;
use Framework\Exceptions\ViewException;
use PHPUnit\Framework\TestCase;

final class ExceptionHierarchyTest extends TestCase
{
    public function testBootDerivedExceptionsInheritFromBootException(): void
    {
        $this->assertInstanceOf(BootException::class, new AutoloadException('x'));
        $this->assertInstanceOf(BootException::class, new ContainerInitializationException('x'));
        $this->assertInstanceOf(BootException::class, new DTOCacheException('x'));
        $this->assertInstanceOf(BootException::class, new RouterInitializationException('x'));
    }

    public function testDomainExceptionsAreThrowableAndKeepMessage(): void
    {
        $cases = [
            new ConfigException('cfg'),
            new ContainerException('container'),
            new ControllerException('controller'),
            new JSONHandlerException('json'),
            new ModelException('model'),
            new MysqlException('mysql'),
            new RouterException('router'),
            new UploaderException('uploader'),
            new ViewException('view'),
        ];

        foreach ($cases as $ex) {
            $this->assertInstanceOf(\Exception::class, $ex);
            $this->assertNotSame('', $ex->getMessage());
        }
    }
}
