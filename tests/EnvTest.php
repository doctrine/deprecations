<?php

declare(strict_types=1);

namespace Doctrine\Deprecations;

use PHPUnit\Framework\TestCase;
use ReflectionProperty;

class EnvTest extends TestCase
{
    public function testEnvIsTakenIntoAccountWhenCallingEnableTrackingDeprecations(): void
    {
        $_ENV['DOCTRINE_DEPRECATIONS'] = 'trigger';
        Deprecation::enableTrackingDeprecations();
        $reflectionProperty = new ReflectionProperty(Deprecation::class, 'type');
        self::assertSame(1 | 2, $reflectionProperty->getValue());
        unset($_ENV['DOCTRINE_DEPRECATIONS']);
        $reflectionProperty->setValue(null, null);
    }
}
