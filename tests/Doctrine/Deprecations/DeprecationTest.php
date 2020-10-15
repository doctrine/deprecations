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
                '2.7',
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
                '2.7',
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

    public function testDeprecationWithNumericLinkPointingToGithubIssue(): void
    {
        Deprecation::enableWithTriggerError();

        $this->expectDeprecation();
        $this->expectDeprecationMessage('this is deprecated foo 1234 (DeprecationTest.php');

        try {
            Deprecation::trigger(
                'doctrine/orm',
                '2.7',
                '1234',
                'this is deprecated %s %d',
                'foo',
                1234
            );
        } catch (Throwable $e) {
            $this->assertEquals(1, Deprecation::getUniqueTriggeredDeprecationsCount());
            $this->assertEquals(['https://github.com/doctrine/orm/issue/1234' => 1], Deprecation::getTriggeredDeprecations());

            throw $e;
        }
    }

    public function testDeprecationWithPsrLogger(): void
    {
        $mock = $this->createMock(LoggerInterface::class);
        $mock->method('debug')->with('this is deprecated foo 1234', $this->callback(function ($context) {
            $this->assertEquals(__FILE__, $context['file']);
            $this->assertEquals('doctrine/orm', $context['package']);
            $this->assertEquals('2.7', $context['since']);
            $this->assertEquals('https://github.com/doctrine/deprecations/2222', $context['link']);

            return true;
        }));

        Deprecation::enableWithPsrLogger($mock);

        Deprecation::trigger(
            'doctrine/orm',
            '2.7',
            'https://github.com/doctrine/deprecations/2222',
            'this is deprecated %s %d',
            'foo',
            1234
        );
    }

    public function testDeprecationWithIgnoredPackage(): void
    {
        Deprecation::enableWithTriggerError();
        Deprecation::ignorePackage('doctrine/orm', '2.8');

        Deprecation::trigger(
            'doctrine/orm',
            '2.8',
            '1234',
            'this is deprecated %s %d',
            'foo',
            1234
        );

        $this->assertEquals(1, Deprecation::getUniqueTriggeredDeprecationsCount());
        $this->assertEquals(['https://github.com/doctrine/orm/issue/1234' => 1], Deprecation::getTriggeredDeprecations());
    }

    public function testDeprecationIgnoredOnce(): void
    {
        Deprecation::enableWithTriggerError();

        Deprecation::ignoreDeprecationOnce('1234', 'doctrine/orm');

        $this->triggerDeprecation();

        $this->expectDeprecation();
        $this->expectDeprecationMessage('this is deprecated foo 1234 (DeprecationTest.php');

        try {
            $this->triggerDeprecation();
        } catch (Throwable $e) {
            $this->assertEquals(1, Deprecation::getUniqueTriggeredDeprecationsCount());
            $this->assertEquals(['https://github.com/doctrine/orm/issue/1234' => 1], Deprecation::getTriggeredDeprecations());

            throw $e;
        }
    }

    private function triggerDeprecation()
    {
        Deprecation::trigger(
            'doctrine/orm',
            '2.7',
            '1234',
            'this is deprecated %s %d',
            'foo',
            1234
        );
    }
}
