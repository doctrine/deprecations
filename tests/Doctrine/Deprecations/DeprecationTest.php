<?php

declare(strict_types=1);

namespace Doctrine\Deprecations;

use PHPUnit\Framework\Error\Deprecated;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionProperty;
use Throwable;

use function method_exists;

class DeprecationTest extends TestCase
{
    public function setUp(): void
    {
        // reset the global state of Deprecation class accross tests
        $reflectionProperty = new ReflectionProperty(Deprecation::class, 'ignoredPackages');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue([]);

        $reflectionProperty = new ReflectionProperty(Deprecation::class, 'ignoredLinks');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue([]);
    }

    public function expectDeprecation(): void
    {
        if (method_exists(TestCase::class, 'expectDeprecation')) {
            parent::expectDeprecation();
        } else {
            parent::expectException(Deprecated::class);
        }
    }

    public function expectDeprecationMessage(string $message): void
    {
        if (method_exists(TestCase::class, 'expectDeprecationMessage')) {
            parent::expectDeprecationMessage($message);
        } else {
            parent::expectExceptionMessage($message);
        }
    }

    public function testDeprecation(): void
    {
        Deprecation::enableWithTriggerError();

        $this->expectDeprecation();
        $this->expectDeprecationMessage('this is deprecated foo 1234 (DeprecationTest.php');

        try {
            Deprecation::trigger(
                'doctrine/orm',
                'https://github.com/doctrine/deprecations/1234',
                'this is deprecated %s %d',
                'foo',
                1234
            );
        } catch (Throwable $e) {
            $this->assertEquals(1, Deprecation::getUniqueTriggeredDeprecationsCount());
            $this->assertEquals(['https://github.com/doctrine/deprecations/1234' => 1], Deprecation::getTriggeredDeprecations());

            throw $e;
        }
    }

    public function testDeprecationResetsCounts(): void
    {
        try {
            Deprecation::trigger(
                'doctrine/orm',
                'https://github.com/doctrine/deprecations/1234',
                'this is deprecated %s %d',
                'foo',
                1234
            );
        } catch (Throwable $e) {
            Deprecation::disable();

            $this->assertEquals(0, Deprecation::getUniqueTriggeredDeprecationsCount());
            $this->assertEquals(['https://github.com/doctrine/deprecations/1234' => 0], Deprecation::getTriggeredDeprecations());
        }
    }

    public function testDeprecationWithPsrLogger(): void
    {
        $mock = $this->createMock(LoggerInterface::class);
        $mock->method('notice')->with('this is deprecated foo 1234', $this->callback(function ($context) {
            $this->assertEquals(__FILE__, $context['file']);
            $this->assertEquals('doctrine/orm', $context['package']);
            $this->assertEquals('https://github.com/doctrine/deprecations/2222', $context['link']);

            return true;
        }));

        Deprecation::enableWithPsrLogger($mock);

        Deprecation::trigger(
            'doctrine/orm',
            'https://github.com/doctrine/deprecations/2222',
            'this is deprecated %s %d',
            'foo',
            1234
        );
    }

    public function testDeprecationWithIgnoredPackage(): void
    {
        Deprecation::enableWithTriggerError();
        Deprecation::ignorePackage('doctrine/orm');

        Deprecation::trigger(
            'doctrine/orm',
            'https://github.com/doctrine/orm/issue/1234',
            'this is deprecated %s %d',
            'foo',
            1234
        );

        $this->assertEquals(1, Deprecation::getUniqueTriggeredDeprecationsCount());
        $this->assertEquals(['https://github.com/doctrine/orm/issue/1234' => 1], Deprecation::getTriggeredDeprecations());
    }
}
