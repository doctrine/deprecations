<?php

declare(strict_types=1);

namespace Doctrine\Deprecations;

use DeprecationTests\Foo;
use DeprecationTests\IgnoringDeprecations;
use DeprecationTests\RootDeprecation;
use Doctrine\Deprecations\PHPUnit\VerifyDeprecations;
use Doctrine\Foo\Baz;
use PHPUnit\Framework\Error\Deprecated;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionProperty;
use Throwable;

use function method_exists;
use function set_error_handler;

class DeprecationTest extends TestCase
{
    use VerifyDeprecations;

    public function setUp(): void
    {
        // reset the global state of Deprecation class accross tests
        $reflectionProperty = new ReflectionProperty(Deprecation::class, 'ignoredPackages');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue([]);

        $reflectionProperty = new ReflectionProperty(Deprecation::class, 'ignoredLinks');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue([]);

        Deprecation::enableTrackingDeprecations();
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

    public function expectErrorHandler(string $expectedMessage, string $identifier, int $times = 1): void
    {
        set_error_handler(function ($type, $message) use ($expectedMessage, $identifier, $times): void {
            $this->assertStringMatchesFormat(
                $expectedMessage,
                $message
            );
            $this->assertEquals([$identifier => $times], Deprecation::getTriggeredDeprecations());
        });
    }

    public function testDeprecation(): void
    {
        Deprecation::enableWithTriggerError();

        $this->expectDeprecationWithIdentifier('https://github.com/doctrine/deprecations/1234');

        $this->expectErrorHandler(
            'this is deprecated foo 1234 (DeprecationTest.php:%d called by TestCase.php:%d, https://github.com/doctrine/deprecations/1234, package doctrine/orm)',
            'https://github.com/doctrine/deprecations/1234'
        );

        Deprecation::trigger(
            'doctrine/orm',
            'https://github.com/doctrine/deprecations/1234',
            'this is deprecated %s %d',
            'foo',
            1234
        );

        $this->assertEquals(1, Deprecation::getUniqueTriggeredDeprecationsCount());

        Deprecation::trigger(
            'doctrine/orm',
            'https://github.com/doctrine/deprecations/1234',
            'this is deprecated %s %d',
            'foo',
            1234
        );

        $this->assertEquals(2, Deprecation::getUniqueTriggeredDeprecationsCount());
    }

    public function testDeprecationWithoutDeduplication(): void
    {
        Deprecation::enableWithTriggerError();
        Deprecation::withoutDeduplication();

        $this->expectErrorHandler(
            'this is deprecated foo 2222 (DeprecationTest.php:%d called by TestCase.php:%d, https://github.com/doctrine/deprecations/2222, package doctrine/orm)',
            'https://github.com/doctrine/deprecations/2222'
        );

        Deprecation::trigger(
            'doctrine/orm',
            'https://github.com/doctrine/deprecations/2222',
            'this is deprecated %s %d',
            'foo',
            2222
        );

        $this->assertEquals(1, Deprecation::getUniqueTriggeredDeprecationsCount());

        $this->expectErrorHandler(
            'this is deprecated foo 2222 (DeprecationTest.php:%d called by TestCase.php:%d, https://github.com/doctrine/deprecations/2222, package doctrine/orm)',
            'https://github.com/doctrine/deprecations/2222',
            2
        );

        Deprecation::trigger(
            'doctrine/orm',
            'https://github.com/doctrine/deprecations/2222',
            'this is deprecated %s %d',
            'foo',
            2222
        );

        $this->assertEquals(2, Deprecation::getUniqueTriggeredDeprecationsCount());
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

    public function expectDeprecationMock(string $message, string $identifier, string $package): MockObject
    {
        $mock = $this->createMock(LoggerInterface::class);
        $mock->method('notice')->with($message, $this->callback(function ($context) use ($identifier, $package) {
            $this->assertEquals($package, $context['package']);
            $this->assertEquals($identifier, $context['link']);

            return true;
        }));

        return $mock;
    }

    public function testDeprecationWithPsrLogger(): void
    {
        $this->expectDeprecationWithIdentifier('https://github.com/doctrine/deprecations/2222');

        $mock = $this->expectDeprecationMock(
            'this is deprecated foo 1234',
            'https://github.com/doctrine/deprecations/2222',
            'doctrine/orm'
        );
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

    public function testDeprecationIfCalledFromOutside(): void
    {
        Deprecation::enableWithTriggerError();

        $this->expectErrorHandler(
            'Bar::oldFunc() is deprecated, use Bar::newFunc() instead. (Bar.php:16 called by Foo.php:14, https://github.com/doctrine/foo, package doctrine/foo)',
            'https://github.com/doctrine/foo'
        );

        Foo::triggerDependencyWithDeprecation();
    }

    public function testDeprecationIfCalledFromOutsideNotTriggeringFromInside(): void
    {
        Deprecation::enableWithTriggerError();

        Foo::triggerDependencyWithDeprecationFromInside();

        $this->assertEquals(0, Deprecation::getUniqueTriggeredDeprecationsCount());
    }

    public function testDeprecationIfCalledFromOutsideNotTriggeringFromInsideClass(): void
    {
        Deprecation::enableWithTriggerError();

        $baz = new Baz();
        $baz->usingOldFunc();

        $this->assertEquals(0, Deprecation::getUniqueTriggeredDeprecationsCount());
    }

    public function testDeprecationCalledFromOutsideInRoot(): void
    {
        Deprecation::enableWithTriggerError();

        $this->expectDeprecationWithIdentifier('https://github.com/doctrine/deprecations/4444');

        $this->expectErrorHandler(
            'this is deprecated foo 1234 (RootDeprecation.php:%d called by DeprecationTest.php:%d, https://github.com/doctrine/deprecations/4444, package doctrine/orm)',
            'https://github.com/doctrine/deprecations/4444'
        );

        RootDeprecation::run();

        $this->assertEquals(1, Deprecation::getUniqueTriggeredDeprecationsCount());
    }

    public function testRunningCodeTemporarilyIgnoringDeprecations(): void
    {
        Deprecation::enableWithTriggerError();

        $this->expectNoDeprecationWithIdentifier('ignored-deprecation');

        IgnoringDeprecations::run();
        IgnoringDeprecations::runNested();
    }
}
