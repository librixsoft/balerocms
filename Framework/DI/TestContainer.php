<?php

namespace Framework\DI;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionNamedType;
use Framework\Attributes\InjectMocks;
use Framework\Attributes\Inject;

class TestContainer
{
    private array $mocks = [];
    private TestCase $testCase;

    public function __construct(TestCase $testCase)
    {
        $this->testCase = $testCase;
    }

    /**
     * Initializes all test properties marked with #[InjectMocks]
     *
     * @param object $test PHPUnit TestCase instance
     * @return void
     * @throws \RuntimeException
     */
    public function initTest(object $test): void
    {
        try {
            $reflectTest = new ReflectionClass($test);

            foreach ($reflectTest->getProperties() as $prop) {
                $attrs = $prop->getAttributes(InjectMocks::class);
                if (empty($attrs)) continue;

                $sutType = $prop->getType();
                if (!$sutType instanceof ReflectionNamedType || $sutType->isBuiltin()) {
                    throw new \RuntimeException(
                        "InjectMocks requires a valid class type on property '{$prop->getName()}'"
                    );
                }

                $sutClass = $sutType->getName();
                if (!class_exists($sutClass)) {
                    throw new \RuntimeException(
                        "Class '{$sutClass}' does not exist for property '{$prop->getName()}'"
                    );
                }

                $sut = $this->createWithMocks($sutClass);

                $prop->setAccessible(true);
                $prop->setValue($test, $sut);
            }
        } catch (\Throwable $e) {
            throw new \RuntimeException(
                "Failed to initialize test container: " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Returns a mock instance or previously generated mock
     *
     * @param string $className
     * @return object
     */
    public function get(string $className): object
    {
        if (isset($this->mocks[$className])) {
            return $this->mocks[$className];
        }
        $this->mocks[$className] = $this->createMock($className);
        return $this->mocks[$className];
    }

    /**
     * Creates the System Under Test (SUT) with mocked dependencies
     *
     * @param string $className
     * @return object
     */
    public function createWithMocks(string $className): object
    {
        $factory = new DependencyFactory($this);
        return $factory->create($className);
    }

    /**
     * Creates a PHPUnit mock for the given class
     *
     * @param string $className
     * @return object
     */
    private function createMock(string $className): object
    {
        $reflection = new ReflectionClass($this->testCase);
        $method = $reflection->getMethod('createMock');
        $method->setAccessible(true);

        return $method->invoke($this->testCase, $className);
    }

    /**
     * Returns a previously generated mock or null
     *
     * @param string $class Class name of the mock
     * @return object|null
     */
    public function getMock(string $class): ?object
    {
        return $this->mocks[$class] ?? null;
    }
}
